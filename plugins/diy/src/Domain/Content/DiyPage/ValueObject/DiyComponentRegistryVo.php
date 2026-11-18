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

namespace Plugin\Diy\Domain\Content\DiyPage\ValueObject;

use App\Infrastructure\Error\DiyError;

final class DiyComponentRegistryVo
{
    public const CURRENT_SCHEMA_VERSION = 1;

    private const MAX_BANNER_ITEMS = 10;

    private const MAX_QUICK_NAV_ITEMS = 20;

    private const MAX_CATEGORY_PANEL_ITEMS = 20;

    private const MAX_IMAGE_AD_ITEMS = 10;

    private const MAX_PRODUCT_GROUP_LIMIT = 50;

    private const MAX_NOTICE_BAR_ITEMS = 10;

    private const MAX_COUPON_GROUP_LIMIT = 10;

    private const MAX_MARKETING_GROUP_LIMIT = 20;

    private const MAX_IMAGE_CUBE_ITEMS = 10;

    private const COMPONENT_TYPES = [
        'banner',
        'quick-nav',
        'category-panel',
        'image-ad',
        'product-group',
        'title-bar',
        'gap',
        'divider',
        'notice-bar',
        'coupon-group',
        'marketing-entry',
        'seckill-group',
        'group-buy-group',
        'product-rank',
        'search-bar',
        'shop-info',
        'user-profile-header',
        'user-stats',
        'user-order-panel',
        'user-menu-list',
        'distribution-center',
        'rich-text',
        'image-cube',
    ];

    private const LINK_TYPES = [
        'none',
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
            throw DiyError::COMPONENT_UNSUPPORTED->exception();
        }
    }

    public static function assertComponentData(string $type, array $component): void
    {
        $data = \is_array($component['data'] ?? null) ? $component['data'] : [];
        $props = \is_array($component['props'] ?? null) ? $component['props'] : [];

        match ($type) {
            'banner' => self::assertBanner($props, $data),
            'quick-nav' => self::assertItemsLimit($data, self::MAX_QUICK_NAV_ITEMS, DiyError::QUICK_NAV_LIMIT_EXCEEDED),
            'category-panel' => self::assertItemsLimit($data, self::MAX_CATEGORY_PANEL_ITEMS, DiyError::CATEGORY_PANEL_LIMIT_EXCEEDED),
            'image-ad' => self::assertImageAd($props, $data),
            'product-group' => self::assertProductGroup($props, $data),
            'notice-bar' => self::assertItemsLimit($data, self::MAX_NOTICE_BAR_ITEMS, DiyError::NOTICE_LIMIT_EXCEEDED),
            'coupon-group' => self::assertCouponGroup($props, $data),
            'marketing-entry' => self::assertItemsLimit($data, self::MAX_NOTICE_BAR_ITEMS, DiyError::MARKETING_ENTRY_LIMIT_EXCEEDED),
            'seckill-group' => self::assertMarketingGroup($props, $data, DiyError::SECKILL_ACTIVITY_REQUIRED),
            'group-buy-group' => self::assertMarketingGroup($props, $data + ['activityId' => ($data['groupBuyIds'][0] ?? $data['activities'][0]['id'] ?? 0)], DiyError::GROUP_BUY_ACTIVITY_REQUIRED),
            'product-rank' => self::assertProductRank($props, $data),
            'search-bar' => self::assertSearchBar($props),
            'image-cube' => self::assertImageCube($props, $data),
            'user-stats' => self::assertItemsLimit($data, 8, DiyError::USER_STATS_LIMIT_EXCEEDED),
            'user-order-panel' => self::assertItemsLimit($data, 10, DiyError::ORDER_ENTRY_LIMIT_EXCEEDED),
            'user-menu-list' => self::assertItemsLimit($data, 10, DiyError::USER_MENU_LIMIT_EXCEEDED),
            'distribution-center' => self::assertDistributionCenter($props, $data),
            'user-profile-header' => self::assertUserProfileHeader($props),
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
        self::assertLimit($props, $data, self::MAX_COUPON_GROUP_LIMIT, DiyError::COUPON_LIMIT_EXCEEDED);

        $couponIds = $data['couponIds'] ?? [];
        if (\is_array($couponIds) && \count($couponIds) > self::MAX_COUPON_GROUP_LIMIT) {
            throw DiyError::COUPON_LIMIT_EXCEEDED->exception();
        }
    }

    private static function assertDistributionCenter(array $props, array $data): void
    {
        $allowed = ['title', 'showInvite', 'showTeam', 'showOrders', 'showCommission'];
        if (array_diff(array_keys($props), $allowed) !== [] || $data !== []) {
            throw DiyError::DISTRIBUTION_RUNTIME_DATA_FORBIDDEN->exception();
        }
        self::assertDisplaySwitches($props, ['showInvite', 'showTeam', 'showOrders', 'showCommission']);
        if (isset($props['title']) && (! \is_string($props['title']) || mb_strlen($props['title']) > 50)) {
            throw DiyError::DISTRIBUTION_TITLE_TOO_LONG->exception();
        }
    }

    private static function assertUserProfileHeader(array $props): void
    {
        // Runtime eligibility is supplied by the distribution API, never by published DIY content.
        if (array_key_exists('showInviteEntry', $props) || array_key_exists('phoneNumber', $props)) {
            throw DiyError::PROFILE_RUNTIME_DATA_FORBIDDEN->exception();
        }
        self::assertDisplaySwitches($props, ['showInvite', 'showQrcode']);
    }

    private static function assertDisplaySwitches(array $props, array $keys): void
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $props) && ! \is_bool($props[$key])) {
                throw DiyError::DISPLAY_SWITCH_INVALID->exception();
            }
        }
    }

    private static function assertBanner(array $props, array $data): void
    {
        self::assertImageProps($props);
        self::assertItemsLimit($data, self::MAX_BANNER_ITEMS, DiyError::BANNER_LIMIT_EXCEEDED);
    }

    private static function assertImageAd(array $props, array $data): void
    {
        self::assertImageProps($props);

        $layout = (string) ($props['layout'] ?? $data['layout'] ?? 'single');
        if (! \in_array($layout, self::IMAGE_AD_LAYOUTS, true)) {
            throw DiyError::IMAGE_AD_LAYOUT_INVALID->exception();
        }

        self::assertItemsLimit($data, self::MAX_IMAGE_AD_ITEMS, DiyError::IMAGE_AD_LIMIT_EXCEEDED);
    }

    private static function assertMarketingGroup(array $props, array $data, DiyError $missingMessage): void
    {
        self::assertLimit($props, $data, self::MAX_MARKETING_GROUP_LIMIT, DiyError::MARKETING_PRODUCT_LIMIT_EXCEEDED);

        $activityId = (int) ($data['activityId'] ?? $data['activity_id'] ?? $props['activityId'] ?? $props['activity_id'] ?? 0);
        if ($activityId <= 0) {
            throw $missingMessage->exception();
        }
    }

    private static function assertProductRank(array $props, array $data): void
    {
        self::assertLimit($props, $data, self::MAX_PRODUCT_GROUP_LIMIT, DiyError::PRODUCT_RANK_LIMIT_EXCEEDED);

        $rankType = (string) ($props['rankType'] ?? $data['rankType'] ?? 'sales');
        if (! \in_array($rankType, self::PRODUCT_RANK_TYPES, true)) {
            throw DiyError::PRODUCT_RANK_TYPE_INVALID->exception();
        }
    }

    private static function assertProductGroup(array $props, array $data): void
    {
        self::assertLimit($props, $data, self::MAX_PRODUCT_GROUP_LIMIT, DiyError::PRODUCT_GROUP_LIMIT_EXCEEDED);

        $source = (string) ($props['source'] ?? $data['source'] ?? $data['mode'] ?? 'recommend');
        if (! \in_array($source, self::PRODUCT_GROUP_SOURCES, true)) {
            throw DiyError::PRODUCT_SOURCE_INVALID->exception();
        }

        $sort = (string) ($props['sort'] ?? $data['sort'] ?? 'default');
        if (! \in_array($sort, self::PRODUCT_GROUP_SORTS, true)) {
            throw DiyError::PRODUCT_SORT_INVALID->exception();
        }
    }

    private static function assertSearchBar(array $props): void
    {
        $placeholder = (string) ($props['placeholder'] ?? '');
        if (mb_strlen($placeholder) > 30) {
            throw DiyError::SEARCH_PLACEHOLDER_TOO_LONG->exception();
        }
    }

    private static function assertImageCube(array $props, array $data): void
    {
        self::assertImageProps($props);

        $layout = (string) ($props['layout'] ?? $data['layout'] ?? 'one');
        if (! \in_array($layout, self::IMAGE_CUBE_LAYOUTS, true)) {
            throw DiyError::IMAGE_CUBE_LAYOUT_INVALID->exception();
        }

        self::assertItemsLimit($data, self::MAX_IMAGE_CUBE_ITEMS, DiyError::IMAGE_CUBE_LIMIT_EXCEEDED);
    }

    private static function assertImageProps(array $props): void
    {
        $widthMode = (string) ($props['widthMode'] ?? 'full');
        if (! \in_array($widthMode, self::IMAGE_WIDTH_MODES, true)) {
            throw DiyError::IMAGE_WIDTH_MODE_INVALID->exception();
        }

        $widthUnit = (string) ($props['widthUnit'] ?? 'percent');
        if (! \in_array($widthUnit, self::IMAGE_WIDTH_UNITS, true)) {
            throw DiyError::IMAGE_WIDTH_UNIT_INVALID->exception();
        }

        if ($widthMode === 'custom') {
            $width = (int) ($props['width'] ?? 100);
            if ($widthUnit === 'percent' && ($width < 1 || $width > 100)) {
                throw DiyError::IMAGE_PERCENT_WIDTH_INVALID->exception();
            }
            if ($widthUnit !== 'percent' && ($width < 1 || $width > 750)) {
                throw DiyError::IMAGE_WIDTH_INVALID->exception();
            }
        }

        $height = (int) ($props['height'] ?? 0);
        if ($height < 0 || $height > 1000) {
            throw DiyError::IMAGE_HEIGHT_INVALID->exception();
        }

        $radius = (int) ($props['radius'] ?? 0);
        if ($radius < 0 || $radius > 100) {
            throw DiyError::IMAGE_RADIUS_INVALID->exception();
        }

        $objectFit = (string) ($props['objectFit'] ?? 'cover');
        if (! \in_array($objectFit, self::IMAGE_OBJECT_FITS, true)) {
            throw DiyError::IMAGE_FIT_INVALID->exception();
        }
    }

    private static function assertLimit(array $props, array $data, int $max, DiyError $message): void
    {
        $limit = (int) ($props['limit'] ?? $data['limit'] ?? 10);
        if ($limit > $max) {
            throw $message->exception();
        }
    }

    private static function assertItemsLimit(array $data, int $limit, DiyError $message): void
    {
        $items = $data['items'] ?? [];
        if (\is_array($items) && \count($items) > $limit) {
            throw $message->exception();
        }
    }

    private static function assertLinks(array $data): void
    {
        $items = $data['items'] ?? [];
        if (! \is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (! \is_array($item)) {
                continue;
            }

            if (isset($item['children']) && \is_array($item['children'])) {
                self::assertLinks(['items' => $item['children']]);
            }

            if (! isset($item['link']) || ! \is_array($item['link'])) {
                continue;
            }

            $linkType = (string) ($item['link']['type'] ?? '');
            if ($linkType !== '' && ! \in_array($linkType, self::LINK_TYPES, true)) {
                throw DiyError::LINK_TYPE_UNSUPPORTED->exception();
            }
        }
    }

    private static function cleanRichText(string $content): string
    {
        $content = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', $content) ?? '';
        $content = strip_tags($content, '<p><br><strong><b><em><i><u><span><div><ul><ol><li><img><a>');

        return trim($content);
    }
}
