<?php

declare(strict_types=1);

namespace HyperfTests\Integration\CustomerServiceTransport;

use HyperfTests\Support\IsolatedRedis;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerService\Infrastructure\Service\RedisCustomerServicePresence;
use Plugin\CustomerService\Infrastructure\Service\RedisSocketTicketStore;

final class RedisTransportTest extends TestCase
{
    use IsolatedRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nativeRedis = $this->openRedis();
    }

    protected function tearDown(): void
    {
        $this->cleanRedis();
        parent::tearDown();
    }

    public function testTicketIsAtomicSingleUseAndHasAnExpiry(): void
    {
        $store = new RedisSocketTicketStore($this->redisBridge($this->nativeRedis));
        $principal = ['principal_type' => 'member', 'principal_id' => 41];
        $store->put('ticket', $principal, 60);
        self::assertGreaterThan(0, $this->nativeRedis->ttl('customer-service:socket-ticket:ticket'));
        self::assertSame($principal, $store->consume('ticket'));
        self::assertNull($store->consume('ticket'));
        $this->nativeRedis->set('customer-service:socket-ticket:broken', '{invalid');
        self::assertNull($store->consume('broken'));
        self::assertFalse($this->nativeRedis->exists('customer-service:socket-ticket:broken') > 0);
    }

    public function testConcurrentConsumersHaveExactlyOneWinner(): void
    {
        if (! function_exists('pcntl_fork')) { self::markTestSkipped('pcntl is required for the ticket race test.'); }
        (new RedisSocketTicketStore($this->redisBridge($this->nativeRedis)))->put('race', ['principal_type' => 'member', 'principal_id' => 41], 60);
        $this->nativeRedis->close();
        $children = [];
        for ($index = 0; $index < 8; ++$index) {
            $pid = pcntl_fork();
            if ($pid === -1) { throw new \RuntimeException('Cannot start ticket consumer.'); }
            if ($pid === 0) {
                try {
                    $redis = $this->openRedis();
                    $result = (new RedisSocketTicketStore($this->redisBridge($redis)))->consume('race');
                    $redis->rPush('race:results', $result === null ? '0' : '1');
                    $redis->close();
                    exit(0);
                } catch (\Throwable) { exit(1); }
            }
            $children[] = $pid;
        }
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status));
            self::assertSame(0, pcntl_wexitstatus($status));
        }
        $this->nativeRedis = $this->openRedis();
        $results = $this->nativeRedis->lRange('race:results', 0, -1);
        self::assertCount(8, $results);
        self::assertSame(1, array_sum($results));
    }

    public function testClosingOneTabKeepsOtherLeaseAndExpiredLeasesDoNotRoute(): void
    {
        $presence = new RedisCustomerServicePresence($this->redisBridge($this->nativeRedis));
        // Agent heartbeat SQL is covered separately; this fixture represents two nodes' leases.
        $key = 'customer-service:presence:agent:7';
        $this->nativeRedis->zAdd($key, time() + 90, 'tab-one', time() + 90, 'tab-two', time() - 1, 'expired');
        self::assertTrue($presence->isOnline(7));
        self::assertSame(2, $this->nativeRedis->zCard($key));
        $presence->disconnect('agent', 7, 'tab-one');
        self::assertTrue($presence->isOnline(7));
        $presence->disconnect('agent', 7, 'tab-two');
        self::assertFalse($presence->isOnline(7));
        $this->nativeRedis->zAdd($key, time() - 1, 'expired');
        self::assertFalse($presence->isOnline(7));
        $presence->refresh('member', 41, 'member-tab');
        self::assertSame(1, $this->nativeRedis->zCard('customer-service:presence:member:41'));
        self::assertGreaterThan(0, $this->nativeRedis->ttl('customer-service:presence:member:41'));
    }
}
