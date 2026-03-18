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

namespace App\Interface\Api\Transformer;

final class HomeTransformer
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function transformOverview(array $payload): array
    {
        return [
            'swiper' => $payload['banners'] ?? [],
            'tabList' => [],
            'activityImg' => $payload['activity_image'] ?? null,
            'sections' => [
                'featured' => $payload['featured_products'] ?? [],
                'hot' => $payload['hot_products'] ?? [],
                'new' => $payload['new_products'] ?? [],
            ],
            'headline' => $payload['headline'] ?? '',
            'categoryList' => $this->transformCategories($payload['categories'] ?? []),
            'seckillList' => $payload['seckill']['list'] ?? [],
            'seckillEndTime' => $payload['seckill']['endTime'] ?? 0,
            'seckillTitle' => $payload['seckill']['title'] ?? '',
            'seckillActivityId' => $payload['seckill']['activityId'] ?? 0,
            'seckillSessionId' => $payload['seckill']['sessionId'] ?? 0,
            'groupBuyList' => [],
        ];
    }

    /**
     * @param array<int, mixed> $categories
     * @return array<int, array{id: int, name: string, icon: null|string}>
     */
    private function transformCategories(array $categories): array
    {
        return array_values(array_map(static function ($category): array {
            if (is_array($category)) {
                return [
                    'id' => (int) ($category['id'] ?? 0),
                    'name' => (string) ($category['name'] ?? ''),
                    'icon' => ($category['icon'] ?? null) ?: null,
                ];
            }

            return [
                'id' => (int) ($category->id ?? 0),
                'name' => (string) ($category->name ?? ''),
                'icon' => ($category->icon ?? null) ?: null,
            ];
        }, $categories));
    }
}
