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

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\Schema\Blueprint;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\Service\DomainConnectionService;
use Plugin\SupplyChain\Handler\YunzhongClientHandler;
use Plugin\SupplyChain\Infrastructure\Persistence\DatabaseApiLogRepository;
use Plugin\SupplyChain\Infrastructure\Persistence\DatabaseConnectionRepository;
use Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong\YunzhongClient;
use Plugin\SupplyChain\Infrastructure\Security\CredentialCipher;
use Plugin\SupplyChain\Interface\Dto\ConnectionDto;
use Plugin\SupplyChain\Interfaces\Data\ProductQuery;
use Plugin\SupplyChain\Interfaces\RequestLimiterInterface;
use Plugin\SupplyChain\Interfaces\TokenStoreInterface;
use Plugin\SupplyChain\Service\SupplyChainSettingsResolver;

/**
 * Applies real MySQL DDL only inside DatabaseTestCase's disposable database.
 * @internal
 * @coversNothing
 */
final class PluginSchemaTest extends DatabaseTestCase
{
    private const MIGRATION = 'plugins/supply-chain/Database/Migrations/2026_10_16_000100_create_supply_chain_tables.php';

    private const TABLES = [
        'supply_after_sales', 'supply_api_logs', 'supply_connections', 'supply_messages',
        'supply_order_items', 'supply_orders', 'supply_product_links', 'supply_products',
        'supply_shipments', 'supply_sku_links', 'supply_skus', 'supply_task_attempts',
        'supply_task_events', 'supply_tasks',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrate(self::MIGRATION);
    }

    public function testEveryPluginTableAndColumnHasAPersistedDatabaseComment(): void
    {
        $tables = $this->connection->select('SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME');
        self::assertSame(self::TABLES, array_column($tables, 'TABLE_NAME'));
        foreach ($tables as $table) {
            self::assertNotSame('', trim($table->TABLE_COMMENT), $table->TABLE_NAME . ' must have a table comment');
        }
        $columns = $this->connection->select('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION');
        self::assertGreaterThan(100, \count($columns));
        foreach ($columns as $column) {
            self::assertNotSame('', trim($column->COLUMN_COMMENT), $column->TABLE_NAME . '.' . $column->COLUMN_NAME . ' must have a field comment');
        }
        $foreignKeys = $this->connection->select('SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()');
        self::assertSame([], $foreignKeys, 'Plugin mappings are logical references and must not add cross-module database foreign keys');
    }

    public function testBusinessIdentityAndDeliveryIdempotencyIndexesAreUnique(): void
    {
        $indexes = [];
        $rows = $this->connection->select('SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND NON_UNIQUE = 0 ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX');
        foreach ($rows as $row) {
            $indexes[$row->TABLE_NAME][$row->INDEX_NAME][] = $row->COLUMN_NAME;
        }
        foreach ([
            ['supply_products', ['connection_id', 'external_product_id']],
            ['supply_skus', ['connection_id', 'external_sku_id']],
            ['supply_product_links', ['supply_product_id']],
            ['supply_product_links', ['product_id']],
            ['supply_sku_links', ['supply_sku_id']],
            ['supply_sku_links', ['sku_id']],
            ['supply_orders', ['connection_id', 'merchant_order_no']],
            ['supply_orders', ['order_id', 'connection_id', 'group_key']],
            ['supply_order_items', ['supply_order_id', 'order_item_id']],
            ['supply_shipments', ['shipment_key']],
            ['supply_after_sales', ['after_sale_id', 'supply_order_id']],
            ['supply_messages', ['connection_id', 'external_message_id']],
            ['supply_tasks', ['business_key']],
            ['supply_task_attempts', ['task_id', 'attempt_no']],
        ] as [$table, $columns]) {
            self::assertContains($columns, array_values($indexes[$table] ?? []), $table . ': ' . implode(',', $columns));
        }
    }

    public function testTwoConnectionsCanShareExternalIdsButDuplicateImportsCannotRebindLocalGoods(): void
    {
        $product = ['connection_id' => 1, 'external_product_id' => '00123', 'name' => '供货商品',
            'snapshot' => '{}', 'snapshot_hash' => str_repeat('a', 64), 'fetched_at' => date('Y-m-d H:i:s')];
        $first = $this->connection->table('supply_products')->insertGetId($product);
        $second = $this->connection->table('supply_products')->insertGetId(array_replace($product, ['connection_id' => 2]));
        self::assertNotSame($first, $second);
        self::assertSame('00123', $this->connection->table('supply_products')->where('id', $first)->value('external_product_id'));
        $this->assertDuplicate(fn () => $this->connection->table('supply_products')->insert($product));

        $link = ['connection_id' => 1, 'supply_product_id' => $first, 'product_id' => 100, 'sync_policy' => '{}'];
        $this->connection->table('supply_product_links')->insert($link);
        $this->assertDuplicate(fn () => $this->connection->table('supply_product_links')->insert(array_replace($link, ['product_id' => 101])));
        $this->assertDuplicate(fn () => $this->connection->table('supply_product_links')->insert(array_replace($link, ['connection_id' => 2, 'supply_product_id' => $second])));
        $this->connection->table('supply_product_links')->insert(array_replace($link, ['connection_id' => 2, 'supply_product_id' => $second, 'product_id' => 101]));
        self::assertSame(2, $this->connection->table('supply_product_links')->count());
    }

    public function testDuplicateQueueEventsAndMessageReceiptsCannotCreateDuplicateWork(): void
    {
        $task = ['connection_id' => 1, 'type' => 'order_push', 'business_key' => 'order:1:connection:1',
            'payload' => '{"purchase_id":1}', 'trace_id' => str_repeat('a', 32)];
        $taskId = $this->connection->table('supply_tasks')->insertGetId($task);
        $this->assertDuplicate(fn () => $this->connection->table('supply_tasks')->insert($task));
        $attempt = ['task_id' => $taskId, 'attempt_no' => 1, 'lease_token' => str_repeat('b', 32),
            'parameters' => '{}', 'started_at' => date('Y-m-d H:i:s')];
        $this->connection->table('supply_task_attempts')->insert($attempt);
        $this->assertDuplicate(fn () => $this->connection->table('supply_task_attempts')->insert($attempt));
        $this->connection->table('supply_task_attempts')->insert(array_replace($attempt, ['attempt_no' => 2]));
        self::assertSame(2, $this->connection->table('supply_task_attempts')->count());

        $message = ['connection_id' => 1, 'external_message_id' => 'message-1', 'type' => 'goods.update', 'payload' => '{"product_id":123}'];
        $this->connection->table('supply_messages')->insert($message);
        $this->assertDuplicate(fn () => $this->connection->table('supply_messages')->insert($message));
        $this->connection->table('supply_messages')->insert(array_replace($message, ['connection_id' => 2]));
        self::assertSame(2, $this->connection->table('supply_messages')->count());
    }

    public function testExternalStringIdentifiersAreCaseSensitiveAndKeepLeadingZeros(): void
    {
        $row = ['connection_id' => 1, 'name' => '大小写不同的上游商品', 'snapshot' => '{}',
            'snapshot_hash' => str_repeat('a', 64), 'fetched_at' => date('Y-m-d H:i:s')];
        foreach (['AbC', 'abc', '00123', '123'] as $id) {
            $this->connection->table('supply_products')->insert($row + ['external_product_id' => $id]);
        }
        self::assertSame(4, $this->connection->table('supply_products')->count());
        foreach (['AbC', 'abc', '00123', '123'] as $id) {
            self::assertSame(1, $this->connection->table('supply_products')->where('external_product_id', $id)->count());
        }
    }

    public function testRollbackAndReinstallAffectOnlyPluginTables(): void
    {
        $this->connection->getSchemaBuilder()->create('core_sentinel', static function (Blueprint $table): void {
            $table->id()->comment('测试主项目保留记录');
            $table->comment('主项目隔离哨兵');
        });
        $this->connection->table('core_sentinel')->insert(['id' => 7]);
        $migration = require BASE_PATH . '/' . self::MIGRATION;
        $migration->down();
        foreach (self::TABLES as $table) {
            self::assertFalse($this->connection->getSchemaBuilder()->hasTable($table), $table);
        }
        self::assertSame(7, (int) $this->connection->table('core_sentinel')->value('id'));
        // down tolerates absent plugin tables; a fresh up rebuilds the complete plugin schema.
        $migration->down();
        $migration->up();
        foreach (self::TABLES as $table) {
            self::assertTrue($this->connection->getSchemaBuilder()->hasTable($table), $table);
        }
        self::assertSame(7, (int) $this->connection->table('core_sentinel')->value('id'));
        $this->connection->getSchemaBuilder()->drop('core_sentinel');
        $this->testEveryPluginTableAndColumnHasAPersistedDatabaseComment();
    }

    public function testEncryptedSettingsAndSdkAuditContractsMatchTheActualSchema(): void
    {
        $repository = new SupplyRepository();
        $cipher = new CredentialCipher(['fixture' => base64_encode(str_repeat('k', 32))], 'fixture');
        $settings = new DomainConnectionService($repository, $cipher);
        $fields = ['name' => '测试供货方', 'api_domain' => 'https://supplier.test', 'api_path' => '/supplyapi',
            'app_key' => 'fixture-app', 'mode' => 'active', 'enabled' => true];
        $id = $this->connection->transaction(static fn () => $settings->save(null, new ConnectionDto($fields, 'fixture-secret'), 7));
        $row = $repository->require('connections', $id);
        self::assertSame(1, (int) $row['credential_version']);
        self::assertStringNotContainsString('fixture-secret', $row['app_secret_ciphertext']);
        self::assertSame('fixture-secret', $cipher->decrypt($row['app_secret_ciphertext'], 'supply-connection:' . $id));
        $resolver = new SupplyChainSettingsResolver(new DatabaseConnectionRepository($repository), $cipher);
        $first = $resolver->resolve($id);
        self::assertSame('https://supplier.test/supplyapi', $first->baseUrl);
        $this->connection->transaction(static fn () => $settings->save($id, new ConnectionDto($fields, 'rotated-fixture-secret'), 8));
        $config = $resolver->resolve($id);
        self::assertSame('rotated-fixture-secret', $config->appSecret);
        self::assertNotSame($first->tokenKey(), $config->tokenKey());

        $tokens = $this->createMock(TokenStoreInterface::class);
        $tokens->method('get')->willReturn('fixture-token');
        $limiter = $this->createMock(RequestLimiterInterface::class);
        $limiter->method('acquire')->willReturn('fixture-lease');
        $http = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":0,"data":{"list":[],"total":0}}'),
        ]))]);
        $client = new YunzhongClientHandler(new YunzhongClient($config, $http, $tokens, new DatabaseApiLogRepository($repository), $limiter));
        self::assertSame([], $client->catalog()->search(new ProductQuery())['items']);
        $audit = $repository->decode($repository->query('api_logs')->first());
        self::assertSame($id, (int) $audit['connection_id']);
        self::assertSame('catalog.search', $audit['operation']);
        self::assertSame('succeeded', $audit['status']);
        self::assertSame(200, (int) $audit['http_status']);
        self::assertSame('0', $audit['provider_code']);
        self::assertNotNull($audit['finished_at']);
        $serialized = json_encode($audit, \JSON_THROW_ON_ERROR);
        foreach (['rotated-fixture-secret', 'fixture-token', 'fixture-app'] as $secret) {
            self::assertStringNotContainsString($secret, $serialized);
        }
    }

    private function assertDuplicate(\Closure $insert): void
    {
        try {
            $insert();
            self::fail('Expected a database-enforced duplicate key rejection');
        } catch (QueryException $error) {
            self::assertSame('23000', (string) $error->getCode());
        }
    }
}
