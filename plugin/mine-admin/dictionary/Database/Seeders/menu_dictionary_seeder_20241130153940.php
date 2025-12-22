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
use App\Model\Permission\Menu;
use App\Model\Permission\Meta;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class MenuDictionarySeeder20241130153940 extends Seeder
{
    public const BASE_DATA = [
        'name' => '',
        'path' => '',
        'component' => '',
        'redirect' => '',
        'created_by' => 0,
        'updated_by' => 0,
        'remark' => '',
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (env('DB_DRIVER') === 'odbc-sql-server') {
            Db::unprepared('SET IDENTITY_INSERT [' . Menu::getModel()->getTable() . '] ON;');
        }
        $this->create($this->data());
        if (env('DB_DRIVER') === 'odbc-sql-server') {
            Db::unprepared('SET IDENTITY_INSERT [' . Menu::getModel()->getTable() . '] OFF;');
        }
    }

    public function data(): array
    {
        return [
            [
                'parent_id' => 29,
                'name' => 'dataCenter:dictionary',
                'path' => '/dataCenter/dictionary',
                'component' => 'mine-admin/dictionary/views/index',
                'meta' => new Meta([
                    'title' => '数据字典',
                    'type' => 'M',
                    'hidden' => 0,
                    'icon' => 'mdi:book-open-variant-outline',
                    'i18n' => 'dictionary.menu.dictionary',
                    'componentPath' => 'plugins/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'dataCenter:dictionary:typeList',
                        'meta' => new Meta([
                            'title' => '字典分类列表',
                            'i18n' => 'dictionary.menu.typeList',
                            'type' => 'B',
                        ]),
                    ],
                    [
                        'name' => 'dataCenter:dictionary:typeSave',
                        'meta' => new Meta([
                            'title' => '字典分类新增',
                            'i18n' => 'dictionary.menu.typeSave',
                            'type' => 'B',
                        ]),
                    ],
                    [
                        'name' => 'dataCenter:dictionary:typeUpdate',
                        'meta' => new Meta([
                            'title' => '字典分类更新',
                            'i18n' => 'dictionary.menu.typeUpdate',
                            'type' => 'B',
                        ]),
                    ],
                    [
                        'name' => 'dataCenter:dictionary:typeDelete',
                        'meta' => new Meta([
                            'title' => '字典分类删除',
                            'i18n' => 'dictionary.menu.typeDelete',
                            'type' => 'B',
                        ]),
                    ],
                    [
                        'name' => 'dataCenter:dictionary:list',
                        'meta' => new Meta([
                            'title' => '字典列表',
                            'i18n' => 'dictionary.menu.list',
                            'type' => 'B',
                        ]),
                    ],
                    [
                        'name' => 'dataCenter:dictionary:save',
                        'meta' => new Meta([
                            'title' => '字典新增',
                            'i18n' => 'dictionary.menu.save',
                            'type' => 'B',
                        ]),
                    ],
                    [
                        'name' => 'dataCenter:dictionary:update',
                        'meta' => new Meta([
                            'title' => '字典更新',
                            'i18n' => 'dictionary.menu.update',
                            'type' => 'B',
                        ]),
                    ],
                    [
                        'name' => 'dataCenter:dictionary:delete',
                        'meta' => new Meta([
                            'title' => '字典删除',
                            'i18n' => 'dictionary.menu.delete',
                            'type' => 'B',
                        ]),
                    ],
                ],
            ],
        ];
    }

    public function create(array $data, int $parent_id = 0): void
    {
        foreach ($data as $v) {
            $_v = $v;
            if (isset($v['children'])) {
                unset($_v['children']);
            }
            if(empty($_v['parent_id'])) {
                $_v['parent_id'] = $parent_id;
            }
            $menu = Menu::create(array_merge(self::BASE_DATA, $_v));
            if (isset($v['children']) && count($v['children'])) {
                $this->create($v['children'], $menu->id);
            }
        }
    }
}
