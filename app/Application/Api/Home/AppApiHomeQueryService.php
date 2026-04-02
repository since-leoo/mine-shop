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

namespace App\Application\Api\Home;

use App\Application\Api\Product\AppApiCategoryQueryService;
use App\Application\Api\Product\AppApiProductQueryService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;
use App\Domain\Trade\Seckill\Api\Query\DomainApiSeckillQueryService;

final class AppApiHomeQueryService
{
    public function __construct(
        private readonly AppApiProductQueryService $productQueryService,
        private readonly AppApiCategoryQueryService $categoryQueryService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly DomainSystemSettingService $systemSettingService,
        private readonly DomainApiSeckillQueryService $seckillQueryService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'banners' => $this->resolveBanners(),
            'activity_image' => $this->resolveActivityImage(),
            'headline' => $this->mallSettingService->basic()->mallName(),
            'categories' => $this->categoryQueryService->tree(0)->take(10)->values()->all(),
            'featured_products' => $this->productQueryService->page(['is_recommend' => true, 'status' => 'active'], 1, 10)['list'],
            'hot_products' => $this->productQueryService->page(['is_hot' => true, 'status' => 'active'], 1, 10)['list'],
            'new_products' => $this->productQueryService->page(['is_new' => true, 'status' => 'active'], 1, 10)['list'],
        ];
    }

    /**
     * @return string[]
     */
    private function resolveBanners(): array
    {
        $custom = $this->systemSettingService->get('mall.home.banners', null);
        if (\is_array($custom) && $custom !== []) {
            $banners = array_values(array_filter(array_map(static function ($item) {
                if (\is_string($item)) {
                    return trim($item);
                }
                if (\is_array($item)) {
                    $candidate = $item['image'] ?? $item['url'] ?? $item['img'] ?? null;
                    return \is_string($candidate) ? trim($candidate) : '';
                }
                return '';
            }, $custom)));

            if ($banners !== []) {
                return $banners;
            }
        }

        return [];
    }

    private function resolveActivityImage(): ?string
    {
        $custom = $this->systemSettingService->get('mall.home.activity_banner', null);
        if (\is_string($custom) && $custom !== '') {
            return $custom;
        }

        return $this->mallSettingService->basic()->logo() ?: null;
    }
}
