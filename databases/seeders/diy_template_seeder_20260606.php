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
                'theme' => $this->theme(),
                'style' => $this->pageStyle('home'),
            ],
            'components' => [
                $this->banner('home-banner'),
                $this->quickNav('home-nav', $this->homeNavItems()),
                $this->marketingEntry('home-marketing-entry'),
                [
                    'id' => 'home-notice',
                    'type' => 'notice-bar',
                    'name' => '公告栏',
                    'enabled' => true,
                    'props' => ['speed' => 40],
                    'style' => [],
                    'data' => [
                        'items' => [
                            ['text' => '欢迎来到商城，更多优惠持续上新', 'link' => ['type' => 'page', 'path' => '/pages/home/index']],
                        ],
                    ],
                ],
                $this->imageAd('home-ad', '满199减40', '/pages/coupon/coupon-center/index', $this->bannerImage(3)),
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
                'theme' => $this->theme(),
                'style' => $this->pageStyle('category_home'),
            ],
            'components' => [
                $this->categoryPanel('category-panel'),
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
                'theme' => $this->theme(),
                'style' => $this->pageStyle('member_home'),
            ],
            'components' => [
                [
                    'id' => 'member-shop-info',
                    'type' => 'shop-info',
                    'name' => '店铺信息',
                    'enabled' => true,
                    'props' => [
                        'showLogo' => true,
                        'logo' => 'assets/tab/user-active.png',
                        'name' => '官方商城',
                        'description' => '精选好物，安心选购',
                    ],
                    'style' => [
                        'backgroundColor' => '#FFF7ED',
                        'color' => '#C2410C',
                        'margin' => ['top' => 0, 'right' => 16, 'bottom' => 8, 'left' => 16],
                        'padding' => ['top' => 0, 'right' => 4, 'bottom' => 0, 'left' => 4],
                        'borderRadius' => 10,
                        'fontSize' => 12,
                        'fontWeight' => 700,
                    ],
                    'data' => ['tags' => ['正品保障', '极速发货', '售后无忧']],
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
                'theme' => $this->theme(),
                'style' => $this->pageStyle('promotion_topic'),
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
            'props' => ['widthMode' => 'contained', 'height' => 148, 'radius' => 24, 'objectFit' => 'cover', 'autoplay' => true, 'interval' => 3000],
            'style' => $id === 'home-banner' ? [
                'margin' => ['top' => 0, 'right' => 16, 'bottom' => 10, 'left' => 16],
                'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
            ] : [],
            'data' => [
                'items' => [
                    $this->imageItem('默认轮播图', '/pages/goods/result/index', $this->bannerImage(1)),
                    $this->imageItem('领券中心', '/pages/coupon/coupon-center/index', $this->bannerImage(2)),
                ],
            ],
        ];
    }

    private function quickNav(string $id, array $items = []): array
    {
        return [
            'id' => $id,
            'type' => 'quick-nav',
            'name' => '金刚区',
            'enabled' => true,
            'props' => ['columns' => 5, 'rows' => 1, 'iconSize' => 48, 'iconRadius' => 16, 'imageSize' => 30, 'itemGap' => 7],
            'style' => $id === 'home-nav' ? [
                'margin' => ['top' => 0, 'right' => 16, 'bottom' => 8, 'left' => 16],
                'padding' => ['top' => 11, 'right' => 8, 'bottom' => 7, 'left' => 8],
                'background' => '#FFFFFF',
                'borderRadius' => 16,
                'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
            ] : [],
            'data' => ['items' => $items],
        ];
    }

    private function marketingEntry(string $id): array
    {
        return [
            'id' => $id,
            'type' => 'marketing-entry',
            'name' => '营销入口',
            'enabled' => true,
            'props' => [
                'title' => '今日活动直达',
                'layout' => 'two-column',
                'itemGap' => 10,
                'cardRadius' => 10,
            ],
            'style' => $id === 'home-marketing-entry' ? [
                'margin' => ['top' => 0, 'right' => 16, 'bottom' => 8, 'left' => 16],
                'padding' => ['top' => 12, 'right' => 14, 'bottom' => 14, 'left' => 14],
                'background' => '#FFFFFF',
                'borderRadius' => 16,
                'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
            ] : [],
            'data' => [
                'items' => [
                    $this->marketingEntryItem('秒杀专场', '点击进入专题页', '距下一场 02:18:45', '/pages/promotion/seckill/index', '#F0A18E'),
                    $this->marketingEntryItem('拼团会场', '精选团购每天上新', '3人团最低5折起', '/pages/promotion/group-buy/index', '#86BFA9'),
                ],
            ],
        ];
    }

    private function marketingEntryItem(string $title, string $subtitle, string $badge, string $path, string $background): array
    {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'badge' => $badge,
            'background' => $background,
            'color' => '#FFFFFF',
            'link' => $this->pageLink($path),
        ];
    }

    private function categoryPanel(string $id): array
    {
        return [
            'id' => $id,
            'type' => 'category-panel',
            'name' => '商品分类',
            'enabled' => true,
            'props' => [
                'title' => '商品分类',
                'columns' => 3,
                'activeIndex' => 0,
            ],
            'style' => [
                'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                'height' => '100%',
            ],
            'data' => [],
        ];
    }

    private function imageAd(string $id, string $title = '广告图', string $path = '/pages/home/index', string $image = ''): array
    {
        return [
            'id' => $id,
            'type' => 'image-ad',
            'name' => '图片广告',
            'enabled' => true,
            'props' => ['layout' => 'single', 'widthMode' => 'contained', 'height' => 120, 'radius' => 16, 'objectFit' => 'cover'],
            'style' => $id === 'home-ad' ? [
                'margin' => ['top' => 0, 'right' => 16, 'bottom' => 8, 'left' => 16],
                'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
            ] : [],
            'data' => [
                'items' => [
                    $this->imageItem($title, $path, $image ?: $this->bannerImage(3)),
                ],
            ],
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
            'style' => [
                'color' => '#3A2A22',
                'fontSize' => $id === 'home-recommend-title' ? 16 : 17,
                'fontWeight' => 700,
                'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                'padding' => ['top' => $id === 'home-recommend-title' ? 16 : 8, 'right' => 16, 'bottom' => 10, 'left' => 16],
            ],
            'data' => [],
        ];
    }

    private function productGroup(string $id, string $mode): array
    {
        $props = [
            'source' => $mode,
            'mode' => $mode,
            'layout' => 'two-column',
            'limit' => 10,
        ];

        if ($id === 'home-recommend-products') {
            $props['variant'] = 'home-recommend';
            $props['gap'] = 10;
            $props['listPadding'] = ['top' => 0, 'right' => 16, 'bottom' => 12, 'left' => 16];
        }

        return [
            'id' => $id,
            'type' => 'product-group',
            'name' => '商品组',
            'enabled' => true,
            'props' => $props,
            'style' => $id === 'home-recommend-products' ? [
                'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                'padding' => ['top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 0],
            ] : [],
            'data' => ['product_ids' => []],
        ];
    }

    private function pageStyle(string $key): array
    {
        if ($key !== 'home') {
            return ['background' => $this->theme()['backgroundColor']];
        }

        return [
            'background' => 'linear-gradient(180deg, #E8836B 0, #F2A99A 90px, #F5C5A3 180px, #FAF3ED 330px, #FAF3ED 100%)',
        ];
    }

    private function theme(): array
    {
        return [
            'primaryColor' => '#E8836B',
            'priceColor' => '#E8836B',
            'backgroundColor' => '#FAF3ED',
            'cardRadius' => 16,
            'buttonShape' => 'round',
        ];
    }

    private function homeNavItems(): array
    {
        return [
            $this->navItem('分类', '/pages/category/index', [], 'assets/home-quick/grid.svg', '类', '#F3E5F5'),
            $this->navItem('优惠券', '/pages/coupon/coupon-center/index', [], 'assets/home-quick/gift.svg', '券', '#FFF3E0'),
            $this->navItem('拼团活动', '/pages/promotion/group-buy/index', [], 'assets/home-quick/group.svg', '团', '#E8F5E9'),
            $this->navItem('热卖榜单', '/pages/promotion/hot-ranking/index', [], 'assets/home-quick/fire.svg', '榜', '#FCE4EC'),
            $this->navItem('我的', '/pages/usercenter/index', [], 'assets/tab/user.png', '我', '#FFF1E8'),
        ];
    }

    private function categoryNavItems(): array
    {
        return [
            $this->navItem('热卖榜', '/pages/promotion/hot-ranking/index', [], 'assets/home-quick/fire.svg', '榜', '#FCE4EC'),
            $this->navItem('新品', '/pages/goods/result/index', ['sort' => 'new'], 'assets/home-quick/leaf.svg', '新', '#E8F5E9'),
            $this->navItem('优惠券', '/pages/coupon/coupon-center/index', [], 'assets/home-quick/tag.svg', '券', '#FFF1E8'),
            $this->navItem('购物车', '/pages/cart/index', [], 'assets/tab/cart.png', '车', '#FFF3E0'),
            $this->navItem('会员中心', '/pages/usercenter/index', [], 'assets/tab/user.png', '我', '#F3E5F5'),
        ];
    }

    private function navItem(
        string $title,
        string $path,
        array $params = [],
        string $icon = '',
        string $iconText = '',
        string $iconBg = '#FFF1E8'
    ): array {
        $item = [
            'title' => $title,
            'icon' => $icon,
            'iconText' => $iconText !== '' ? $iconText : mb_substr($title, 0, 1),
            'iconBg' => $iconBg,
            'link' => $this->pageLink($path, $params),
        ];

        $svg = $this->quickNavSvg($icon);
        if ($svg !== '') {
            $item['svg'] = $svg;
        }

        return $item;
    }

    private function quickNavSvg(string $icon): string
    {
        if (! preg_match('/\.(svg|png)$/', $icon)) {
            return '';
        }

        $asset = preg_replace('#^assets/#', '', ltrim($icon, '/'));
        $asset = preg_replace('/\.png$/', '.svg', $asset);
        $path = dirname(__DIR__, 2) . '/web/public/diy-assets/' . $asset;
        if (! is_file($path)) {
            return '';
        }

        $svg = trim((string) file_get_contents($path));
        return str_starts_with($svg, '<svg') ? $svg : '';
    }

    private function imageItem(string $title, string $path, string $image): array
    {
        return [
            'image' => $image,
            'title' => $title,
            'link' => $this->pageLink($path),
        ];
    }

    private function pageLink(string $path, array $params = []): array
    {
        return array_filter([
            'type' => 'page',
            'path' => $path,
            'params' => $params,
        ], static fn ($value): bool => $value !== []);
    }

    private function bannerImage(int $index): string
    {
        return sprintf('https://tdesign.gtimg.com/miniprogram/template/retail/home/v2/banner%d.png', $index);
    }
}
