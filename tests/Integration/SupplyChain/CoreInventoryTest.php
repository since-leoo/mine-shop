<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace HyperfTests\Integration\SupplyChain;

use App\Infrastructure\Service\Trade\ExternalInventoryProjection;
use App\Infrastructure\Service\Trade\RedisStockReservation;
use Hyperf\Config\Config;
use HyperfTests\Support\IsolatedRedis;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class CoreInventoryTest extends TestCase
{
    use IsolatedRedis;

    private ExternalInventoryProjection $projection;

    private RedisStockReservation $reservations;

    protected function setUp(): void
    {
        $this->nativeRedis = $this->openRedis();
        $bridge = $this->redisBridge($this->nativeRedis);
        $config = new Config(['cache' => ['default' => ['prefix' => '']]]);
        $this->projection = new ExternalInventoryProjection($bridge, $config);
        $this->reservations = new RedisStockReservation($bridge, $config);
    }

    protected function tearDown(): void
    {
        $this->cleanRedis();
    }

    public function testStockRefreshPreservesReservationsAndCancelledOrderRestoresOnlyOnce(): void
    {
        self::assertSame(100, $this->projection->project('supply_chain', 11, 100, 1, true, []));
        $items = [['sku_id' => 11, 'quantity' => 2]];
        $this->reservations->reserve('ORDER1', $items, 'product:stock');
        $this->reservations->reserve('ORDER1', $items, 'product:stock');
        self::assertSame(98, $this->projection->project('supply_chain', 11, 100, 2, false, []));
        self::assertSame(98, $this->projection->project('supply_chain', 11, 200, 1, false, []));
        $this->reservations->release('ORDER1', $items, 'product:stock');
        $this->reservations->release('ORDER1', $items, 'product:stock');
        self::assertSame('100', $this->nativeRedis->hGet('product:stock', '11'));
        self::assertSame('0', $this->nativeRedis->hGet('product:stock', '_external_held:11'));
    }

    public function testAcknowledgedProcurementCannotBeRestockedByCustomerRefund(): void
    {
        $this->projection->project('supply_chain', 11, 100, 1, true, []);
        $items = [['sku_id' => 11, 'quantity' => 2]];
        $this->reservations->reserve('ORDER1', $items, 'product:stock');
        $this->projection->acknowledge('supply_chain', 'ORDER1', $items);
        $this->projection->acknowledge('supply_chain', 'ORDER1', $items);
        $this->reservations->release('ORDER1', $items, 'product:stock');
        self::assertSame('98', $this->nativeRedis->hGet('product:stock', '11'));
        self::assertSame(96, $this->projection->project('supply_chain', 11, 98, 2, false, []));
        self::assertSame(98, $this->projection->project('supply_chain', 11, 98, 3, false, ['ORDER1']));
        self::assertSame(98, $this->projection->project('supply_chain', 11, 98, 4, false, ['ORDER1']));
    }

    public function testReconcileCannotReleaseAnUnacknowledgedOrder(): void
    {
        $this->projection->project('supply_chain', 11, 10, 1, true, []);
        $this->reservations->reserve('ORDER1', [['sku_id' => 11, 'quantity' => 2]], 'product:stock');
        try {
            $this->projection->project('supply_chain', 11, 10, 2, false, ['ORDER1']);
            self::fail('unacknowledged reservation must not be cleared');
        } catch (\DomainException) {
            self::assertSame('8', $this->nativeRedis->hGet('product:stock', '11'));
            self::assertSame('2', $this->nativeRedis->hGet('product:stock', '_external_held:11'));
        }
    }

    public function testLostRedisProjectionFailsClosedInsteadOfResettingReservations(): void
    {
        $this->projection->project('supply_chain', 11, 10, 1, true, []);
        $this->nativeRedis->del('product:stock');
        $this->expectException(\DomainException::class);
        $this->projection->project('supply_chain', 11, 10, 2, false, []);
    }

    public function testMixedStockReservationFailureRemainsAtomic(): void
    {
        $this->projection->project('supply_chain', 11, 10, 1, true, []);
        $this->nativeRedis->hSet('product:stock', '12', 1);
        try {
            $this->reservations->reserve('MIXED', [['sku_id' => 11, 'quantity' => 2], ['sku_id' => 12, 'quantity' => 2]], 'product:stock');
            self::fail('local stock insufficient');
        } catch (\DomainException) {
            self::assertSame('10', $this->nativeRedis->hGet('product:stock', '11'));
            self::assertFalse($this->nativeRedis->hGet('product:stock', '_external_held:11'));
        }
    }

    public function testProcurementGroupsAcknowledgeOnlyTheirOwnSkuSubset(): void
    {
        $this->projection->project('supply_chain', 11, 10, 1, true, []);
        $this->projection->project('supply_chain', 12, 10, 1, true, []);
        $items = [['sku_id' => 11, 'quantity' => 2], ['sku_id' => 12, 'quantity' => 3]];
        $this->reservations->reserve('GROUPED', $items, 'product:stock');
        $this->projection->acknowledge('supply_chain', 'GROUPED', [$items[0]]);
        $this->reservations->release('GROUPED', $items, 'product:stock');
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', '11'));
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', '12'));
        self::assertSame('ack:2', $this->nativeRedis->hGet('product:stock', '_external_order:GROUPED:11'));
        self::assertSame('released', $this->nativeRedis->hGet('product:stock', '_external_order:GROUPED:12'));
    }

    public function testProtectedReleaseRetriesWithoutDoubleReturningMerchantStock(): void
    {
        $this->projection->project('supply_chain', 11, 10, 1, true, []);
        $this->nativeRedis->hSet('product:stock', '12', 10);
        $items = [['sku_id' => 11, 'quantity' => 2], ['sku_id' => 12, 'quantity' => 3]];
        $this->reservations->reserve('RACE', $items, 'product:stock');
        $this->projection->protect('supply_chain', 'RACE', [$items[0]]);
        $this->projection->protect('supply_chain', 'RACE', [$items[0]]);
        foreach ([1, 2] as $_) {
            try {
                $this->reservations->release('RACE', $items, 'product:stock');
                self::fail('outbox must stay pending');
            } catch (\RuntimeException $error) {
                self::assertStringContainsString('等待释放重试', $error->getMessage());
            }
            self::assertSame('8', $this->nativeRedis->hGet('product:stock', '11'));
            self::assertSame('10', $this->nativeRedis->hGet('product:stock', '12'));
        }
        $this->projection->clearProtection('supply_chain', 'RACE', [$items[0]]);
        $this->projection->clearProtection('supply_chain', 'RACE', [$items[0]]);
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', '11'));
        self::assertSame('2', $this->nativeRedis->hGet('product:stock', '_external_held:11'));
        $this->reservations->release('RACE', $items, 'product:stock');
        $this->reservations->release('RACE', $items, 'product:stock');
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', '11'));
        self::assertSame('10', $this->nativeRedis->hGet('product:stock', '12'));
        self::assertSame('0', $this->nativeRedis->hGet('product:stock', '_external_held:11'));
        $this->expectException(\DomainException::class);
        $this->projection->protect('supply_chain', 'RACE', [$items[0]]);
    }

    public function testAcceptedProtectedPurchaseCannotBeClearedOrReturnedByPendingRefund(): void
    {
        $this->projection->project('supply_chain', 11, 10, 1, true, []);
        $items = [['sku_id' => 11, 'quantity' => 2]];
        $this->reservations->reserve('ACCEPT', $items, 'product:stock');
        $this->projection->protect('supply_chain', 'ACCEPT', $items);
        try {
            $this->reservations->release('ACCEPT', $items, 'product:stock');
            self::fail('pending external request');
        } catch (\RuntimeException) {
            self::assertSame('8', $this->nativeRedis->hGet('product:stock', '11'));
        }
        $this->projection->acknowledge('supply_chain', 'ACCEPT', $items);
        $this->projection->acknowledge('supply_chain', 'ACCEPT', $items);
        $this->reservations->release('ACCEPT', $items, 'product:stock');
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', '11'));
        self::assertSame('ack:2', $this->nativeRedis->hGet('product:stock', '_external_order:ACCEPT:11'));
        $this->expectException(\DomainException::class);
        $this->projection->clearProtection('supply_chain', 'ACCEPT', $items);
    }

    public function testProtectionBatchValidationDoesNotPartiallyChangeAnySku(): void
    {
        foreach ([11, 12] as $id) {
            $this->projection->project('supply_chain', $id, 10, 1, true, []);
        }
        $items = [['sku_id' => 11, 'quantity' => 2], ['sku_id' => 12, 'quantity' => 3]];
        $this->reservations->reserve('BATCH', $items, 'product:stock');
        try {
            $this->projection->protect('supply_chain', 'BATCH', [$items[0], ['sku_id' => 12, 'quantity' => 99]]);
            self::fail('bad quantity');
        } catch (\DomainException) {
            self::assertSame('reserved:2', $this->nativeRedis->hGet('product:stock', '_external_order:BATCH:11'));
        }
        $this->projection->protect('supply_chain', 'BATCH', [$items[0]]);
        $this->projection->clearProtection('supply_chain', 'BATCH', [$items[0]]);
        self::assertSame('reserved:2', $this->nativeRedis->hGet('product:stock', '_external_order:BATCH:11'));
        self::assertSame('reserved:3', $this->nativeRedis->hGet('product:stock', '_external_order:BATCH:12'));
        self::assertSame('8', $this->nativeRedis->hGet('product:stock', '11'));
        $this->projection->protect('supply_chain', 'BATCH', [$items[0]]);
        self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:BATCH:11'));
    }

    public function testConcurrentRefundAndPostProtectionCannotBothReturnStockAndPermitPost(): void
    {
        if (! \function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl required for real concurrent Redis race');
        }
        for ($attempt = 0; $attempt < 8; ++$attempt) {
            $skuId = 100 + $attempt;
            $trade = 'CONCURRENT' . $attempt;
            $this->projection->project('supply_chain', $skuId, 10, 1, true, []);
            $items = [['sku_id' => $skuId, 'quantity' => 2]];
            $this->reservations->reserve($trade, $items, 'product:stock');
            [$parentSocket, $childSocket] = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
            $pid = pcntl_fork();
            if ($pid === -1) {
                self::fail('cannot fork concurrent test');
            }
            if ($pid === 0) {
                fclose($parentSocket);
                $client = new \Redis();
                $client->connect(getenv('MINE_SHOP_TEST_REDIS_SOCKET'));
                $client->setOption(\Redis::OPT_PREFIX, $this->redisPrefix);
                fread($childSocket, 1);
                $result = $client->eval(ExternalInventoryProjection::PROTECT_SCRIPT, ['product:stock', 'supply_chain', $trade, (string) $skuId, '2'], 1);
                fwrite($childSocket, (string) $result);
                fclose($childSocket);
                $client->close();
                exit(0);
            }
            fclose($childSocket);
            fwrite($parentSocket, '1');
            $deferred = false;
            try {
                $this->reservations->release($trade, $items, 'product:stock');
            } catch (\RuntimeException) {
                $deferred = true;
            }
            $protect = (int) stream_get_contents($parentSocket);
            fclose($parentSocket);
            pcntl_waitpid($pid, $status);
            self::assertSame(0, pcntl_wexitstatus($status));
            if ($protect === 1) {
                self::assertTrue($deferred);
                self::assertSame('8', $this->nativeRedis->hGet('product:stock', (string) $skuId));
                self::assertSame('protected:2', $this->nativeRedis->hGet('product:stock', '_external_order:' . $trade . ':' . $skuId));
            } else {
                self::assertSame(-3, $protect);
                self::assertFalse($deferred);
                self::assertSame('10', $this->nativeRedis->hGet('product:stock', (string) $skuId));
            }
        }
    }

    public function testSameStockVersionCannotBeReusedWithAnotherQuantity(): void
    {
        $this->projection->project('supply_chain', 11, 10, 1, true, []);
        $this->expectException(\DomainException::class);
        $this->projection->project('supply_chain', 11, 20, 1, false, []);
    }
}
