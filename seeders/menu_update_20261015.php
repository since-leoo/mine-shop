<?php

declare(strict_types=1);

use App\Infrastructure\Model\Permission\Menu;
use App\Infrastructure\Model\Permission\Meta;
use Hyperf\Database\Seeders\Seeder;

class MenuUpdate20261015 extends Seeder
{
    public function run(): void
    {
        $parent = Menu::query()->where('name', 'mall:order')->first();
        if ($parent === null) {
            throw new \RuntimeException('订单管理菜单不存在，请先初始化商城菜单');
        }

        // 独立认款权限不自动授予已有角色；重复执行保留管理员已有的配置。
        Menu::query()->firstOrCreate(['name' => 'order:order:manual-pay'], [
            'parent_id' => $parent->id,
            'meta' => new Meta([
                'title' => '人工认款',
                'type' => 'B',
                'i18n' => 'mallMenu.mall.orderManualPay',
            ]),
            'path' => '',
            'component' => '',
            'redirect' => '',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '线下收款后确认订单已支付',
        ]);
    }
}
