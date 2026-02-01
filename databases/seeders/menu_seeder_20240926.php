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
use App\Infrastructure\Model\Permission\Meta;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class MenuSeeder20240926 extends Seeder
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
    public function run(): void
    {
        Menu::truncate();
        if (env('DB_DRIVER') === 'odbc-sql-server') {
            Db::unprepared('SET IDENTITY_INSERT [' . Menu::getModel()->getTable() . '] ON;');
        }
        $this->create($this->data());
        if (env('DB_DRIVER') === 'odbc-sql-server') {
            Db::unprepared('SET IDENTITY_INSERT [' . Menu::getModel()->getTable() . '] OFF;');
        }
    }

    /**
     * Database seeds data.
     */
    public function data(): array
    {
        return [
            [
                'name' => 'mall:product-manage',
                'path' => '/mall/product-center',
                'meta' => new Meta([
                    'title' => '产品管理',
                    'i18n' => 'mallMenu.mall.productManage',
                    'icon' => 'ph:cube',
                    'hidden' => 0,
                    'type' => 'M',
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'mall:product',
                        'path' => '/mall/product',
                        'component' => 'mall/views/product/index',
                        'meta' => new Meta([
                            'title' => '商品管理',
                            'i18n' => 'mallMenu.mall.product',
                            'icon' => 'ph:package',
                            'hidden' => 0,
                            'type' => 'M',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'product:product:list',
                                'meta' => new Meta([
                                    'title' => '商品列表',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.productList',
                                ]),
                            ],
                            [
                                'name' => 'product:product:read',
                                'meta' => new Meta([
                                    'title' => '商品详情',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.productRead',
                                ]),
                            ],
                            [
                                'name' => 'product:product:create',
                                'meta' => new Meta([
                                    'title' => '商品新增',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.productCreate',
                                ]),
                            ],
                            [
                                'name' => 'product:product:update',
                                'meta' => new Meta([
                                    'title' => '商品编辑',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.productUpdate',
                                ]),
                            ],
                            [
                                'name' => 'product:product:delete',
                                'meta' => new Meta([
                                    'title' => '商品删除',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.productDelete',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'name' => 'mall:category',
                        'path' => '/mall/category',
                        'component' => 'mall/views/category/index',
                        'meta' => new Meta([
                            'title' => '分类管理',
                            'i18n' => 'mallMenu.mall.category',
                            'icon' => 'ph:list-bullets',
                            'hidden' => 0,
                            'type' => 'M',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'product:category:list',
                                'meta' => new Meta([
                                    'title' => '分类列表',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.categoryList',
                                ]),
                            ],
                            [
                                'name' => 'product:category:read',
                                'meta' => new Meta([
                                    'title' => '分类详情',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.categoryRead',
                                ]),
                            ],
                            [
                                'name' => 'product:category:create',
                                'meta' => new Meta([
                                    'title' => '分类新增',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.categoryCreate',
                                ]),
                            ],
                            [
                                'name' => 'product:category:update',
                                'meta' => new Meta([
                                    'title' => '分类编辑',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.categoryUpdate',
                                ]),
                            ],
                            [
                                'name' => 'product:category:delete',
                                'meta' => new Meta([
                                    'title' => '分类删除',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.categoryDelete',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'name' => 'mall:brand',
                        'path' => '/mall/brand',
                        'component' => 'mall/views/brand/index',
                        'meta' => new Meta([
                            'title' => '品牌管理',
                            'i18n' => 'mallMenu.mall.brand',
                            'icon' => 'ph:tag',
                            'hidden' => 0,
                            'type' => 'M',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'product:brand:list',
                                'meta' => new Meta([
                                    'title' => '品牌列表',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.brandList',
                                ]),
                            ],
                            [
                                'name' => 'product:brand:read',
                                'meta' => new Meta([
                                    'title' => '品牌详情',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.brandRead',
                                ]),
                            ],
                            [
                                'name' => 'product:brand:create',
                                'meta' => new Meta([
                                    'title' => '品牌新增',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.brandCreate',
                                ]),
                            ],
                            [
                                'name' => 'product:brand:update',
                                'meta' => new Meta([
                                    'title' => '品牌编辑',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.brandUpdate',
                                ]),
                            ],
                            [
                                'name' => 'product:brand:delete',
                                'meta' => new Meta([
                                    'title' => '品牌删除',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.brandDelete',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'mall:order-manage',
                'path' => '/mall/order-center',
                'meta' => new Meta([
                    'title' => '订单管理',
                    'i18n' => 'mallMenu.mall.orderManage',
                    'icon' => 'ph:clipboard-text',
                    'hidden' => 0,
                    'type' => 'M',
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'mall:order',
                        'path' => '/mall/order',
                        'component' => 'mall/views/order/index',
                        'meta' => new Meta([
                            'title' => '订单管理',
                            'i18n' => 'mallMenu.mall.order',
                            'icon' => 'ph:receipt',
                            'hidden' => 0,
                            'type' => 'M',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'order:order:list',
                                'meta' => new Meta([
                                    'title' => '订单列表',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.orderList',
                                ]),
                            ],
                            [
                                'name' => 'order:order:read',
                                'meta' => new Meta([
                                    'title' => '订单详情',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.orderRead',
                                ]),
                            ],
                            [
                                'name' => 'order:order:create',
                                'meta' => new Meta([
                                    'title' => '订单新增',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.orderCreate',
                                ]),
                            ],
                            [
                                'name' => 'order:order:update',
                                'meta' => new Meta([
                                    'title' => '订单更新',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.orderUpdate',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'mall:activity-manage',
                'path' => '/mall/activity',
                'meta' => new Meta([
                    'title' => '活动管理',
                    'i18n' => 'mallMenu.mall.activity',
                    'icon' => 'ph:sparkle',
                    'hidden' => 0,
                    'type' => 'M',
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'mall:seckill',
                        'path' => '/mall/seckill',
                        'component' => 'mall/views/seckill/index',
                        'meta' => new Meta([
                            'title' => '秒杀活动',
                            'i18n' => 'mallMenu.mall.seckill',
                            'icon' => 'ph:alarm',
                            'hidden' => 0,
                            'type' => 'M',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'seckill:activity:list',
                                'meta' => new Meta([
                                    'title' => '活动列表',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillActivityList',
                                ]),
                            ],
                            [
                                'name' => 'seckill:activity:read',
                                'meta' => new Meta([
                                    'title' => '活动详情',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillActivityRead',
                                ]),
                            ],
                            [
                                'name' => 'seckill:activity:create',
                                'meta' => new Meta([
                                    'title' => '活动新增',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillActivityCreate',
                                ]),
                            ],
                            [
                                'name' => 'seckill:activity:update',
                                'meta' => new Meta([
                                    'title' => '活动编辑',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillActivityUpdate',
                                ]),
                            ],
                            [
                                'name' => 'seckill:activity:delete',
                                'meta' => new Meta([
                                    'title' => '活动删除',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillActivityDelete',
                                ]),
                            ],
                            [
                                'name' => 'seckill:session:list',
                                'meta' => new Meta([
                                    'title' => '场次列表',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillSessionList',
                                ]),
                            ],
                            [
                                'name' => 'seckill:session:read',
                                'meta' => new Meta([
                                    'title' => '场次详情',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillSessionRead',
                                ]),
                            ],
                            [
                                'name' => 'seckill:session:create',
                                'meta' => new Meta([
                                    'title' => '场次新增',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillSessionCreate',
                                ]),
                            ],
                            [
                                'name' => 'seckill:session:update',
                                'meta' => new Meta([
                                    'title' => '场次编辑',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillSessionUpdate',
                                ]),
                            ],
                            [
                                'name' => 'seckill:session:delete',
                                'meta' => new Meta([
                                    'title' => '场次删除',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillSessionDelete',
                                ]),
                            ],
                            [
                                'name' => 'seckill:product:list',
                                'meta' => new Meta([
                                    'title' => '商品列表',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillProductList',
                                ]),
                            ],
                            [
                                'name' => 'seckill:product:read',
                                'meta' => new Meta([
                                    'title' => '商品详情',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillProductRead',
                                ]),
                            ],
                            [
                                'name' => 'seckill:product:create',
                                'meta' => new Meta([
                                    'title' => '商品新增',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillProductCreate',
                                ]),
                            ],
                            [
                                'name' => 'seckill:product:update',
                                'meta' => new Meta([
                                    'title' => '商品编辑',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillProductUpdate',
                                ]),
                            ],
                            [
                                'name' => 'seckill:product:delete',
                                'meta' => new Meta([
                                    'title' => '商品删除',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.seckillProductDelete',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'name' => 'mall:group_buy',
                        'path' => '/mall/group-buy',
                        'component' => 'mall/views/group-buy/index',
                        'meta' => new Meta([
                            'title' => '团购活动',
                            'i18n' => 'mallMenu.mall.groupBuy',
                            'icon' => 'ph:users',
                            'hidden' => 0,
                            'type' => 'M',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'promotion:group_buy:list',
                                'meta' => new Meta([
                                    'title' => '团购列表',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.groupBuyList',
                                ]),
                            ],
                            [
                                'name' => 'promotion:group_buy:read',
                                'meta' => new Meta([
                                    'title' => '团购详情',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.groupBuyRead',
                                ]),
                            ],
                            [
                                'name' => 'promotion:group_buy:create',
                                'meta' => new Meta([
                                    'title' => '团购新增',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.groupBuyCreate',
                                ]),
                            ],
                            [
                                'name' => 'promotion:group_buy:update',
                                'meta' => new Meta([
                                    'title' => '团购编辑',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.groupBuyUpdate',
                                ]),
                            ],
                            [
                                'name' => 'promotion:group_buy:delete',
                                'meta' => new Meta([
                                    'title' => '团购删除',
                                    'type' => 'B',
                                    'i18n' => 'mallMenu.mall.groupBuyDelete',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'mall:seckill:session',
                'path' => '/mall/seckill/sessions',
                'component' => 'mall/views/seckill/sessions/index',
                'meta' => new Meta([
                    'title' => '场次管理',
                    'i18n' => 'mallMenu.mall.seckillSession',
                    'icon' => 'ph:clock',
                    'hidden' => 1,
                    'type' => 'M',
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 0,
                    'affix' => 0,
                ]),
            ],
            [
                'name' => 'mall:seckill:product',
                'path' => '/mall/seckill/products',
                'component' => 'mall/views/seckill/products/index',
                'meta' => new Meta([
                    'title' => '商品配置',
                    'i18n' => 'mallMenu.mall.seckillProduct',
                    'icon' => 'ph:package',
                    'hidden' => 1,
                    'type' => 'M',
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 0,
                    'affix' => 0,
                ]),
            ],
            [
                'name' => 'mall:config',
                'path' => '/mall/config',
                'meta' => new Meta([
                    'title' => '商城配置',
                    'i18n' => 'mallMenu.mall.config',
                    'icon' => 'ph:storefront',
                    'hidden' => 0,
                    'type' => 'M',
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'mall:config:product',
                        'path' => '/mall/config/product',
                        'component' => 'mall/views/config/product',
                        'meta' => new Meta([
                            'title' => '商品与库存',
                            'i18n' => 'mallMenu.mall.productSetting',
                            'icon' => 'ph:cube',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'auth' => ['system:setting:list'],
                        ]),
                    ],
                    [
                        'name' => 'mall:config:order',
                        'path' => '/mall/config/order',
                        'component' => 'mall/views/config/order',
                        'meta' => new Meta([
                            'title' => '订单与售后',
                            'i18n' => 'mallMenu.mall.orderSetting',
                            'icon' => 'ph:receipt',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'auth' => ['system:setting:list'],
                        ]),
                    ],
                    [
                        'name' => 'mall:config:payment',
                        'path' => '/mall/config/payment',
                        'component' => 'mall/views/config/payment',
                        'meta' => new Meta([
                            'title' => '支付与结算',
                            'i18n' => 'mallMenu.mall.paymentSetting',
                            'icon' => 'ph:credit-card',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'auth' => ['system:setting:list'],
                        ]),
                    ],
                    [
                        'name' => 'mall:config:shipping',
                        'path' => '/mall/config/shipping',
                        'component' => 'mall/views/config/shipping',
                        'meta' => new Meta([
                            'title' => '配送与物流',
                            'i18n' => 'mallMenu.mall.shippingSetting',
                            'icon' => 'ph:truck',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'auth' => ['system:setting:list'],
                        ]),
                    ],
                    [
                        'name' => 'mall:config:member',
                        'path' => '/mall/config/member',
                        'component' => 'mall/views/config/member',
                        'meta' => new Meta([
                            'title' => '会员与营销',
                            'i18n' => 'mallMenu.mall.memberSetting',
                            'icon' => 'ph:users-three',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'auth' => ['system:setting:list'],
                        ]),
                    ],
                    [
                        'name' => 'mall:config:content',
                        'path' => '/mall/config/content',
                        'component' => 'mall/views/config/content',
                        'meta' => new Meta([
                            'title' => '内容与合规',
                            'i18n' => 'mallMenu.mall.contentSetting',
                            'icon' => 'ph:file-text',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'auth' => ['system:setting:list'],
                        ]),
                    ],
                ],
            ],
            [
                'name' => 'system:config',
                'path' => '/system/config',
                'meta' => new Meta([
                    'title' => '系统配置',
                    'i18n' => 'systemMenu.system.config',
                    'icon' => 'ph:wrench',
                    'hidden' => 0,
                    'type' => 'M',
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'system:config:basic',
                        'path' => '/system/config/basic',
                        'component' => 'system/views/config/basic',
                        'meta' => new Meta([
                            'title' => '基础信息',
                            'i18n' => 'systemMenu.system.basic',
                            'icon' => 'ph:sliders',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'auth' => ['system:setting:list'],
                        ]),
                    ],
                    [
                        'name' => 'system:config:integration',
                        'path' => '/system/config/integration',
                        'component' => 'system/views/config/integration',
                        'meta' => new Meta([
                            'title' => '系统集成',
                            'i18n' => 'systemMenu.system.integration',
                            'icon' => 'ph:plug',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'auth' => ['system:setting:list'],
                        ]),
                    ],
                    [
                        'name' => 'system:setting:list',
                        'meta' => new Meta([
                            'title' => '配置查看',
                            'type' => 'B',
                            'i18n' => 'systemMenu.system.settingList',
                        ]),
                    ],
                    [
                        'name' => 'system:setting:update',
                        'meta' => new Meta([
                            'title' => '配置更新',
                            'type' => 'B',
                            'i18n' => 'systemMenu.system.settingUpdate',
                        ]),
                    ],
                ],
            ],
            [
                'name' => 'member',
                'path' => '/member',
                'meta' => new Meta([
                    'title' => '会员中心',
                    'i18n' => 'memberMenu.member.index',
                    'icon' => 'ph:user-circle',
                    'hidden' => 0,
                    'type' => 'M',
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'member:overview',
                        'path' => '/member/overview',
                        'component' => 'member/views/overview/index',
                        'meta' => new Meta([
                            'title' => '会员概览',
                            'i18n' => 'memberMenu.member.overview',
                            'icon' => 'ph:chart-line',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'cache' => 1,
                        ]),
                    ],
                    [
                        'name' => 'member:list',
                        'path' => '/member/list',
                        'component' => 'member/views/list/index',
                        'meta' => new Meta([
                            'title' => '会员列表',
                            'i18n' => 'memberMenu.member.list',
                            'icon' => 'ph:list-bullets',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'cache' => 1,
                        ]),
                        'children' => [
                            [
                                'name' => 'member:member:list',
                                'meta' => new Meta([
                                    'title' => '会员列表',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.list',
                                ]),
                            ],
                            [
                                'name' => 'member:member:read',
                                'meta' => new Meta([
                                    'title' => '会员详情',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.detail',
                                ]),
                            ],
                            [
                                'name' => 'member:member:create',
                                'meta' => new Meta([
                                    'title' => '会员新增',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.create',
                                ]),
                            ],
                            [
                                'name' => 'member:member:update',
                                'meta' => new Meta([
                                    'title' => '会员编辑',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.update',
                                ]),
                            ],
                            [
                                'name' => 'member:member:tag',
                                'meta' => new Meta([
                                    'title' => '会员标签',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.tag',
                                ]),
                            ],
                            [
                                'name' => 'member:tag:list',
                                'meta' => new Meta([
                                    'title' => '标签列表',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.tagList',
                                ]),
                            ],
                            [
                                'name' => 'member:tag:create',
                                'meta' => new Meta([
                                    'title' => '标签新增',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.tagCreate',
                                ]),
                            ],
                            [
                                'name' => 'member:tag:update',
                                'meta' => new Meta([
                                    'title' => '标签编辑',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.tagUpdate',
                                ]),
                            ],
                            [
                                'name' => 'member:tag:delete',
                                'meta' => new Meta([
                                    'title' => '标签删除',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.tagDelete',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'name' => 'member:level',
                        'path' => '/member/levels',
                        'component' => 'member/views/level/index',
                        'meta' => new Meta([
                            'title' => '等级配置',
                            'i18n' => 'memberMenu.member.level',
                            'icon' => 'ph:medal',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'cache' => 1,
                        ]),
                        'children' => [
                            [
                                'name' => 'member:level:list',
                                'meta' => new Meta([
                                    'title' => '等级列表',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.levelList',
                                ]),
                            ],
                            [
                                'name' => 'member:level:read',
                                'meta' => new Meta([
                                    'title' => '等级详情',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.levelRead',
                                ]),
                            ],
                            [
                                'name' => 'member:level:create',
                                'meta' => new Meta([
                                    'title' => '等级新增',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.levelCreate',
                                ]),
                            ],
                            [
                                'name' => 'member:level:update',
                                'meta' => new Meta([
                                    'title' => '等级编辑',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.levelUpdate',
                                ]),
                            ],
                            [
                                'name' => 'member:level:delete',
                                'meta' => new Meta([
                                    'title' => '等级删除',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.levelDelete',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'name' => 'member:wallet',
                        'path' => '/member/points',
                        'component' => 'member/views/points/index',
                        'meta' => new Meta([
                            'title' => '会员钱包',
                            'i18n' => 'memberMenu.member.wallet',
                            'icon' => 'ph:wallet',
                            'type' => 'M',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'cache' => 1,
                        ]),
                        'children' => [
                            [
                                'name' => 'member:wallet:list',
                                'meta' => new Meta([
                                    'title' => '钱包记录',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.walletList',
                                ]),
                            ],
                            [
                                'name' => 'member:wallet:adjust',
                                'meta' => new Meta([
                                    'title' => '钱包调整',
                                    'type' => 'B',
                                    'i18n' => 'memberMenu.member.walletAdjust',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'permission',
                'path' => '/permission',
                'meta' => new Meta([
                    'title' => '权限管理',
                    'i18n' => 'baseMenu.permission.index',
                    'icon' => 'ri:git-repository-private-line',
                    'type' => 'M',
                    'hidden' => 0,
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'permission:user',
                        'path' => '/permission/user',
                        'component' => 'base/views/permission/user/index',
                        'meta' => new Meta([
                            'type' => 'M',
                            'title' => '用户管理',
                            'i18n' => 'baseMenu.permission.user',
                            'icon' => 'material-symbols:manage-accounts-outline',
                            'hidden' => 0,
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'permission:user:index',
                                'meta' => new Meta([
                                    'title' => '用户列表',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.userList',
                                ]),
                            ],
                            [
                                'name' => 'permission:user:save',
                                'meta' => new Meta([
                                    'title' => '用户保存',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.userSave',
                                ]),
                            ],
                            [
                                'name' => 'permission:user:update',
                                'meta' => new Meta([
                                    'title' => '用户更新',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.userUpdate',
                                ]),
                            ],
                            [
                                'name' => 'permission:user:delete',
                                'meta' => new Meta([
                                    'title' => '用户删除',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.userDelete',
                                ]),
                            ],
                            [
                                'name' => 'permission:user:password',
                                'meta' => new Meta([
                                    'title' => '用户初始化密码',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.userPassword',
                                ]),
                            ],
                            [
                                'name' => 'user:get:roles',
                                'meta' => new Meta([
                                    'title' => '获取用户角色',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.getUserRole',
                                ]),
                            ],
                            [
                                'name' => 'user:set:roles',
                                'meta' => new Meta([
                                    'title' => '用户角色赋予',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.setUserRole',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'name' => 'permission:menu',
                        'path' => '/permission/menu',
                        'component' => 'base/views/permission/menu/index',
                        'meta' => new Meta([
                            'title' => '菜单管理',
                            'i18n' => 'baseMenu.permission.menu',
                            'icon' => 'ph:list-bold',
                            'hidden' => 0,
                            'type' => 'M',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'permission:menu:index',
                                'meta' => new Meta([
                                    'title' => '菜单列表',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.menuList',
                                ]),
                            ],
                            [
                                'name' => 'permission:menu:create',
                                'meta' => new Meta([
                                    'title' => '菜单保存',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.menuSave',
                                ]),
                            ],
                            [
                                'name' => 'permission:menu:save',
                                'meta' => new Meta([
                                    'title' => '菜单更新',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.menuUpdate',
                                ]),
                            ],
                            [
                                'name' => 'permission:menu:delete',
                                'meta' => new Meta([
                                    'title' => '菜单删除',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.menuDelete',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'name' => 'permission:role',
                        'path' => '/permission/role',
                        'component' => 'base/views/permission/role/index',
                        'meta' => new Meta([
                            'title' => '角色管理',
                            'i18n' => 'baseMenu.permission.role',
                            'icon' => 'material-symbols:supervisor-account-outline-rounded',
                            'hidden' => 0,
                            'type' => 'M',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'permission:role:index',
                                'meta' => new Meta([
                                    'title' => '角色列表',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.roleList',
                                ]),
                            ],
                            [
                                'name' => 'permission:role:save',
                                'meta' => new Meta([
                                    'title' => '角色保存',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.roleSave',
                                ]),
                            ],
                            [
                                'name' => 'permission:role:update',
                                'meta' => new Meta([
                                    'title' => '角色更新',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.roleUpdate',
                                ]),
                            ],
                            [
                                'name' => 'permission:role:delete',
                                'meta' => new Meta([
                                    'title' => '角色删除',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.roleDelete',
                                ]),
                            ],
                            [
                                'name' => 'permission:get:role',
                                'meta' => new Meta([
                                    'title' => '获取角色权限',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.getRolePermission',
                                ]),
                            ],
                            [
                                'name' => 'permission:set:role',
                                'meta' => new Meta([
                                    'title' => '赋予角色权限',
                                    'type' => 'B',
                                    'i18n' => 'baseMenu.permission.setRolePermission',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'log',
                'path' => '/log',
                'meta' => new Meta([
                    'title' => '日志管理',
                    'i18n' => 'baseMenu.log.index',
                    'icon' => 'ph:instagram-logo',
                    'type' => 'M',
                    'hidden' => 0,
                    'componentPath' => 'modules/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => 1,
                    'copyright' => 1,
                    'cache' => 1,
                    'affix' => 0,
                ]),
                'children' => [
                    [
                        'name' => 'log:userLogin',
                        'path' => '/log/userLoginLog',
                        'component' => 'base/views/log/userLogin',
                        'meta' => new Meta([
                            'title' => '用户登录日志管理',
                            'type' => 'M',
                            'hidden' => 0,
                            'icon' => 'ph:user-list',
                            'i18n' => 'baseMenu.log.userLoginLog',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'log:userLogin:list',
                                'path' => '/log/userLoginLog',
                                'meta' => new Meta([
                                    'title' => '用户登录日志列表',
                                    'i18n' => 'baseMenu.log.userLoginLogList',
                                    'type' => 'B',
                                ]),
                            ],
                            [
                                'name' => 'log:userLogin:delete',
                                'meta' => new Meta([
                                    'title' => '删除用户登录日志',
                                    'i18n' => 'baseMenu.log.userLoginLogDelete',
                                    'type' => 'B',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'name' => 'log:userOperation',
                        'path' => '/log/operationLog',
                        'component' => 'base/views/log/userOperation',
                        'meta' => new Meta([
                            'title' => '操作日志管理',
                            'type' => 'M',
                            'hidden' => 0,
                            'icon' => 'ph:list-magnifying-glass',
                            'i18n' => 'baseMenu.log.operationLog',
                            'componentPath' => 'modules/',
                            'componentSuffix' => '.vue',
                            'breadcrumbEnable' => 1,
                            'copyright' => 1,
                            'cache' => 1,
                            'affix' => 0,
                        ]),
                        'children' => [
                            [
                                'name' => 'log:userOperation:list',
                                'meta' => new Meta([
                                    'title' => '用户操作日志列表',
                                    'i18n' => 'baseMenu.log.userOperationLog',
                                    'type' => 'B',
                                ]),
                            ],
                            [
                                'name' => 'log:userOperation:delete',
                                'meta' => new Meta([
                                    'title' => '删除用户操作日志',
                                    'i18n' => 'baseMenu.log.userOperationLogDelete',
                                    'type' => 'B',
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
            if (isset($v['children']) && count($v['children'])) {
                $this->create($v['children'], $menu->id);
            }
        }
    }
}
