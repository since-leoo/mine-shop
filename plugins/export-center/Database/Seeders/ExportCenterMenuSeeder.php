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

namespace Plugin\ExportCenter\Database\Seeders;

use App\Infrastructure\Model\Permission\Menu;
use App\Infrastructure\Model\Permission\Meta;
use Hyperf\Database\Seeders\Seeder;

class ExportCenterMenuSeeder extends Seeder
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

    public function run(): void
    {
        $this->create($this->data());
    }

    public function data(): array
    {
        return [
            [
                'name' => 'export:center',
                'path' => '/export',
                'redirect' => '/export/task',
                'meta' => new Meta([
                    'title' => '导出中心',
                    'i18n' => 'exportMenu.exportCenter',
                    'icon' => 'mdi:download-box',
                    'type' => 'M',
                    'hidden' => 0,
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'export:task',
                        'path' => '/export/task',
                        'component' => 'since/export-center/views/task/index',
                        'meta' => new Meta([
                            'title' => '下载中心',
                            'i18n' => 'exportMenu.downloadCenter',
                            'icon' => 'mdi:file-download',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'plugins/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'export:task:list',
                                'meta' => new Meta([
                                    'title' => '导出任务列表',
                                    'type' => 'B',
                                    'i18n' => 'exportMenu.exportTaskList',
                                ]),
                            ],
                            [
                                'name' => 'export:task:create',
                                'meta' => new Meta([
                                    'title' => '创建导出任务',
                                    'type' => 'B',
                                    'i18n' => 'exportMenu.exportTaskCreate',
                                ]),
                            ],
                            [
                                'name' => 'export:task:download',
                                'meta' => new Meta([
                                    'title' => '下载导出文件',
                                    'type' => 'B',
                                    'i18n' => 'exportMenu.exportTaskDownload',
                                ]),
                            ],
                            [
                                'name' => 'export:task:delete',
                                'meta' => new Meta([
                                    'title' => '删除导出记录',
                                    'type' => 'B',
                                    'i18n' => 'exportMenu.exportTaskDelete',
                                ]),
                            ],
                        ],
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
            $_v['parent_id'] = $parent_id;
            $menu = Menu::create(array_merge(self::BASE_DATA, $_v));
            if (isset($v['children']) && \count($v['children'])) {
                $this->create($v['children'], $menu->id);
            }
        }
    }
}
