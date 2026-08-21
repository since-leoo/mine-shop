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

namespace Plugin\CustomerService;

use App\Infrastructure\Model\Permission\Menu;
use App\Infrastructure\Model\Permission\Meta;
use App\Infrastructure\Model\Permission\Role;
use Hyperf\DbConnection\Db;
use SinceLeoo\Plugin\Contract\AbstractPlugin;

final class Plugin extends AbstractPlugin
{
    public function install(): void
    {
        $webSource = \dirname(__DIR__) . '/web';
        $webTarget = BASE_PATH . '/web/src/plugins/since/customer-service';
        if (is_dir($webSource)) {
            $this->copyDirectory($webSource, $webTarget);
        }
        $this->installMenus();
    }

    public function uninstall(): void
    {
        $webTarget = BASE_PATH . '/web/src/plugins/since/customer-service';
        if (is_dir($webTarget)) {
            $this->deleteDirectory($webTarget);
        }
        $this->uninstallMenus();
    }

    private function installMenus(): void
    {
        Db::transaction(function (): void {
            $root = $this->menu('customer-service:manage', 0, '/customer-service', '', '客服中心', 'ant-design:customer-service-outlined', 90);
            $workbench = $this->menu('customer-service:workbench', (int) $root->id, '/customer-service/workbench', 'since/customer-service/views/workbench/index', '客服工作台', 'ant-design:message-outlined', 10);
            $faq = $this->menu('customer-service:faq', (int) $root->id, '/customer-service/faq', 'since/customer-service/views/faq/index', '常见问题', 'ant-design:question-circle-outlined', 20);

            $menus = [$root, $workbench, $faq];
            foreach (['read', 'accept', 'transfer', 'close'] as $index => $action) {
                $menus[] = $this->button('customer-service:conversation:' . $action, (int) $workbench->id, '会话' . ['查看', '接待', '转接', '关闭'][$index]);
            }
            foreach (['list', 'create', 'update', 'delete'] as $index => $action) {
                $menus[] = $this->button('customer-service:faq:' . $action, (int) $faq->id, '常见问题' . ['查看', '新增', '编辑', '删除'][$index]);
            }

            $superAdmin = Role::query()->where('code', 'SuperAdmin')->first();
            if ($superAdmin !== null) {
                $superAdmin->menus()->syncWithoutDetaching(array_map(static fn (Menu $menu) => $menu->id, $menus));
            }
        });
    }

    private function uninstallMenus(): void
    {
        Db::transaction(function (): void {
            $menus = Menu::query()->where('name', 'like', 'customer-service:%')->orderByDesc('id')->get();
            foreach ($menus as $menu) {
                $menu->roles()->detach();
                $menu->delete();
            }
        });
    }

    private function menu(string $name, int $parentId, string $path, string $component, string $title, string $icon, int $sort): Menu
    {
        return Menu::query()->firstOrCreate(['name' => $name], [
            'parent_id' => $parentId, 'path' => $path, 'component' => $component, 'redirect' => '', 'status' => 1, 'sort' => $sort,
            'created_by' => 0, 'updated_by' => 0, 'remark' => 'since/customer-service 插件菜单',
            'meta' => new Meta(['title' => $title, 'icon' => $icon, 'hidden' => false, 'type' => 'M', 'componentPath' => 'plugins/', 'componentSuffix' => '.vue', 'breadcrumbEnable' => true, 'copyright' => true, 'cache' => true, 'affix' => false]),
        ]);
    }

    private function button(string $name, int $parentId, string $title): Menu
    {
        return Menu::query()->firstOrCreate(['name' => $name], [
            'parent_id' => $parentId, 'path' => '', 'component' => '', 'redirect' => '', 'status' => 1, 'sort' => 0,
            'created_by' => 0, 'updated_by' => 0, 'remark' => 'since/customer-service 插件权限',
            'meta' => new Meta(['title' => $title, 'type' => 'B']),
        ]);
    }

    private function copyDirectory(string $source, string $target): void
    {
        if (! is_dir($target)) {
            mkdir($target, 0o755, true);
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $targetPath = $target . \DIRECTORY_SEPARATOR . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0o755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    private function deleteDirectory(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
