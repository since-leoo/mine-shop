<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Trade;

use HyperfTests\Support\DatabaseTestCase;

final class FreshTradeSchemaTest extends DatabaseTestCase
{
    private const MIGRATIONS = [
        '2024_01_01_000008_create_mall_orders_table.php' => 'CreateMallOrdersTable',
        '2024_01_01_000028_create_mall_payments_table.php' => 'CreateMallPaymentsTable',
        '2024_01_01_000029_create_mall_payment_refunds_table.php' => 'CreateMallPaymentRefundsTable',
        '2026_03_16_000001_create_after_sale_tables.php' => 'CreateAfterSaleTables',
    ];

    private const TABLES = ['orders', 'order_submissions', 'trade_outbox', 'order_payments', 'payment_refunds', 'order_trade_after_sale'];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::MIGRATIONS as $file => $class) {
            require_once BASE_PATH . '/migrations/' . $file;
            (new $class())->up();
        }
    }

    public function testOriginalMigrationsCreateAllRecoveryStateAndDatabaseComments(): void
    {
        $database = $this->connection->getDatabaseName();
        foreach (self::TABLES as $table) {
            $metadata = $this->connection->selectOne(
                'SELECT TABLE_COMMENT AS note FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$database, $table]
            );
            self::assertNotNull($metadata, $table . ' must be created by its original migration');
            self::assertNotSame('', trim($metadata->note), $table . ' table comment');
            $columns = $this->connection->select(
                'SELECT COLUMN_NAME AS name, COLUMN_COMMENT AS note FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$database, $table]
            );
            self::assertNotEmpty($columns);
            foreach ($columns as $column) {
                self::assertNotSame('', trim($column->note), $table . '.' . $column->name . ' comment');
            }
        }
        $refundStatus = $this->connection->selectOne(
            'SELECT DATA_TYPE AS type, CHARACTER_MAXIMUM_LENGTH AS length FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$database, 'order_trade_after_sale', 'refund_status']
        );
        self::assertSame('varchar', $refundStatus->type);
        self::assertSame(30, (int) $refundStatus->length);
        $defaultStatus = $this->connection->selectOne(
            'SELECT COLUMN_DEFAULT AS status FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$database, 'payment_refunds', 'status']
        );
        self::assertSame('requested', $defaultStatus->status);

        // 直接验证新库能保存业务失败态，避免只检查迁移源码却遗漏数据库枚举限制。
        $this->connection->table('order_trade_after_sale')->insert([
            'after_sale_no' => 'SCHEMA-CHECK', 'order_id' => 1, 'order_item_id' => 1, 'member_id' => 1,
            'type' => 'refund_only', 'refund_status' => 'failed', 'reason' => 'schema test',
        ]);
        self::assertSame('failed', $this->connection->table('order_trade_after_sale')->value('refund_status'));
    }

    public function testOriginalMigrationRollbackAndRebuildIncludesRecoveryTables(): void
    {
        foreach (array_reverse(self::MIGRATIONS) as $class) {
            (new $class())->down();
        }
        foreach (self::TABLES as $table) {
            self::assertFalse($this->connection->getSchemaBuilder()->hasTable($table));
        }
        foreach (self::MIGRATIONS as $class) {
            (new $class())->up();
        }
        foreach (self::TABLES as $table) {
            self::assertTrue($this->connection->getSchemaBuilder()->hasTable($table));
        }
    }
}
