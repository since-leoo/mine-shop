<?php

declare(strict_types=1);

namespace HyperfTests\Integration\SmsAuthentication;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Infrastructure\Abstract\ICache;
use HyperfTests\Support\IsolatedRedis;
use PHPUnit\Framework\TestCase;
use Plugin\Sms\Service\EasySmsVerificationService;

/** No PDO connection may be inherited by the forked Redis consumers. */
final class SmsAtomicConsumptionTest extends TestCase
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

    public function testConcurrentConsumersHaveExactlyOneWinner(): void
    {
        if (! function_exists('pcntl_fork')) self::markTestSkipped('pcntl required for concurrency regression.');
        $phone = '13800138006'; $code = '001234';
        $this->nativeRedis->set('/plugin/sms/verification:code:login:' . $phone, $code, ['EX' => 300]);
        $this->nativeRedis->close(); $children = [];
        for ($index = 0; $index < 8; ++$index) {
            $pid = pcntl_fork();
            if ($pid === -1) throw new \RuntimeException('Unable to fork verifier.');
            if ($pid === 0) {
                try {
                    $this->nativeRedis = $this->openRedis();
                    $this->nativeRedis->rPush('sms-race:results', $this->service()->verifyCode($phone, 'login', $code) ? '1' : '0');
                    exit(0);
                } catch (\Throwable) { exit(1); }
            }
            $children[] = $pid;
        }
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status)); self::assertSame(0, pcntl_wexitstatus($status));
        }
        $this->nativeRedis = $this->openRedis();
        $results = $this->nativeRedis->lRange('sms-race:results', 0, -1);
        self::assertCount(8, $results); self::assertSame(1, array_sum($results));
    }

    public function testConcurrentSendsReserveOneSlotAndOneDailyQuota(): void
    {
        if (! function_exists('pcntl_fork')) self::markTestSkipped('pcntl required for concurrency regression.');
        $phone = '13800138007';
        $this->nativeRedis->close(); $children = [];
        for ($index = 0; $index < 8; ++$index) {
            $pid = pcntl_fork();
            if ($pid === -1) throw new \RuntimeException('Unable to fork sender.');
            if ($pid === 0) {
                try {
                    putenv('APP_ENV=testing');
                    $this->nativeRedis = $this->openRedis();
                    try { $this->service()->sendCode($phone, 'login'); $sent = '1'; }
                    catch (\DomainException) { $sent = '0'; }
                    $this->nativeRedis->rPush('sms-send-race:results', $sent);
                    exit(0);
                } catch (\Throwable) { exit(1); }
            }
            $children[] = $pid;
        }
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status)); self::assertSame(0, pcntl_wexitstatus($status));
        }
        $this->nativeRedis = $this->openRedis();
        $results = $this->nativeRedis->lRange('sms-send-race:results', 0, -1);
        self::assertCount(8, $results); self::assertSame(1, array_sum($results));
        self::assertSame('1', $this->nativeRedis->get('/plugin/sms/verification:daily:' . date('Ymd') . ':' . $phone));
        self::assertGreaterThan(0, $this->nativeRedis->ttl('/plugin/sms/verification:code:login:' . $phone));
        self::assertGreaterThan(0, $this->nativeRedis->ttl('/plugin/sms/verification:rate:login:' . $phone));
    }

    private function service(): EasySmsVerificationService
    {
        $cache = $this->createMock(ICache::class);
        $cache->method('setPrefix')->willReturnSelf();
        $cache->method('getPrefix')->willReturn('/plugin/sms/verification');
        $cache->method('eval')->willReturnCallback(fn (string $script, array $args, int $keys): bool => (bool) $this->nativeRedis->eval($script, $args, $keys));
        return new EasySmsVerificationService($this->createMock(DomainMallSettingService::class), $cache);
    }
}
