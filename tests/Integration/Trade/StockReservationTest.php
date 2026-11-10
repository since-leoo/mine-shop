<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Trade;

use App\Infrastructure\Service\Trade\RedisStockReservation;
use Hyperf\Config\Config;
use Hyperf\Redis\Redis as HyperfRedis;
use PHPUnit\Framework\TestCase;

final class StockReservationTest extends TestCase
{
    private \Redis $redis;
    private RedisStockReservation $stock;
    private string $prefix;

    protected function setUp(): void
    {
        $socket = getenv('MINE_SHOP_TEST_REDIS_SOCKET');
        if (! $socket) {
            self::markTestSkipped('Set isolated test Redis socket.');
        }
        if (! str_starts_with($socket, '/tmp/mine-shop-tests.') || ! str_ends_with($socket, '/redis.sock')) {
            throw new \RuntimeException('Disposable Redis required.');
        }
        $this->redis = new \Redis();
        $this->redis->connect($socket);
        $proxy = new class($this->redis) extends HyperfRedis {
            public function __construct(private readonly \Redis $connection) {}
            public function __call($name, $arguments) { return $this->connection->{$name}(...$arguments); }
        };
        $this->prefix = 'codex:' . bin2hex(random_bytes(10)) . ':';
        $this->stock = new RedisStockReservation($proxy, new Config(['cache' => ['default' => ['prefix' => $this->prefix]]]));
        $this->redis->hMSet($this->prefix . 'product:stock', ['1' => 10, '2' => 2]);
    }

    protected function tearDown(): void
    {
        if (isset($this->redis)) {
            $this->redis->del($this->prefix . 'product:stock');
            $this->redis->close();
        }
    }

    public function testReserveReplayAndReleaseReplayChangeStockOnlyOnce(): void
    {
        $items = [['sku_id' => 1, 'quantity' => 3], ['sku_id' => 1, 'quantity' => 2]];
        $this->stock->reserve('A', $items, 'product:stock');
        $this->stock->reserve('A', $items, 'product:stock');
        self::assertSame('5', $this->redis->hGet($this->prefix . 'product:stock', '1'));
        $this->stock->release('A', $items, 'product:stock');
        $this->stock->release('A', $items, 'product:stock');
        self::assertSame('10', $this->redis->hGet($this->prefix . 'product:stock', '1'));
        $this->expectException(\DomainException::class);
        $this->stock->reserve('A', $items, 'product:stock');
    }

    public function testMultiSkuFailureDoesNotPartiallyReserveOrInventStockOnCompensation(): void
    {
        $items = [['sku_id' => 1, 'quantity' => 3], ['sku_id' => 2, 'quantity' => 3]];
        try {
            $this->stock->reserve('A', $items, 'product:stock');
            self::fail('insufficient stock expected');
        } catch (\DomainException) {
            $this->stock->release('A', $items, 'product:stock');
            self::assertSame('10', $this->redis->hGet($this->prefix . 'product:stock', '1'));
            self::assertSame('2', $this->redis->hGet($this->prefix . 'product:stock', '2'));
        }
    }

    public function testChangedPayloadCannotReserveOrReleaseSameReservation(): void
    {
        $this->stock->reserve('A', [['sku_id' => 1, 'quantity' => 3]], 'product:stock');
        $this->expectException(\DomainException::class);
        $this->stock->release('A', [['sku_id' => 1, 'quantity' => 8]], 'product:stock');
    }

    public function testReleaseBeforeDelayedReserveFencesTheTimedOutRequest(): void
    {
        $items = [['sku_id' => 1, 'quantity' => 3]];
        $this->stock->release('DELAYED', $items, 'product:stock');
        try {
            $this->stock->reserve('DELAYED', $items, 'product:stock');
            self::fail('released reservation must reject a delayed reserve');
        } catch (\DomainException) {
            self::assertSame('10', $this->redis->hGet($this->prefix . 'product:stock', '1'));
        }
    }

    public function testLegacyReleaseIsIdempotent(): void
    {
        $items = [['sku_id' => 1, 'quantity' => 3]];
        $this->stock->release('OLD', $items, 'product:stock', true);
        $this->stock->release('OLD', $items, 'product:stock', true);
        self::assertSame('13', $this->redis->hGet($this->prefix . 'product:stock', '1'));
    }
}
