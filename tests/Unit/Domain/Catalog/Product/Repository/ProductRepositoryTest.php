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

namespace HyperfTests\Unit\Domain\Catalog\Product\Repository;

use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Infrastructure\Model\Product\Product;
use Hyperf\Database\Model\Builder;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ProductRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testHandleSearchFiltersByIds(): void
    {
        /** @var Product $product */
        $product = Mockery::mock(Product::class);
        $repository = new ProductRepository($product);
        /** @var Builder $query */
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('when')
            ->andReturnUsing(static function (mixed $condition, callable $callback) use ($query): Builder {
                if ($condition) {
                    $callback($query);
                }

                return $query;
            });
        $query->shouldReceive('whereIn')->once()->with('id', [3, 1, 2])->andReturnSelf();
        $query->shouldReceive('with')->once()->andReturnSelf();

        $result = $repository->handleSearch($query, ['ids' => [3, 1, 2]]);

        self::assertSame($query, $result);
    }
}
