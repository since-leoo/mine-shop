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

use App\Infrastructure\Plugin\PluginUninstallGuard;
use Hyperf\Database\Schema\Blueprint;
use HyperfTests\Support\DatabaseTestCase;

/**
 * @internal
 * @coversNothing
 */
final class CoreLifecycleTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once BASE_PATH . '/migrations/2024_01_01_000008_create_mall_orders_table.php';
        (new \CreateMallOrdersTable())->up();
        $this->connection->getSchemaBuilder()->create('supply_product_links', static function (Blueprint $table): void { $table->id(); });
    }

    public function testDeclaredHistoryGuardRemainsActiveForDisabledPlugin(): void
    {
        $definition = json_decode(file_get_contents(BASE_PATH . '/plugins/supply-chain/plugin.json'), true, 512, \JSON_THROW_ON_ERROR);
        $definition['enabled'] = false;
        (new PluginUninstallGuard())->assertCanUninstall($definition);
        $this->connection->table('supply_product_links')->insert(['id' => 1]);
        $this->expectException(\DomainException::class);
        (new PluginUninstallGuard())->assertCanUninstall($definition);
    }

    public function testSubmissionGuardRecognizesActualFrozenItemPayloadIncludingMixedOrders(): void
    {
        $definition = ['uninstall_submission_guards' => [['table' => 'order_submissions', 'payload_key' => 'fulfillment']]];
        $this->connection->table('order_submissions')->insert(['trade_no' => 'PENDING', 'member_id' => 1, 'status' => 'reserved', 'payload' => json_encode(['items' => [
            ['sku_id' => 1, 'fulfillment_provider' => 'merchant', 'fulfillment_snapshot' => []],
        ]])]);
        (new PluginUninstallGuard())->assertCanUninstall($definition);
        $this->connection->table('order_submissions')->update(['payload' => json_encode(['items' => [
            ['sku_id' => 1, 'fulfillment_provider' => 'merchant', 'fulfillment_snapshot' => []],
            ['sku_id' => 2, 'fulfillment_provider' => 'supply_chain', 'fulfillment_snapshot' => ['connection_id' => 1]],
        ]])]);
        try {
            (new PluginUninstallGuard())->assertCanUninstall($definition);
            self::fail('pending frozen supplier order must block uninstall');
        } catch (\DomainException $error) {
            self::assertStringContainsString('待处理订单', $error->getMessage());
        }
        $this->connection->table('order_submissions')->update(['status' => 'created']);
        (new PluginUninstallGuard())->assertCanUninstall($definition);
        self::assertTrue(true);
    }

    public function testForcedUninstallUsesRealHostAspectEvenWhenPluginDisabled(): void
    {
        $this->connection->table('supply_product_links')->insert(['id' => 1]);
        $process = proc_open([\PHP_BINARY, __DIR__ . '/core-lifecycle-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName()], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr . $stdout);
        $result = json_decode(trim($stdout), true, 512, \JSON_THROW_ON_ERROR);
        self::assertTrue($result['proxied']);
        self::assertSame(\DomainException::class, $result['exception'] ?? null);
        self::assertTrue($result['lock_unchanged']);
        self::assertArrayNotHasKey('returned', $result);
    }
}
