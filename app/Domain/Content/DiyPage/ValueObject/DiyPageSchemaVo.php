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

final class DiyPageSchemaVo
{
    private const MAX_COMPONENTS = 50;

    private const MAX_BANNER_ITEMS = 10;

    private const MAX_QUICK_NAV_ITEMS = 20;

    private const MAX_IMAGE_AD_ITEMS = 10;

    private const MAX_PRODUCT_GROUP_LIMIT = 50;

    private const COMPONENT_TYPES = [
        'banner',
        'quick-nav',
        'image-ad',
        'product-group',
        'title-bar',
        'gap',
        'divider',
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

    private function __construct(private readonly array $schema) {}

    public static function fromArray(array $schema, string $pageKey): self
    {
        self::assertPage($schema, $pageKey);
        self::assertComponents($schema['components']);

        return new self(self::normalize($schema));
    }

    public function toArray(): array
    {
        return $this->schema;
    }

    public function publishedPayload(): array
    {
        $schema = $this->schema;
        $schema['components'] = array_values(array_filter(
            $schema['components'],
            static fn (array $component): bool => ($component['enabled'] ?? true) === true
        ));

        return $schema;
    }

    private static function assertPage(array $schema, string $pageKey): void
    {
        if (($schema['version'] ?? null) !== 1) {
            throw new \DomainException('装修页面结构版本无效');
        }

        if (! isset($schema['page']) || ! \is_array($schema['page'])) {
            throw new \DomainException('装修页面信息不能为空');
        }

        if (($schema['page']['key'] ?? '') !== $pageKey) {
            throw new \DomainException('装修页面键不一致');
        }

        if (! isset($schema['components']) || ! \is_array($schema['components'])) {
            throw new \DomainException('装修组件不能为空');
        }
    }

    private static function assertComponents(array $components): void
    {
        if (\count($components) > self::MAX_COMPONENTS) {
            throw new \DomainException('装修组件最多50个');
        }

        $ids = [];
        foreach ($components as $component) {
            if (! \is_array($component)) {
                throw new \DomainException('装修组件格式无效');
            }

            $id = (string) ($component['id'] ?? '');
            if ($id === '') {
                throw new \DomainException('组件ID不能为空');
            }
            if (isset($ids[$id])) {
                throw new \DomainException('组件ID重复');
            }
            $ids[$id] = true;

            $type = (string) ($component['type'] ?? '');
            if (! \in_array($type, self::COMPONENT_TYPES, true)) {
                throw new \DomainException('不支持的装修组件');
            }

            self::assertComponentData($type, $component);
        }
    }

    private static function assertComponentData(string $type, array $component): void
    {
        $data = \is_array($component['data'] ?? null) ? $component['data'] : [];
        $props = \is_array($component['props'] ?? null) ? $component['props'] : [];

        if ($type === 'banner') {
            self::assertItemsLimit($data, self::MAX_BANNER_ITEMS, '轮播图最多10张');
        }

        if ($type === 'quick-nav') {
            self::assertItemsLimit($data, self::MAX_QUICK_NAV_ITEMS, '金刚区最多20个入口');
        }

        if ($type === 'image-ad') {
            self::assertItemsLimit($data, self::MAX_IMAGE_AD_ITEMS, '图片广告最多10张');
        }

        if ($type === 'product-group') {
            $limit = (int) ($props['limit'] ?? $data['limit'] ?? 10);
            if ($limit > self::MAX_PRODUCT_GROUP_LIMIT) {
                throw new \DomainException('商品组数量最多50个');
            }
        }

        self::assertLinks($data);
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

    private static function normalize(array $schema): array
    {
        $schema['components'] = array_values(array_map(static function (array $component): array {
            $component['enabled'] = (bool) ($component['enabled'] ?? true);
            $component['props'] = \is_array($component['props'] ?? null) ? $component['props'] : [];
            $component['style'] = \is_array($component['style'] ?? null) ? $component['style'] : [];
            $component['data'] = \is_array($component['data'] ?? null) ? $component['data'] : [];
            return $component;
        }, $schema['components']));

        return $schema;
    }
}
