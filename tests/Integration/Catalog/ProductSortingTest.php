<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Catalog;

use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Infrastructure\Model\Product\Product;
use HyperfTests\Support\DatabaseTestCase;

final class ProductSortingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 数据库测试容器只补框架分页工厂，不启动应用或读取环境配置。
        \Hyperf\Context\ApplicationContext::setContainer(new class($this->container) implements \Psr\Container\ContainerInterface {
            public function __construct(private \Psr\Container\ContainerInterface $base) {}
            public function get(string $id): mixed { return $this->base->get($id); }
            public function has(string $id): bool { return $this->base->has($id); }
            public function make(string $id, array $parameters): mixed
            {
                if ($id !== \Hyperf\Contract\LengthAwarePaginatorInterface::class) {
                    throw new \RuntimeException('Unexpected test factory: ' . $id);
                }
                return new \Hyperf\Paginator\LengthAwarePaginator(...$parameters);
            }
        });
        require_once BASE_PATH . '/migrations/2024_01_01_000004_create_mall_products_table.php';
        (new \CreateMallProductsTable())->up();
        require_once BASE_PATH . '/migrations/2024_01_01_000005_create_mall_product_skus_table.php';
        (new \CreateMallProductSkusTable())->up();
        foreach ([[1, 999, 'active'], [2, 12000, 'active'], [3, 999, 'active'], [4, 1, 'inactive']] as [$id, $price, $status]) {
            $this->connection->table('products')->insert([
                'id' => $id, 'product_code' => 'P' . $id, 'name' => '测试商品' . $id,
                'category_id' => 1, 'min_price' => $price, 'max_price' => $price, 'status' => $status,
            ]);
        }
    }

    public function testPriceDirectionIsAppliedBeforePaginationWithStableTies(): void
    {
        $repository = new ProductRepository(new Product());
        $ascending = $repository->pageForApiList(['status' => 'active', 'sort' => 'price_asc'], 1, 2);
        self::assertSame([3, 1], array_column($ascending['list'], 'id'));
        self::assertSame(3, $ascending['total']);
        $second = $repository->pageForApiList(['status' => 'active', 'sort' => 'price_asc'], 2, 2);
        self::assertSame([2], array_column($second['list'], 'id'));
        $descending = $repository->pageForApiList(['status' => 'active', 'sort' => 'price_desc'], 1, 3);
        self::assertSame([2, 3, 1], array_column($descending['list'], 'id'));
        $default = $repository->pageForApiList(['status' => 'active'], 1, 3);
        self::assertSame([3, 2, 1], array_column($default['list'], 'id'));
    }
}
