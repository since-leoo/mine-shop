<?php

declare(strict_types=1);

use App\Domain\Content\DiyPage\Enum\DiyPageStatus;

final class DefaultDiyPageSchemas
{
    private const THEME = [
        'primaryColor' => '#E8836B',
        'priceColor' => '#E8836B',
        'backgroundColor' => '#FAF3ED',
        'cardRadius' => 16,
        'buttonShape' => 'round',
    ];

    public static function all(): array
    {
        $schemas = [];
        foreach ([DiyPageStatus::TYPE_MINIPROGRAM, DiyPageStatus::TYPE_H5] as $pageType) {
            foreach (self::definitions($pageType) as $definition) {
                $schemas[] = $definition;
            }
        }

        return $schemas;
    }

    private static function definitions(string $pageType): array
    {
        return [
            self::page('home', '首页', $pageType, '默认商城首页', [
                self::search('home-search', '搜索你需要的商品', '/pages/goods/search/index'),
                self::banner('home-banner', 148, 24, [
                    self::imageItem('春日上新', '/pages/goods/result/index', self::bannerImage(1)),
                    self::imageItem('限时特惠', '/pages/coupon/coupon-center/index', self::bannerImage(2)),
                ]),
                self::quickNav('home-nav', [
                    self::navItem('分类', '/pages/category/index', [], 'assets/home-quick/grid.svg', '类', '#F3E5F5'),
                    self::navItem('优惠券', '/pages/coupon/coupon-center/index', [], 'assets/home-quick/gift.svg', '券', '#FFF3E0'),
                    self::navItem('拼团活动', '/pages/promotion/group-buy/index', [], 'assets/home-quick/group.svg', '团', '#E8F5E9'),
                    self::navItem('热卖榜单', '/pages/promotion/hot-ranking/index', [], 'assets/home-quick/fire.svg', '榜', '#FCE4EC'),
                    self::navItem('我的', '/pages/usercenter/index', [], 'assets/tab/user.png', '我', '#FFF1E8'),
                ]),
                self::marketingEntry('home-marketing-entry'),
                self::notice('home-notice', '新人下单立减，优惠持续上新', '/pages/coupon/coupon-center/index'),
                self::imageAd('home-coupon-ad', '满199减40', '/pages/coupon/coupon-center/index', 96, 18, self::bannerImage(3)),
                self::title('home-recommend-title', '人气推荐', '为你精选好物'),
                self::productGroup('home-recommend-products', 'recommend', 10, 'two-column'),
            ]),
            self::page('category', '商品分类', $pageType, '默认分类页', [
                self::categoryPanel('category-panel'),
            ]),
            self::page('cart', '购物车', $pageType, '默认购物车页', [
                self::title('cart-title', '购物车', '已选商品会在这里展示'),
                self::richText('cart-empty-state', '购物车还是空的，先去挑点喜欢的商品吧', [
                    'margin' => ['top' => 40, 'right' => 16, 'bottom' => 12, 'left' => 16],
                    'padding' => ['top' => 48, 'right' => 18, 'bottom' => 48, 'left' => 18],
                    'backgroundColor' => '#FFFFFF',
                    'borderRadius' => 24,
                    'color' => '#A89088',
                    'fontSize' => 14,
                    'textAlign' => 'center',
                    'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
                ]),
                self::title('cart-recommend-title', '猜你喜欢', '加入购物车前再看看'),
                self::productGroup('cart-recommend-products', 'recommend', 6, 'two-column', [
                    'variant' => 'design-card-grid',
                    'gap' => 10,
                    'listPadding' => ['top' => 0, 'right' => 16, 'bottom' => 16, 'left' => 16],
                ]),
            ]),
            self::page('usercenter', '会员中心', $pageType, '默认会员中心页', [
                self::userProfileHeader('user-profile-header'),
                self::userStats('user-stats'),
                self::userOrderPanel('user-order-panel'),
                self::userMenuList('user-account-menu', [
                    self::menuItem('收货地址', '/pages/user/address/list/index', 'assets/usercenter/menu-address.svg'),
                    self::menuItem('优惠券', '/pages/coupon/coupon-list/index', 'assets/usercenter/menu-coupon.svg', '3张可用'),
                    self::menuItem('我的钱包', '/pages/usercenter/wallet-transactions/index', 'assets/usercenter/menu-wallet.svg'),
                ]),
                self::userMenuList('user-service-menu', [
                    self::menuItem('联系客服', '/pages/usercenter/index', 'assets/usercenter/menu-help.svg'),
                    self::menuItem('设置', '/pages/user/person-info/index', 'assets/usercenter/menu-settings.svg'),
                ]),
            ]),
            self::page('coupon-center', '领券中心', $pageType, '默认领券中心页', [
                self::imageAd('coupon-center-hero', '精选优惠券等你来领', '/pages/coupon/coupon-center/index', 132, 24, self::couponHeroImage()),
                self::couponGroup('coupon-center-list', 6, 'two-column'),
                self::title('coupon-products-title', '用券好物', '精选可用券商品'),
                self::productGroup('coupon-products', 'recommend', 8, 'two-column', [
                    'variant' => 'design-card-grid',
                    'gap' => 10,
                    'listPadding' => ['top' => 0, 'right' => 16, 'bottom' => 16, 'left' => 16],
                ]),
            ]),
        ];
    }

    private static function page(string $key, string $title, string $pageType, string $description, array $components): array
    {
        return [
            'page_key' => $key,
            'page_type' => $pageType,
            'title' => $title,
            'description' => $description,
            'schema' => [
                'version' => 1,
                'page' => [
                    'key' => $key,
                    'title' => $title,
                    'theme' => self::THEME,
                    'style' => self::pageStyle($key),
                ],
                'components' => $components,
            ],
        ];
    }

    private static function search(string $id, string $placeholder, string $target): array
    {
        return self::component($id, 'search-bar', '搜索框', [
            'placeholder' => $placeholder,
            'shape' => 'round',
            'target' => $target,
        ], [], self::homeOnlyStyle($id, [
            'margin' => ['top' => 4, 'right' => 16, 'bottom' => 10, 'left' => 16],
            'padding' => ['top' => 0, 'right' => 14, 'bottom' => 0, 'left' => 14],
            'height' => 38,
            'borderRadius' => 22,
            'background' => 'rgba(255,255,255,0.88)',
            'boxShadow' => '0 2px 12px rgba(200,140,110,0.10)',
            'color' => '#C4B0A6',
            'fontSize' => 13,
        ]));
    }

    private static function banner(string $id, int $height, int $radius, array $items): array
    {
        return self::component($id, 'banner', '轮播图', [
            'widthMode' => 'contained',
            'height' => $height,
            'radius' => $radius,
            'objectFit' => 'cover',
            'autoplay' => true,
        ], ['items' => $items], self::homeOnlyStyle($id, [
            'margin' => ['top' => 0, 'right' => 16, 'bottom' => 10, 'left' => 16],
            'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
        ]));
    }

    private static function quickNav(string $id, array $items, int $columns = 5, int $rows = 1, ?array $style = null): array
    {
        return self::component($id, 'quick-nav', '快捷入口', [
            'columns' => $columns,
            'rows' => $rows,
            'iconSize' => 48,
            'iconRadius' => 16,
            'imageSize' => 30,
            'itemGap' => 7,
        ], ['items' => $items], $style ?? self::homeOnlyStyle($id, [
            'margin' => ['top' => 0, 'right' => 16, 'bottom' => 8, 'left' => 16],
            'padding' => ['top' => 11, 'right' => 8, 'bottom' => 7, 'left' => 8],
            'background' => '#FFFFFF',
            'borderRadius' => 16,
            'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
        ]));
    }

    private static function marketingEntry(string $id): array
    {
        return self::component($id, 'marketing-entry', '营销入口', [
            'title' => '今日活动直达',
            'layout' => 'two-column',
            'itemGap' => 10,
            'cardRadius' => 10,
        ], [
            'items' => [
                self::marketingEntryItem('秒杀专场', '点击进入专题页', '距下一场 02:18:45', '/pages/promotion/seckill/index', '#F0A18E'),
                self::marketingEntryItem('拼团会场', '精选团购每天上新', '3人团最低5折起', '/pages/promotion/group-buy/index', '#86BFA9'),
            ],
        ], self::homeOnlyStyle($id, [
            'margin' => ['top' => 0, 'right' => 16, 'bottom' => 8, 'left' => 16],
            'padding' => ['top' => 12, 'right' => 14, 'bottom' => 14, 'left' => 14],
            'background' => '#FFFFFF',
            'borderRadius' => 16,
            'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
        ]));
    }

    private static function marketingEntryItem(string $title, string $subtitle, string $badge, string $path, string $background): array
    {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'badge' => $badge,
            'background' => $background,
            'color' => '#FFFFFF',
            'link' => self::pageLink($path),
        ];
    }

    private static function categoryPanel(string $id): array
    {
        return self::component($id, 'category-panel', '商品分类', [
            'title' => '商品分类',
            'columns' => 3,
            'activeIndex' => 0,
        ], [], [
            'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
            'height' => '100%',
        ]);
    }

    private static function notice(string $id, string $text, string $path): array
    {
        return self::component($id, 'notice-bar', '公告栏', [
            'speed' => 40,
            'showIcon' => true,
        ], [
            'items' => [
                ['text' => $text, 'link' => self::pageLink($path)],
            ],
        ], [
            'backgroundColor' => '#FFF7ED',
            'color' => '#C2410C',
            'margin' => ['top' => 0, 'right' => 16, 'bottom' => 8, 'left' => 16],
            'padding' => ['top' => 0, 'right' => 4, 'bottom' => 0, 'left' => 4],
            'borderRadius' => 10,
            'fontSize' => 12,
            'fontWeight' => 700,
        ]);
    }

    private static function imageAd(string $id, string $title, string $path, int $height, int $radius, string $image): array
    {
        return self::component($id, 'image-ad', '图片广告', [
            'layout' => 'single',
            'widthMode' => 'contained',
            'height' => $height,
            'radius' => $radius,
            'objectFit' => 'cover',
            ...($id === 'coupon-center-hero' ? ['variant' => 'coupon-hero'] : []),
        ], [
            'items' => [
                self::imageItem($title, $path, $image),
            ],
        ], self::homeOnlyStyle($id, [
            'margin' => ['top' => 0, 'right' => 16, 'bottom' => 8, 'left' => 16],
            'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
        ]));
    }

    private static function title(string $id, string $title, string $subtitle): array
    {
        return self::component($id, 'title-bar', '标题栏', [
            'title' => $title,
            'subtitle' => $subtitle,
        ], [], [
            'color' => '#3A2A22',
            'fontSize' => $id === 'home-recommend-title' ? 16 : 17,
            'fontWeight' => 700,
            'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
            'padding' => ['top' => $id === 'home-recommend-title' ? 16 : 8, 'right' => 16, 'bottom' => 10, 'left' => 16],
        ]);
    }

    private static function productGroup(string $id, string $source, int $limit, string $layout, array $extraProps = []): array
    {
        $props = [
            'source' => $source,
            'mode' => $source,
            'sort' => 'default',
            'limit' => $limit,
            'layout' => $layout,
        ];

        if ($id === 'home-recommend-products') {
            $props['variant'] = 'home-recommend';
            $props['gap'] = 10;
            $props['listPadding'] = ['top' => 0, 'right' => 16, 'bottom' => 12, 'left' => 16];
        }

        $props = array_replace($props, $extraProps);

        return self::component($id, 'product-group', '商品组', $props, [
            'product_ids' => [],
            'products' => [],
        ], self::homeOnlyStyle($id, [
            'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
            'padding' => ['top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 0],
        ]));
    }

    private static function pageStyle(string $key): array
    {
        if ($key === 'usercenter') {
            return [
                'background' => self::THEME['backgroundColor'],
                'topTransparent' => true,
            ];
        }

        if ($key !== 'home') {
            return ['background' => self::THEME['backgroundColor']];
        }

        return [
            'background' => 'linear-gradient(180deg, #E8836B 0, #F2A99A 90px, #F5C5A3 180px, #FAF3ED 330px, #FAF3ED 100%)',
        ];
    }

    private static function homeOnlyStyle(string $id, array $style): array
    {
        return str_starts_with($id, 'home-') ? $style : [];
    }

    private static function userProfileHeader(string $id): array
    {
        return self::component($id, 'user-profile-header', '用户中心头部', [
            'nickname' => '小花花',
            'inviteCode' => 'WARM2026',
            'avatar' => '',
            'qrcodeIcon' => 'assets/usercenter/profile-qrcode.svg',
        ], [], [
            'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
            'padding' => ['top' => 96, 'right' => 28, 'bottom' => 38, 'left' => 28],
            'background' => 'linear-gradient(180deg, #EF8D78 0%, #F3A896 48%, #F8D7BF 78%, #FFF4EA 100%)',
            'color' => '#FFFFFF',
            'borderRadius' => ['bottomLeft' => 30, 'bottomRight' => 30],
        ]);
    }

    private static function userStats(string $id): array
    {
        return self::component($id, 'user-stats', '用户资产统计', [], [
            'items' => [
                ['label' => '优惠券', 'value' => '3', 'link' => self::pageLink('/pages/coupon/coupon-list/index')],
                ['label' => '积分', 'value' => '520', 'link' => self::pageLink('/pages/usercenter/index')],
                ['label' => '余额', 'value' => '¥88', 'link' => self::pageLink('/pages/usercenter/wallet-transactions/index')],
                ['label' => '收藏', 'value' => '12', 'link' => self::pageLink('/pages/usercenter/index')],
            ],
        ], [
            'margin' => ['top' => -20, 'right' => 20, 'bottom' => 14, 'left' => 20],
            'padding' => ['top' => 0, 'right' => 0, 'bottom' => 14, 'left' => 0],
            'background' => '#FFFFFF',
            'borderRadius' => 16,
            'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
            'position' => 'relative',
            'zIndex' => 2,
        ]);
    }

    private static function userOrderPanel(string $id): array
    {
        return self::component($id, 'user-order-panel', '我的订单', [
            'title' => '我的订单',
            'moreText' => '全部订单',
            'moreLink' => self::pageLink('/pages/order/order-list/index'),
        ], [
            'items' => [
                self::orderItem('待付款', '/pages/order/order-list/index', ['tabType' => 5], 'assets/usercenter/order-pay.svg', true),
                self::orderItem('待发货', '/pages/order/order-list/index', ['tabType' => 10], 'assets/usercenter/order-deliver.svg'),
                self::orderItem('待收货', '/pages/order/order-list/index', ['tabType' => 40], 'assets/usercenter/order-receive.svg'),
                self::orderItem('待评价', '/pages/order/order-list/index', ['tabType' => 60], 'assets/usercenter/order-review.svg'),
                self::orderItem('退换/售后', '/pages/order/after-service-list/index', [], 'assets/usercenter/order-service.svg'),
            ],
        ], self::designPanelStyle(0, 14));
    }

    private static function userMenuList(string $id, array $items): array
    {
        return self::component($id, 'user-menu-list', '用户菜单', [], [
            'items' => $items,
        ], self::designPanelStyle(0, 14));
    }

    private static function shopInfo(string $id): array
    {
        return self::component($id, 'shop-info', '店铺信息', [
            'variant' => 'user-profile',
            'logo' => '',
            'name' => '温馨用户',
            'description' => '邀请码：WARM2026',
        ], [
            'tags' => ['正品保障', '极速发货', '售后无忧'],
        ], [
            'margin' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
            'padding' => ['top' => 48, 'right' => 20, 'bottom' => 48, 'left' => 20],
            'background' => 'linear-gradient(180deg, #E8836B 0%, #F2A99A 42%, #F5C5A3 72%, #FAF3ED 100%)',
            'color' => '#FFFFFF',
            'borderRadius' => 0,
        ]);
    }

    private static function couponGroup(string $id, int $limit, string $layout = 'scroll'): array
    {
        return self::component($id, 'coupon-group', '优惠券组', [
            'title' => '精选优惠券',
            'limit' => $limit,
            'layout' => $layout,
        ], [
            'couponIds' => [],
            'coupons' => [],
        ], [
            'margin' => ['top' => 8, 'right' => 16, 'bottom' => 14, 'left' => 16],
        ]);
    }

    private static function richText(string $id, string $content, ?array $style = null): array
    {
        return self::component($id, 'rich-text', '说明文本', [
            'padding' => 16,
        ], [
            'content' => '<p>' . $content . '</p>',
        ], $style ?? [
            'backgroundColor' => '#FFFFFF',
            'borderRadius' => 16,
            'color' => '#7A5F55',
            'fontSize' => 14,
        ]);
    }

    private static function imageItem(string $title, string $path, string $image): array
    {
        return [
            'image' => $image,
            'title' => $title,
            'link' => self::pageLink($path),
        ];
    }

    private static function bannerImage(int $index): string
    {
        return sprintf('https://tdesign.gtimg.com/miniprogram/template/retail/home/v2/banner%d.png', $index);
    }

    private static function couponHeroImage(): string
    {
        return self::bannerImage(3);
    }

    private static function designCardStyle(int $top = 0, int $bottom = 8): array
    {
        return [
            'margin' => ['top' => $top, 'right' => 16, 'bottom' => $bottom, 'left' => 16],
            'padding' => ['top' => 14, 'right' => 8, 'bottom' => 14, 'left' => 8],
            'background' => '#FFFFFF',
            'borderRadius' => 16,
            'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
        ];
    }

    private static function designPanelStyle(int $top = 0, int $bottom = 14): array
    {
        return [
            'margin' => ['top' => $top, 'right' => 20, 'bottom' => $bottom, 'left' => 20],
            'background' => '#FFFFFF',
            'borderRadius' => 14,
            'boxShadow' => '0 2px 8px rgba(200,140,110,0.08)',
        ];
    }

    private static function orderItem(string $label, string $path, array $params, string $icon, bool $badge = false): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'badge' => $badge,
            'link' => self::pageLink($path, $params),
        ];
    }

    private static function menuItem(string $label, string $path, string $icon, string $value = ''): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'value' => $value,
            'link' => self::pageLink($path),
        ];
    }

    private static function navItem(
        string $title,
        string $path,
        array $params = [],
        string $icon = '',
        string $iconText = '',
        string $iconBg = '#FFF1E8'
    ): array
    {
        $item = [
            'title' => $title,
            'icon' => $icon,
            'iconText' => $iconText !== '' ? $iconText : mb_substr($title, 0, 1),
            'iconBg' => $iconBg,
            'link' => self::pageLink($path, $params),
        ];

        $svg = self::quickNavSvg($icon);
        if ($svg !== '') {
            $item['svg'] = $svg;
        }

        return $item;
    }

    private static function quickNavSvg(string $icon): string
    {
        if (! preg_match('/\.(svg|png)$/', $icon)) {
            return '';
        }

        $asset = preg_replace('#^assets/#', '', ltrim($icon, '/'));
        $asset = preg_replace('/\.png$/', '.svg', $asset);
        $path = dirname(__DIR__, 3) . '/web/public/diy-assets/' . $asset;
        if (! is_file($path)) {
            return '';
        }

        $svg = trim((string) file_get_contents($path));
        return str_starts_with($svg, '<svg') ? $svg : '';
    }

    private static function pageLink(string $path, array $params = []): array
    {
        return array_filter([
            'type' => 'page',
            'path' => $path,
            'params' => $params,
        ], static fn ($value): bool => $value !== []);
    }

    private static function component(
        string $id,
        string $type,
        string $name,
        array $props = [],
        array $data = [],
        array $style = []
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'enabled' => true,
            'props' => $props,
            'style' => $style,
            'data' => $data,
        ];
    }
}
