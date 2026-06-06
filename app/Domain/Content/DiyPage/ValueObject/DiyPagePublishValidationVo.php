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

final class DiyPagePublishValidationVo
{
    private const IMAGE_COMPONENTS = [
        'banner',
        'image-ad',
        'image-cube',
    ];

    private const ACTIVITY_COMPONENTS = [
        'seckill-group',
        'group-buy-group',
    ];

    private function __construct(private readonly array $issues) {}

    public static function inspect(array $schema): self
    {
        $issues = [];
        $components = \is_array($schema['components'] ?? null) ? $schema['components'] : [];

        foreach ($components as $index => $component) {
            if (! \is_array($component) || ($component['enabled'] ?? true) === false) {
                continue;
            }

            $type = (string) ($component['type'] ?? '');
            $name = (string) ($component['name'] ?? $type);
            $data = \is_array($component['data'] ?? null) ? $component['data'] : [];
            $props = \is_array($component['props'] ?? null) ? $component['props'] : [];

            self::inspectImages($issues, $type, $name, $index, $data);
            self::inspectProductGroup($issues, $type, $name, $index, $props, $data);
            self::inspectActivity($issues, $type, $name, $index, $props, $data);
        }

        return new self($issues);
    }

    public function passed(): bool
    {
        return $this->issues === [];
    }

    public function issues(): array
    {
        return $this->issues;
    }

    private static function inspectImages(array &$issues, string $type, string $name, int $index, array $data): void
    {
        if (! \in_array($type, self::IMAGE_COMPONENTS, true)) {
            return;
        }

        $items = \is_array($data['items'] ?? null) ? $data['items'] : [];
        foreach ($items as $itemIndex => $item) {
            if (! \is_array($item)) {
                continue;
            }

            $image = (string) ($item['image'] ?? $item['img'] ?? $item['url'] ?? '');
            if ($image === '') {
                $issues[] = self::issue('image_required', $name, $index, sprintf('第%d张图片不能为空', $itemIndex + 1));
            }

            if (\is_array($item['link'] ?? null)) {
                self::inspectLink($issues, $name, $index, $item['link']);
            }
        }
    }

    private static function inspectProductGroup(array &$issues, string $type, string $name, int $index, array $props, array $data): void
    {
        if ($type !== 'product-group') {
            return;
        }

        $source = (string) ($props['source'] ?? $data['source'] ?? $data['mode'] ?? 'recommend');
        if ($source !== 'manual') {
            return;
        }

        $productIds = \is_array($data['product_ids'] ?? null) ? $data['product_ids'] : [];
        $products = \is_array($data['products'] ?? null) ? $data['products'] : [];
        if ($productIds === [] && $products === []) {
            $issues[] = self::issue('product_source_required', $name, $index, '手动商品组必须选择商品');
        }
    }

    private static function inspectActivity(array &$issues, string $type, string $name, int $index, array $props, array $data): void
    {
        if (! \in_array($type, self::ACTIVITY_COMPONENTS, true)) {
            return;
        }

        $activityId = (int) ($data['activityId'] ?? $data['activity_id'] ?? $props['activityId'] ?? $props['activity_id'] ?? 0);
        if ($activityId <= 0) {
            $issues[] = self::issue('activity_required', $name, $index, '营销组件必须选择活动');
        }
    }

    private static function inspectLink(array &$issues, string $name, int $index, array $link): void
    {
        $type = (string) ($link['type'] ?? '');
        if ($type === '') {
            return;
        }

        if ($type === 'page') {
            $path = (string) ($link['path'] ?? $link['url'] ?? '');
            if ($path === '') {
                $issues[] = self::issue('link_required', $name, $index, '页面跳转路径不能为空');
            }
            return;
        }

        if (\in_array($type, ['product', 'category', 'coupon', 'group_buy', 'seckill'], true) && (string) ($link['id'] ?? '') === '') {
            $issues[] = self::issue('link_required', $name, $index, '业务跳转ID不能为空');
        }
    }

    private static function issue(string $code, string $componentName, int $componentIndex, string $message): array
    {
        return [
            'level' => 'blocking',
            'code' => $code,
            'component_name' => $componentName,
            'component_index' => $componentIndex,
            'message' => $message,
        ];
    }
}
