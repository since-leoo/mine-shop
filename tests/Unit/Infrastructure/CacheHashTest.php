<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Infrastructure;

use App\Infrastructure\Abstract\ICache;
use Hyperf\Redis\RedisProxy;
use PHPUnit\Framework\TestCase;

final class CacheHashTest extends TestCase
{
    public function testHashDeletesAllFieldsAndInitializesWithoutOverwriting(): void
    {
        $proxy = \Mockery::mock(RedisProxy::class);
        $proxy->shouldReceive('hDel')->once()->with('catalog:stock', '88', '89')->andReturn(2);
        $proxy->shouldReceive('hSetNx')->once()->with('catalog:stock', '88', 5)->andReturn(1);
        $cache = (new \ReflectionClass(ICache::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(ICache::class, 'redis'))->setValue($cache, $proxy);
        (new \ReflectionProperty(ICache::class, 'prefix'))->setValue($cache, 'catalog');
        try {
            self::assertTrue($cache->hDel('stock', '88', '89'));
            self::assertTrue($cache->hSetNx('stock', '88', 5));
        } finally {
            \Mockery::close();
        }
    }
}
