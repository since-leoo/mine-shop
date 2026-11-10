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

use App\Application\Commad\Plugin\AppPluginPermissionRegistrar;
use App\Domain\Infrastructure\Plugin\Contract\PluginPermissionRegistrarInterface;
use App\Domain\Infrastructure\Plugin\Repository\PluginPermissionRepository;
use App\Domain\Infrastructure\Plugin\Service\DomainPluginPermissionService;
use App\Infrastructure\Service\Trade\HyperfTransactionManager;
use HyperfTests\Support\DatabaseTestCase;
use Mine\Support\Listener\RegisterBlueprintListener;

/**
 * @internal
 * @coversNothing
 */
final class CorePermissionTest extends DatabaseTestCase
{
    public function testRealSupplyChainSeederRegistersControllerPermissionCodesThroughPublicPort(): void
    {
        (new RegisterBlueprintListener())->process(new \stdClass());
        require_once BASE_PATH . '/migrations/2021_04_18_215320_create_menu_table.php';
        (new \CreateMenuTable())->up();
        $service = new AppPluginPermissionRegistrar(new DomainPluginPermissionService(new PluginPermissionRepository()), new HyperfTransactionManager());
        $this->container->set(PluginPermissionRegistrarInterface::class, $service);
        require_once BASE_PATH . '/plugins/supply-chain/Database/Seeders/supply_chain_permission_seeder_20261016.php';
        (new \supply_chain_permission_seeder_20261016())->run();
        (new \supply_chain_permission_seeder_20261016())->run();
        preg_match_all("/Permission\\(code: '([^']+)'\\)/", file_get_contents(BASE_PATH . '/plugins/supply-chain/src/Interface/Admin/SupplyChainController.php'), $matches);
        $codes = array_values(array_unique($matches[1]));
        self::assertNotEmpty($codes);
        foreach ($codes as $code) {
            self::assertSame(1, $this->connection->table('menu')->where('name', $code)->count(), $code);
        }
        self::assertSame(17, $this->connection->table('menu')->count());
    }

    public function testPermissionRegistrationIsIdempotentAndPreservesAdminChoices(): void
    {
        (new RegisterBlueprintListener())->process(new \stdClass());
        require_once BASE_PATH . '/migrations/2021_04_18_215320_create_menu_table.php';
        (new \CreateMenuTable())->up();
        $service = new AppPluginPermissionRegistrar(new DomainPluginPermissionService(new PluginPermissionRepository()), new HyperfTransactionManager());
        $service->register('supply-chain', ['supply-chain:task:list' => '任务列表', 'supply-chain:task:retry' => '重试任务']);
        $id = (int) $this->connection->table('menu')->where('name', 'supply-chain:task:list')->value('id');
        $this->connection->table('menu')->where('id', $id)->update(['status' => 2, 'remark' => '管理员配置']);
        $service->register('supply-chain', ['supply-chain:task:list' => '任务记录']);
        self::assertSame(3, $this->connection->table('menu')->count());
        $row = $this->connection->table('menu')->where('id', $id)->first();
        self::assertSame(2, (int) $row->status);
        self::assertSame('管理员配置', $row->remark);
        self::assertSame('任务记录', json_decode($row->meta, true)['title']);
        self::assertTrue(json_decode($this->connection->table('menu')->where('name', 'plugin:supply-chain')->value('meta'), true)['hidden']);
        $this->expectException(\InvalidArgumentException::class);
        $service->register('supply-chain', ['other:task:list' => '越权命名空间']);
    }
}
