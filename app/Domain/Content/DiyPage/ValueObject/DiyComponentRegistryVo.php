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

namespace App\Domain\Content\DiyPage\ValueObject;

final class DiyComponentRegistryVo
{
    public const CURRENT_SCHEMA_VERSION = 1;

    private const MAX_BANNER_ITEMS = 10;

    private const MAX_QUICK_NAV_ITEMS = 20;

    private const MAX_IMAGE_AD_ITEMS = 10;

    private const MAX_PRODUCT_GROUP_LIMIT = 50;

    private const MAX_NOTICE_BAR_ITEMS = 10;

    private const MAX_COUPON_GROUP_LIMIT = 10;

    private const MAX_MARKETING_GROUP_LIMIT = 20;

    private const MAX_IMAGE_CUBE_ITEMS = 10;

    private const COMPONENT_TYPES = [
        'banner',
        'quick-nav',
        'image-ad',
        'product-group',
        'title-bar',
        'gap',
        'divider',
        'notice-bar',
        'coupon-group',
        'seckill-group',
        'group-buy-group',
        'product-rank',
        'search-bar',
        'shop-info',
        'rich-text',
        'image-cube',
    ];

    private const LINK_TYPES = [
        'page',
        'url',
        'product',
        'category',
        'coupon',
        'group_buy',
        'seckill',
    ];

    private const PRODUCT_RANK_TYPES = [
        'sales',
        'new',
        'recommend',
        'hot',
    ];

    private const PRODUCT_GROUP_SOURCES = [
        'manual',
        'recommend',
        'hot',
        'new',
        'category',
        'tag',
        'activity',
    ];

    private const PRODUCT_GROUP_SORTS = [
        'default',
        'sales',
        'price_asc',
        'price_desc',
        'new',
    ];

    private const IMAGE_CUBE_LAYOUTS = [
        'one',
        'two',
        'three',
        'four',
        'left-one-right-two',
    ];

    private const IMAGE_AD_LAYOUTS = [
        'single',
        'two-column',
        'horizontal',
        'vertical',
    ];

    private const IMAGE_WIDTH_MODES = [
        'full',
        'contained',
        'custom',
    ];

    private const IMAGE_WIDTH_UNITS = [
        'percent',
        'px',
        'rpx',
    ];

    private const IMAGE_OBJECT_FITS = [
        'cover',
        'contain',
        'fill',
    ];

    /**
     * @return string[]
     */
    public static function componentTypes(): array
    {
        return self::COMPONENT_TYPES;
    }

    public static function assertSupportedComponent(string $type): void
    {
        if (! \in_array($type, self::COMPONENT_TYPES, true)) {
            throw new \DomainException('不支持的装修组件');
        }
    }

    public static function assertComponentData(string $type, array $component): void
    {
        $data = \is_array($component['data'] ?? null) ? $component['data'] : [];
        $props = \is_array($component['props'] ?? null) ? $component['props'] : [];

        match ($type) {
            'banner' => self::assertBanner($props, $data),
            'quick-nav' => self::assertItemsLimit($data, self::MAX_QUICK_NAV_ITEMS, '金刚区最多20个入口'),
            'image-ad' => self::assertImageAd($props, $data),
            'product-group' => self::assertProductGroup($props, $data),
            'notice-bar' => self::assertItemsLimit($data, self::MAX_NOTICE_BAR_ITEMS, '公告栏最多10条'),
            'coupon-group' => self::assertCouponGroup($props, $data),
            'seckill-group' => self::assertMarketingGroup($props, $data, '秒杀组件必须选择活动'),
            'group-buy-group' => self::assertMarketingGroup($props, $data, '拼团组件必须选择活动'),
            'product-rank' => self::assertProductRank($props, $data),
            'search-bar' => self::assertSearchBar($props),
            'image-cube' => self::assertImageCube($props, $data),
            default => null,
        };

        self::assertLinks($data);
    }

    public static function normalizeComponent(array $component): array
    {
        $component['enabled'] = (bool) ($component['enabled'] ?? true);
        $component['props'] = \is_array($component['props'] ?? null) ? $component['props'] : [];
        $component['style'] = \is_array($component['style'] ?? null) ? $component['style'] : [];
        $component['data'] = \is_array($component['data'] ?? null) ? $component['data'] : [];

        if (($component['type'] ?? '') === 'rich-text') {
            $component['data']['content'] = self::cleanRichText((string) ($component['data']['content'] ?? ''));
        }

        return $component;
    }

    public static function migrateSchema(array $schema): array
    {
        $version = (int) ($schema['version'] ?? 0);
        if ($version < self::CURRENT_SCHEMA_VERSION) {
            $schema['version'] = self::CURRENT_SCHEMA_VERSION;
        }

        return $schema;
    }

    private static function assertCouponGroup(array $props, array $data): void
    {
        self::assertLimit($props, $data, self::MAX_COUPON_GROUP_LIMIT, '优惠券组最多10张');

        $couponIds = $data['couponIds'] ?? [];
        if (\is_array($couponIds) && \count($couponIds) > self::MAX_COUPON_GROUP_LIMIT) {
            throw new \DomainException('优惠券组最多10张');
        }
    }

    private static function assertBanner(array $props, array $data): void
    {
        self::assertImageProps($props);
        self::assertItemsLimit($data, self::MAX_BANNER_ITEMS, '轮播图最多10张');
    }

    private static function assertImageAd(array $props, array $data): void
    {
        self::assertImageProps($props);

        $layout = (string) ($props['layout'] ?? $data['layout'] ?? 'single');
        if (! \in_array($layout, self::IMAGE_AD_LAYOUTS, true)) {
            throw new \DomainException('图片广告布局无效');
        }

        self::assertItemsLimit($data, self::MAX_IMAGE_AD_ITEMS, '图片广告最多10张');
    }

    private static function assertMarketingGroup(array $props, array $data, string $missingMessage): void
    {
        self::assertLimit($props, $data, self::MAX_MARKETING_GROUP_LIMIT, '营销组件商品最多20个');

        $activityId = (int) ($data['activityId'] ?? $data['activity_id'] ?? $props['activityId'] ?? $props['activity_id'] ?? 0);
        if ($activityId <= 0) {
            throw new \DomainException($missingMessage);
        }
    }

    private static function assertProductRank(array $props, array $data): void
    {
        self::assertLimit($props, $data, self::MAX_PRODUCT_GROUP_LIMIT, '商品榜单数量最多50个');

        $rankType = (string) ($props['rankType'] ?? $data['rankType'] ?? 'sales');
        if (! \in_array($rankType, self::PRODUCT_RANK_TYPES, true)) {
            throw new \DomainException('商品榜单类型无效');
        }
    }

    private static function assertProductGroup(array $props, array $data): void
    {
        self::assertLimit($props, $data, self::MAX_PRODUCT_GROUP_LIMIT, '商品组数量最多50个');

        $source = (string) ($props['source'] ?? $data['source'] ?? $data['mode'] ?? 'recommend');
        if (! \in_array($source, self::PRODUCT_GROUP_SOURCES, true)) {
            throw new \DomainException('商品组来源无效');
        }

        $sort = (string) ($props['sort'] ?? $data['sort'] ?? 'default');
        if (! \in_array($sort, self::PRODUCT_GROUP_SORTS, true)) {
            throw new \DomainException('商品组排序无效');
        }
    }

    private static function assertSearchBar(array $props): void
    {
        $placeholder = (string) ($props['placeholder'] ?? '');
        if (mb_strlen($placeholder) > 30) {
            throw new \DomainException('搜索框占位文案最多30个字');
        }
    }

    private static function assertImageCube(array $props, array $data): void
    {
        self::assertImageProps($props);

        $layout = (string) ($props['layout'] ?? $data['layout'] ?? 'one');
        if (! \in_array($layout, self::IMAGE_CUBE_LAYOUTS, true)) {
            throw new \DomainException('图片魔方布局无效');
        }

        self::assertItemsLimit($data, self::MAX_IMAGE_CUBE_ITEMS, '图片魔方最多10张');
    }

    private static function assertImageProps(array $props): void
    {
        $widthMode = (string) ($props['widthMode'] ?? 'full');
        if (! \in_array($widthMode, self::IMAGE_WIDTH_MODES, true)) {
            throw new \DomainException('图片组件宽度模式无效');
        }

        $widthUnit = (string) ($props['widthUnit'] ?? 'percent');
        if (! \in_array($widthUnit, self::IMAGE_WIDTH_UNITS, true)) {
            throw new \DomainException('图片组件宽度单位无效');
        }

        if ($widthMode === 'custom') {
            $width = (int) ($props['width'] ?? 100);
            if ($widthUnit === 'percent' && ($width < 1 || $width > 100)) {
                throw new \DomainException('图片组件百分比宽度需在1-100之间');
            }
            if ($widthUnit !== 'percent' && ($width < 1 || $width > 750)) {
                throw new \DomainException('图片组件宽度需在1-750之间');
            }
        }

        $height = (int) ($props['height'] ?? 0);
        if ($height < 0 || $height > 1000) {
            throw new \DomainException('图片组件高度需在0-1000之间');
        }

        $radius = (int) ($props['radius'] ?? 0);
        if ($radius < 0 || $radius > 100) {
            throw new \DomainException('图片组件圆角需在0-100之间');
        }

        $objectFit = (string) ($props['objectFit'] ?? 'cover');
        if (! \in_array($objectFit, self::IMAGE_OBJECT_FITS, true)) {
            throw new \DomainException('图片组件填充方式无效');
        }
    }

    private static function assertLimit(array $props, array $data, int $max, string $message): void
    {
        $limit = (int) ($props['limit'] ?? $data['limit'] ?? 10);
        if ($limit > $max) {
            throw new \DomainException($message);
        }
    }

    private static function assertItemsLimit(array $data, int $limit, string $message): void
    {
        $items = $data['items'] ?? [];
        if (\is_array($items) && \count($items) > $limit) {
            throw new \DomainException($message);
        }
    }

    private static function assertLinks(array $data): void
    {
        $items = $data['items'] ?? [];
        if (! \is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (! \is_array($item) || ! isset($item['link']) || ! \is_array($item['link'])) {
                continue;
            }

            $linkType = (string) ($item['link']['type'] ?? '');
            if ($linkType !== '' && ! \in_array($linkType, self::LINK_TYPES, true)) {
                throw new \DomainException('不支持的跳转类型');
            }
        }
    }

    private static function cleanRichText(string $content): string
    {
        $content = preg_replace('#<(script|style|iframe)[^>]*>.*?</\\1>#is', '', $content) ?? '';
        $content = strip_tags($content, '<p><br><strong><b><em><i><u><span><div><ul><ol><li><img><a>');

        return trim($content);
    }
}
