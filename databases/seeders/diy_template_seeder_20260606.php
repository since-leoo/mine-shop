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

use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use App\Infrastructure\Model\Content\DiyTemplate;
use App\Infrastructure\Model\Content\DiyTemplateCategory;
use Hyperf\Database\Seeders\Seeder;

class DiyTemplateSeeder20260606 extends Seeder
{
    public function run(): void
    {
        $categories = DiyTemplateCategory::query()
            ->get()
            ->keyBy('code');

        foreach ($this->templates() as $template) {
            $category = $categories->get($template['category_code']);
            if (! $category instanceof DiyTemplateCategory) {
                continue;
            }

            $schema = DiyPageSchemaVo::fromArray($template['schema'], $template['page_key'])->toArray();

            DiyTemplate::query()->updateOrCreate(
                [
                    'name' => $template['name'],
                    'page_key' => $template['page_key'],
                    'page_type' => $template['page_type'],
                ],
                [
                    'category_id' => $category->id,
                    'cover' => $template['cover'],
                    'description' => $template['description'],
                    'schema' => $schema,
                    'sort' => $template['sort'],
                    'is_enabled' => true,
                ]
            );
        }
    }

    private function templates(): array
    {
        return [
            [
                'category_code' => 'home',
                'name' => '默认商城首页',
                'page_key' => 'home',
                'page_type' => DiyPageStatus::TYPE_ALL,
                'cover' => null,
                'description' => '包含轮播图、金刚区、公告、图片广告和商品组的默认首页模板',
                'sort' => 100,
                'schema' => $this->homeSchema(),
            ],
            [
                'category_code' => 'category',
                'name' => '默认分类首页',
                'page_key' => 'category_home',
                'page_type' => DiyPageStatus::TYPE_ALL,
                'cover' => null,
                'description' => '适合分类页入口的搜索、分类导航和推荐商品模板',
                'sort' => 90,
                'schema' => $this->categorySchema(),
            ],
            [
                'category_code' => 'member',
                'name' => '默认会员中心',
                'page_key' => 'member_home',
                'page_type' => DiyPageStatus::TYPE_ALL,
                'cover' => null,
                'description' => '包含店铺信息、优惠券和会员推荐商品的会员中心模板',
                'sort' => 80,
                'schema' => $this->memberSchema(),
            ],
            [
                'category_code' => 'promotion',
                'name' => '默认活动专题',
                'page_key' => 'promotion_topic',
                'page_type' => DiyPageStatus::TYPE_ALL,
                'cover' => null,
                'description' => '适合秒杀、拼团和榜单组合的活动专题模板',
                'sort' => 70,
                'schema' => $this->promotionSchema(),
            ],
        ];
    }

    private function homeSchema(): array
    {
        return [
            'version' => 1,
            'page' => [
                'key' => 'home',
                'title' => '首页',
                'backgroundColor' => '#f7f8fa',
            ],
            'components' => [
                $this->banner('home-banner'),
                $this->quickNav('home-nav'),
                [
                    'id' => 'home-notice',
                    'type' => 'notice-bar',
                    'name' => '公告栏',
                    'enabled' => true,
                    'props' => ['speed' => 40],
                    'style' => [],
                    'data' => [
                        'items' => [
                            ['text' => '欢迎来到商城，更多优惠持续上新', 'link' => ['type' => 'page', 'value' => '/pages/index/index']],
                        ],
                    ],
                ],
                $this->imageAd('home-ad'),
                $this->titleBar('home-recommend-title', '精选推荐'),
                $this->productGroup('home-recommend-products', 'recommend'),
            ],
        ];
    }

    private function categorySchema(): array
    {
        return [
            'version' => 1,
            'page' => [
                'key' => 'category_home',
                'title' => '分类',
                'backgroundColor' => '#f7f8fa',
            ],
            'components' => [
                [
                    'id' => 'category-search',
                    'type' => 'search-bar',
                    'name' => '搜索框',
                    'enabled' => true,
                    'props' => ['placeholder' => '搜索商品'],
                    'style' => [],
                    'data' => [],
                ],
                $this->quickNav('category-nav'),
                $this->titleBar('category-new-title', '新品上架'),
                $this->productGroup('category-new-products', 'new'),
            ],
        ];
    }

    private function memberSchema(): array
    {
        return [
            'version' => 1,
            'page' => [
                'key' => 'member_home',
                'title' => '会员中心',
                'backgroundColor' => '#f7f8fa',
            ],
            'components' => [
                [
                    'id' => 'member-shop-info',
                    'type' => 'shop-info',
                    'name' => '店铺信息',
                    'enabled' => true,
                    'props' => ['showLogo' => true],
                    'style' => [],
                    'data' => [],
                ],
                [
                    'id' => 'member-coupons',
                    'type' => 'coupon-group',
                    'name' => '优惠券组',
                    'enabled' => true,
                    'props' => ['limit' => 3],
                    'style' => [],
                    'data' => ['couponIds' => []],
                ],
                $this->titleBar('member-hot-title', '会员热卖'),
                $this->productGroup('member-hot-products', 'hot'),
            ],
        ];
    }

    private function promotionSchema(): array
    {
        return [
            'version' => 1,
            'page' => [
                'key' => 'promotion_topic',
                'title' => '活动专题',
                'backgroundColor' => '#f7f8fa',
            ],
            'components' => [
                $this->banner('promotion-banner'),
                [
                    'id' => 'promotion-rank',
                    'type' => 'product-rank',
                    'name' => '商品榜单',
                    'enabled' => true,
                    'props' => ['rankType' => 'sales', 'limit' => 10],
                    'style' => [],
                    'data' => [],
                ],
                $this->titleBar('promotion-hot-title', '活动热卖'),
                $this->productGroup('promotion-hot-products', 'hot'),
            ],
        ];
    }

    private function banner(string $id): array
    {
        return [
            'id' => $id,
            'type' => 'banner',
            'name' => '轮播图',
            'enabled' => true,
            'props' => ['height' => 160, 'radius' => 8, 'autoplay' => true, 'interval' => 3000],
            'style' => [],
            'data' => ['items' => []],
        ];
    }

    private function quickNav(string $id): array
    {
        return [
            'id' => $id,
            'type' => 'quick-nav',
            'name' => '金刚区',
            'enabled' => true,
            'props' => ['columns' => 5, 'rows' => 1],
            'style' => [],
            'data' => ['items' => []],
        ];
    }

    private function imageAd(string $id): array
    {
        return [
            'id' => $id,
            'type' => 'image-ad',
            'name' => '图片广告',
            'enabled' => true,
            'props' => ['layout' => 'single'],
            'style' => [],
            'data' => ['items' => []],
        ];
    }

    private function titleBar(string $id, string $title): array
    {
        return [
            'id' => $id,
            'type' => 'title-bar',
            'name' => '标题栏',
            'enabled' => true,
            'props' => ['title' => $title, 'subtitle' => ''],
            'style' => [],
            'data' => [],
        ];
    }

    private function productGroup(string $id, string $mode): array
    {
        return [
            'id' => $id,
            'type' => 'product-group',
            'name' => '商品组',
            'enabled' => true,
            'props' => ['mode' => $mode, 'layout' => 'two-column', 'limit' => 10],
            'style' => [],
            'data' => ['product_ids' => []],
        ];
    }
}
