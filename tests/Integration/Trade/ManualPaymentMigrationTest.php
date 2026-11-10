<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Trade;

use HyperfTests\Support\DatabaseTestCase;

final class ManualPaymentMigrationTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once BASE_PATH . '/migrations/2024_01_01_000008_create_mall_orders_table.php';
        require_once BASE_PATH . '/migrations/2024_01_01_000028_create_mall_payments_table.php';
        require_once BASE_PATH . '/migrations/2026_10_15_000001_add_manual_order_payment_method.php';
        (new \CreateMallOrdersTable())->up();
        (new \CreateMallPaymentsTable())->up();
    }

    public function testUpgradePreservesExistingMethodsAndNullableDefault(): void
    {
        foreach ([null, 'wechat', 'alipay', 'balance'] as $index => $method) {
            $this->insertOrder('OLD-' . $index, $method);
        }
        $this->connection->table('order_payments')->insert([
            'payment_no' => 'PAY-EXISTING', 'order_id' => 2, 'order_no' => 'OLD-1',
            'member_id' => 7, 'payment_method' => 'wechat', 'payment_amount' => 100,
            'paid_amount' => 100, 'status' => 'paid', 'third_party_no' => 'WECHAT-EXISTING',
        ]);
        $orders = $this->connection->table('orders')->orderBy('id')->get()->all();
        $payments = $this->connection->table('order_payments')->orderBy('id')->get()->all();

        (new \AddManualOrderPaymentMethod())->up();

        self::assertEquals($orders, $this->connection->table('orders')->orderBy('id')->get()->all());
        self::assertEquals($payments, $this->connection->table('order_payments')->orderBy('id')->get()->all());
        self::assertSame([null, 'wechat', 'alipay', 'balance'], $this->connection->table('orders')->orderBy('id')->pluck('pay_method')->all());
        $this->insertOrder('MANUAL', 'manual');
        self::assertSame('manual', $this->connection->table('orders')->where('order_no', 'MANUAL')->value('pay_method'));
        $column = $this->connection->selectOne("SHOW FULL COLUMNS FROM orders WHERE Field = 'pay_method'");
        self::assertSame('YES', $column->Null);
        self::assertNull($column->Default);
        self::assertSame('支付方式', $column->Comment);
    }

    public function testRollbackWithoutManualRecordsRestoresOriginalEnumAndPreservesOrders(): void
    {
        $this->insertOrder('EXISTING', 'wechat');
        $migration = new \AddManualOrderPaymentMethod();
        $migration->up();
        $migration->down();

        self::assertSame('wechat', $this->connection->table('orders')->value('pay_method'));
        $column = $this->connection->selectOne("SHOW FULL COLUMNS FROM orders WHERE Field = 'pay_method'");
        self::assertSame("enum('wechat','alipay','balance')", $column->Type);
        self::assertSame('YES', $column->Null);
        self::assertNull($column->Default);
    }

    public function testRollbackRefusesToEraseManualOrders(): void
    {
        $migration = new \AddManualOrderPaymentMethod();
        $migration->up();
        $this->insertOrder('MANUAL', 'manual');

        try {
            $migration->down();
            self::fail('Rollback must preserve manual payment history.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('不能回滚', $exception->getMessage());
        }

        self::assertSame('manual', $this->connection->table('orders')->value('pay_method'));
    }

    public function testRollbackAlsoChecksManualPaymentHistory(): void
    {
        $migration = new \AddManualOrderPaymentMethod();
        $migration->up();
        $orderId = $this->insertOrder('HISTORY', null);
        $this->connection->table('order_payments')->insert([
            'payment_no' => 'PAY-MANUAL', 'order_id' => $orderId, 'order_no' => 'HISTORY',
            'member_id' => 7, 'payment_method' => 'manual', 'payment_amount' => 100,
            'paid_amount' => 100, 'status' => 'paid',
        ]);

        try {
            $migration->down();
            self::fail('Rollback must preserve manual payment history.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('不能回滚', $exception->getMessage());
        }

        self::assertSame('manual', $this->connection->table('order_payments')->value('payment_method'));
        $column = $this->connection->selectOne("SHOW FULL COLUMNS FROM orders WHERE Field = 'pay_method'");
        self::assertStringContainsString("'manual'", $column->Type);
    }

    public function testPermissionSeedIsIdempotentAndDoesNotGrantExistingRoles(): void
    {
        $this->createMenuTables();
        $parentId = $this->connection->table('menu')->insertGetId(['name' => 'mall:order', 'parent_id' => 0]);
        $updateId = $this->connection->table('menu')->insertGetId(['name' => 'order:order:update', 'parent_id' => $parentId]);
        $this->connection->table('role_belongs_menu')->insert(['role_id' => 5, 'menu_id' => $updateId]);
        require_once BASE_PATH . '/seeders/menu_update_20261015.php';
        $seeder = new \MenuUpdate20261015();
        $seeder->run();
        $permission = $this->connection->table('menu')->where('name', 'order:order:manual-pay')->first();
        self::assertSame((int) $parentId, (int) $permission->parent_id);
        $meta = json_decode($permission->meta, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('B', $meta['type']);
        self::assertSame('人工认款', $meta['title']);

        $this->connection->table('menu')->where('id', $permission->id)->update(['status' => 2, 'remark' => '暂不开放']);
        $seeder->run();

        self::assertSame(1, $this->connection->table('menu')->where('name', 'order:order:manual-pay')->count());
        self::assertSame(2, (int) $this->connection->table('menu')->where('id', $permission->id)->value('status'));
        self::assertSame('暂不开放', $this->connection->table('menu')->where('id', $permission->id)->value('remark'));
        self::assertSame(1, $this->connection->table('role_belongs_menu')->count());
        self::assertSame(0, $this->connection->table('role_belongs_menu')->where('menu_id', $permission->id)->count());
    }

    public function testPermissionSeedRequiresOrderMenuBeforeWriting(): void
    {
        $this->createMenuTables();
        require_once BASE_PATH . '/seeders/menu_update_20261015.php';

        try {
            (new \MenuUpdate20261015())->run();
            self::fail('An order permission must not be created without its parent menu.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('订单管理菜单不存在', $exception->getMessage());
        }

        self::assertSame(0, $this->connection->table('menu')->count());
    }

    public function testFreshMenuSeedDefinesManualPaymentPermission(): void
    {
        $this->createMenuTables();
        require_once BASE_PATH . '/seeders/menu_seeder_20240926.php';
        (new \MenuSeeder20240926())->run();

        $parentId = $this->connection->table('menu')->where('name', 'mall:order')->value('id');
        self::assertNotNull($parentId);
        self::assertSame(1, $this->connection->table('menu')->where('name', 'order:order:manual-pay')->where('parent_id', $parentId)->count());
        $permission = $this->connection->table('menu')->where('name', 'order:order:manual-pay')->first();
        self::assertSame('人工认款', json_decode($permission->meta, true, 512, JSON_THROW_ON_ERROR)['title']);
        self::assertSame(0, $this->connection->table('role_belongs_menu')->count());
    }

    private function insertOrder(string $number, ?string $method): int
    {
        return (int) $this->connection->table('orders')->insertGetId([
            'order_no' => $number, 'member_id' => 7, 'goods_amount' => 100,
            'total_amount' => 100, 'pay_amount' => 100, 'pay_method' => $method,
        ]);
    }

    private function createMenuTables(): void
    {
        $this->connection->statement('CREATE TABLE menu (id BIGINT AUTO_INCREMENT PRIMARY KEY, parent_id BIGINT DEFAULT 0, name VARCHAR(50) UNIQUE, meta JSON, path VARCHAR(60) DEFAULT "", component VARCHAR(150) DEFAULT "", redirect VARCHAR(100) DEFAULT "", status INT DEFAULT 1, sort INT DEFAULT 0, created_by BIGINT DEFAULT 0, updated_by BIGINT DEFAULT 0, remark VARCHAR(60) DEFAULT "", created_at DATETIME NULL, updated_at DATETIME NULL)');
        $this->connection->statement('CREATE TABLE role_belongs_menu (id BIGINT AUTO_INCREMENT PRIMARY KEY, role_id BIGINT, menu_id BIGINT, created_at DATETIME NULL, updated_at DATETIME NULL)');
    }
}
