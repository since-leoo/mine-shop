<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Trade;

use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Infrastructure\Model\Order\Order;
use HyperfTests\Support\DatabaseTestCase;

final class AdminOrderQueryTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Hyperf\Context\ApplicationContext::setContainer(new class($this->container) implements \Psr\Container\ContainerInterface {
            public function __construct(private \Psr\Container\ContainerInterface $base) {}
            public function get(string $id): mixed { return $this->base->get($id); }
            public function has(string $id): bool { return $this->base->has($id); }
            public function make(string $id, array $parameters): mixed
            {
                if ($id !== \Hyperf\Contract\LengthAwarePaginatorInterface::class) {
                    throw new \RuntimeException('Unexpected test dependency: ' . $id);
                }
                return new \Hyperf\Paginator\LengthAwarePaginator(...$parameters);
            }
        });
        foreach ([
            '2024_01_01_000004_create_mall_products_table.php' => 'CreateMallProductsTable',
            '2024_01_01_000006_create_mall_members_table.php' => 'CreateMallMembersTable',
            '2024_01_01_000008_create_mall_orders_table.php' => 'CreateMallOrdersTable',
            '2024_01_01_000009_create_mall_order_items_table.php' => 'CreateMallOrderItemsTable',
            '2024_01_01_000010_create_mall_order_addresses_table.php' => 'CreateMallOrderAddressesTable',
            '2024_01_01_000011_create_mall_order_packages_table.php' => 'CreateMallOrderPackagesTable',
        ] as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
        $this->migrate('migrations/2026_10_16_000003_add_external_commerce_contracts.php');

        $this->connection->table('members')->insert(['id' => 7, 'nickname' => '测试会员', 'phone' => '13800000000']);
        foreach ([1, 2] as $id) {
            $this->connection->table('orders')->insert([
                'id' => $id, 'order_no' => 'QUERY-' . $id, 'member_id' => 7,
                'goods_amount' => 1200, 'total_amount' => 1300, 'pay_amount' => 1100,
                'shipping_fee' => 100, 'discount_amount' => 200, 'buyer_remark' => '详情备注',
                'expire_time' => '2030-01-01 00:00:00', 'created_at' => '2026-11-18 12:00:00',
            ]);
        }
        $this->connection->table('order_items')->insert([
            'order_id' => 1, 'product_id' => 3, 'sku_id' => 11,
            'product_name' => '测试商品', 'sku_name' => '红色', 'product_image' => '/product.png',
            'spec_values' => '["红色"]', 'unit_price' => 600, 'quantity' => 2, 'total_price' => 1200,
        ]);
        $this->connection->table('order_addresses')->insert([
            'order_id' => 1, 'name' => '收货人', 'phone' => '13900000000',
            'province' => '浙江省', 'city' => '杭州市', 'district' => '西湖区',
            'detail' => '测试路1号', 'full_address' => '浙江省杭州市西湖区测试路1号',
        ]);
        $this->connection->table('order_packages')->insert([
            'order_id' => 1, 'package_no' => 'PACKAGE-1', 'express_no' => 'EXPRESS-1',
        ]);
    }

    public function testListOnlyReturnsRequiredFieldsWithBatchLoadedRelations(): void
    {
        $this->connection->enableQueryLog();
        $page = (new OrderRepository(new Order()))->page([], 1, 20);
        $queries = $this->connection->getQueryLog();
        $this->connection->disableQueryLog();

        self::assertSame(2, $page['total']);
        self::assertSame([2, 1], array_column($page['list'], 'id'));
        self::assertCount(5, $queries, 'Count, orders and three batch-loaded relations; no per-order queries or packages.');
        $row = $page['list'][1];
        self::assertEqualsCanonicalizing([
            'id', 'order_no', 'member_id', 'status', 'pay_amount', 'pay_status', 'pay_no',
            'pay_method', 'shipping_status', 'expire_time', 'created_at', 'member', 'items', 'address',
        ], array_keys($row));
        self::assertSame(1100, $row['pay_amount']);
        self::assertNotEmpty($row['expire_time']);
        self::assertSame(['id' => 7, 'nickname' => '测试会员', 'phone' => '13800000000'], $row['member']);
        self::assertSame(['order_id' => 1, 'name' => '收货人', 'phone' => '13900000000'], $row['address']);
        self::assertSame([[
            'order_id' => 1, 'product_name' => '测试商品', 'sku_name' => '红色',
            'product_image' => '/product.png', 'quantity' => 2,
        ]], $row['items']);
        self::assertNull($page['list'][0]['address']);
        self::assertSame([], $page['list'][0]['items']);
        self::assertSame('测试会员', $page['list'][0]['member']['nickname']);
    }

    public function testListKeepsFiltersPaginationAndStatistics(): void
    {
        $repository = new OrderRepository(new Order());
        $page = $repository->page(['product_name' => '测试', 'member_phone' => '139'], 1, 1);
        self::assertSame(1, $page['total']);
        self::assertSame([1], array_column($page['list'], 'id'));
        self::assertSame(1, $repository->stats(['product_name' => '测试'])['pending']);
        $secondPage = $repository->page([], 2, 1);
        self::assertSame(2, $secondPage['total']);
        self::assertSame([1], array_column($secondPage['list'], 'id'));
    }

    public function testDetailAndExportKeepFullDataAndMissingDetailReturnsNull(): void
    {
        $repository = new OrderRepository(new Order());
        $repository->page([], 1, 20);
        $detail = $repository->findDetail(1);
        self::assertSame('详情备注', $detail['buyer_remark']);
        self::assertSame(1300, $detail['total_amount']);
        self::assertSame(100, $detail['shipping_fee']);
        self::assertSame(200, $detail['discount_amount']);
        self::assertSame('测试路1号', $detail['address']['detail']);
        self::assertSame(600, $detail['items'][0]['unit_price']);
        self::assertSame(['红色'], $detail['items'][0]['spec_values']);
        self::assertSame('EXPRESS-1', $detail['packages'][0]['express_no']);
        self::assertNull($repository->findDetail(999));

        $export = iterator_to_array($repository->getExportData(['order_no' => 'QUERY-1']));
        self::assertCount(1, $export);
        self::assertSame('测试路1号', $export[0]['address']['detail']);
        self::assertSame(600, $export[0]['unit_price']);
        self::assertSame(['红色'], $export[0]['spec_values']);
    }
}
