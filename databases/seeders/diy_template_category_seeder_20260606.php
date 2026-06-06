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

use App\Infrastructure\Model\Content\DiyTemplateCategory;
use Hyperf\Database\Seeders\Seeder;

class DiyTemplateCategorySeeder20260606 extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $category) {
            DiyTemplateCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }

    private function categories(): array
    {
        return [
            [
                'name' => '首页模板',
                'code' => 'home',
                'sort' => 100,
                'is_enabled' => true,
            ],
            [
                'name' => '营销活动模板',
                'code' => 'promotion',
                'sort' => 90,
                'is_enabled' => true,
            ],
            [
                'name' => '会员中心模板',
                'code' => 'member',
                'sort' => 80,
                'is_enabled' => true,
            ],
            [
                'name' => '分类页模板',
                'code' => 'category',
                'sort' => 70,
                'is_enabled' => true,
            ],
        ];
    }
}
