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

use App\Infrastructure\Model\Permission\Menu;
use Hyperf\Database\Seeders\Seeder;

class DiyPageMenu20260606 extends Seeder
{
    public function run(): void
    {
        $parent = Menu::query()->where('name', 'mall:config')->first()
            ?: Menu::query()->where('name', 'mall:product-manage')->first();

        if (! $parent instanceof Menu) {
            return;
        }

        /** @var Menu $menu */
        $menu = Menu::query()->updateOrCreate(
            ['name' => 'mall:diy:page'],
            [
                'parent_id' => $parent->id,
                'path' => '/mall/diy/page',
                'component' => 'mall/views/diy/page/index',
                'redirect' => '',
                'status' => 1,
                'sort' => 90,
                'meta' => [
                    'title' => 'DIY页面',
                    'icon' => 'ph:paint-brush',
                    'type' => 'M',
                    'hidden' => 0,
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ],
            ]
        );

        Menu::query()->updateOrCreate(
            ['name' => 'mall:diy:editor'],
            [
                'parent_id' => $menu->id,
                'path' => '/mall/diy/editor',
                'component' => 'mall/views/diy/editor/index',
                'redirect' => '',
                'status' => 1,
                'sort' => 91,
                'meta' => [
                    'title' => 'DIY页面装修',
                    'type' => 'M',
                    'hidden' => 1,
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 0,
                    'affix' => 0,
                ],
            ]
        );

        $permissions = [
            'mall:diy:read' => 'DIY页面查看',
            'mall:diy:create' => 'DIY页面创建',
            'mall:diy:update' => 'DIY页面编辑',
            'mall:diy:publish' => 'DIY页面发布',
            'mall:diy:enable' => 'DIY页面启用',
        ];

        foreach ($permissions as $name => $title) {
            Menu::query()->updateOrCreate(
                ['name' => $name],
                [
                    'parent_id' => $menu->id,
                    'path' => '/mall/diy/page',
                    'component' => '',
                    'redirect' => '',
                    'status' => 1,
                    'sort' => 100,
                    'meta' => [
                        'title' => $title,
                        'type' => 'B',
                        'hidden' => 1,
                        'cache' => 1,
                        'affix' => 0,
                    ],
                ]
            );
        }
    }
}
