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

/**
 * 首页数据聚合应用服务.
 */
final class AppApiHomeQueryService
{
    public function __construct(
        private readonly AppApiProductQueryService $productQueryService,
        private readonly AppApiCategoryQueryService $categoryQueryService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly DomainSystemSettingService $systemSettingService,
        private readonly DomainApiSeckillQueryService $seckillQueryService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $featured = $this->productQueryService->page(['is_recommend' => true, 'status' => 'active'], 1, 10)['list'];
        $hot = $this->productQueryService->page(['is_hot' => true, 'status' => 'active'], 1, 10)['list'];
        $new = $this->productQueryService->page(['is_new' => true, 'status' => 'active'], 1, 10)['list'];

        $seckill = $this->resolveSeckill();

        return [
            'swiper' => $this->resolveBanners(),
            'tabList' => [],
            'activityImg' => $this->resolveActivityImage(),
            'sections' => [
                'featured' => $featured,
                'hot' => $hot,
                'new' => $new,
            ],
            'headline' => $this->mallSettingService->basic()->mallName(),
            'categoryList' => $this->resolveTopCategories(),
            'seckillList' => $seckill['list'],
            'seckillEndTime' => $seckill['endTime'],
            'seckillTitle' => $seckill['title'],
            'seckillActivityId' => $seckill['activityId'],
            'seckillSessionId' => $seckill['sessionId'],
            'groupBuyList' => [],
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

        $logo = $this->mallSettingService->basic()->logo();
        return $logo ? [$logo] : [];
    }

    private function resolveActivityImage(): ?string
    {
        $custom = $this->systemSettingService->get('mall.home.activity_banner', null);
        if (\is_string($custom) && $custom !== '') {
            return $custom;
        }
        return $this->mallSettingService->basic()->logo() ?: null;
    }

    /**
     * 获取顶级分类列表（金刚区）.
     *
     * @return array<int, array{id: int, name: string, icon: null|string}>
     */
    private function resolveTopCategories(): array
    {
        $categories = $this->categoryQueryService->tree(0);

        return $categories->take(10)->map(static fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'icon' => $cat->icon ?: null,
        ])->values()->toArray();
    }

    private function resolveSeckill(): array
    {
        return $this->seckillQueryService->getHomeSeckill(6);
    }
}
