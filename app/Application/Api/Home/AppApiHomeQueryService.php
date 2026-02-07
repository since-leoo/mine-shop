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

use App\Application\Api\Product\AppApiProductQueryService;
use App\Domain\SystemSetting\Service\DomainMallSettingService;
use App\Domain\SystemSetting\Service\DomainSystemSettingService;

/**
 * 首页数据聚合应用服务.
 */
final class AppApiHomeQueryService
{
    public function __construct(
        private readonly AppApiProductQueryService $productQueryService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly DomainSystemSettingService $systemSettingService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $featured = $this->productQueryService->page(['is_recommend' => true, 'status' => 'active'], 1, 10)['list'];
        $hot = $this->productQueryService->page(['is_hot' => true, 'status' => 'active'], 1, 10)['list'];
        $new = $this->productQueryService->page(['is_new' => true, 'status' => 'active'], 1, 10)['list'];

        return [
            'swiper' => $this->resolveBanners(),
            'tabList' => $this->buildTabs(),
            'activityImg' => $this->resolveActivityImage(),
            'sections' => [
                'featured' => $featured,
                'hot' => $hot,
                'new' => $new,
            ],
            'headline' => $this->mallSettingService->basic()->mallName(),
        ];
    }

    /**
     * @return array<int, array{text:string,key:string}>
     */
    private function buildTabs(): array
    {
        return [
            ['text' => '精选推荐', 'key' => 'featured'],
            ['text' => '热销榜单', 'key' => 'hot'],
            ['text' => '新品速递', 'key' => 'new'],
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
}
