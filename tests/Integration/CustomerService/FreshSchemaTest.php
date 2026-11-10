<?php

declare(strict_types=1);

namespace HyperfTests\Integration\CustomerService;

use Hyperf\DbConnection\Db;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\CustomerService\Infrastructure\Repository\DatabaseSupportConfiguration;

final class FreshSchemaTest extends DatabaseTestCase
{
    private const MIGRATIONS = [
        'migrations/2026_08_21_000001_create_customer_service_tables.php',
        'migrations/2026_08_21_000002_create_customer_service_audit_tables.php',
    ];

    public function testFreshSchemaIncludesAllRuntimeFieldsIndexesAndComments(): void
    {
        foreach (self::MIGRATIONS as $migration) {
            $this->migrate($migration);
        }
        // 检查真实 MySQL 元数据，避免只检查源码却漏掉实际未创建的列。
        $tables = $this->connection->select("SELECT TABLE_NAME AS name, TABLE_COMMENT AS comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'customer_service_%'");
        self::assertCount(12, $tables);
        foreach ($tables as $table) {
            self::assertNotSame('', $table->comment, $table->name . ' 缺少表备注');
        }
        $missingComments = $this->connection->select("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'customer_service_%' AND COLUMN_COMMENT = ''");
        self::assertSame([], $missingComments, '包括主键和时间字段在内的所有客服字段都必须有备注');

        $schema = $this->connection->getSchemaBuilder();
        foreach (['reception_state', 'generation', 'ai_turn', 'clarification_count', 'category_id', 'classification_json', 'ai_agent_id', 'config_revision', 'priority', 'last_sequence', 'queue_entered_at', 'assignment_expires_at', 'deferred_at', 'handoff_reason', 'resolution_status', 'resolution_source', 'resolved_at'] as $column) {
            self::assertTrue($schema->hasColumn('customer_service_conversations', $column), $column);
        }
        $indexes = array_column($this->connection->select("SHOW INDEX FROM customer_service_messages"), 'Key_name');
        self::assertContains('cs_message_sender_client_unique', $indexes);
        self::assertContains('cs_message_sequence_unique', $indexes);
        self::assertNotContains('cs_message_conversation_client_unique', $indexes);
        $sequence = $this->connection->selectOne("SELECT IS_NULLABLE AS nullable FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_service_messages' AND COLUMN_NAME = 'sequence'");
        self::assertSame('NO', $sequence->nullable);

        $configuration = new DatabaseSupportConfiguration();
        self::assertSame(['revision' => 0, 'snapshot' => []], $configuration->draft());
        self::assertSame(['revision' => 0, 'snapshot' => []], $configuration->published());
        self::assertSame(1, Db::table('customer_service_config_state')->count());
        foreach (array_column($tables, 'name') as $table) {
            if ($table !== 'customer_service_config_state') {
                self::assertSame(0, Db::table($table)->count(), '全新部署不应生成历史业务数据');
            }
        }
        $id = Db::table('customer_service_conversations')->insertGetId(['conversation_no' => 'FRESH', 'member_id' => 1]);
        $conversation = Db::table('customer_service_conversations')->where('id', $id)->first();
        self::assertSame('human_waiting', $conversation->reception_state);
        self::assertSame(1, (int) $conversation->generation);
        self::assertSame(0, (int) $conversation->last_sequence);
        self::assertSame(1, Db::table('customer_service_conversations')->where('reception_state', 'human_waiting')->count());
    }

    public function testFreshMigrationsRollbackAndRecreateWithoutUpgradeFiles(): void
    {
        foreach (self::MIGRATIONS as $path) {
            $this->migrate($path);
        }
        foreach (array_reverse(self::MIGRATIONS) as $path) {
            (require BASE_PATH . '/' . $path)->down();
        }
        self::assertSame([], $this->connection->select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'customer_service_%'"));
        foreach (self::MIGRATIONS as $path) {
            $this->migrate($path);
        }
        self::assertSame(1, Db::table('customer_service_config_state')->count());
        self::assertTrue($this->connection->getSchemaBuilder()->hasColumn('customer_service_conversations', 'reception_state'));
    }
}
