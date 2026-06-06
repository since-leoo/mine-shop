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

namespace App\Application\Api\Content;

use App\Application\Api\Product\AppApiProductQueryService;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\Service\DomainDiyPageService;

final class AppApiDiyPageQueryService
{
    public function __construct(
        private readonly DomainDiyPageService $diyPageService,
        private readonly AppApiProductQueryService $productQueryService,
    ) {}

    public function published(string $pageKey, string $pageType = DiyPageStatus::TYPE_MINIPROGRAM): ?array
    {
        $payload = $this->diyPageService->getPublished($pageKey, $pageType);
        if ($payload === null) {
            return null;
        }

        return $this->fillProductGroups($payload);
    }

    private function fillProductGroups(array $payload): array
    {
        $payload['components'] = array_values(array_map(function (array $component): array {
            if (($component['type'] ?? '') !== 'product-group') {
                return $component;
            }

            $data = \is_array($component['data'] ?? null) ? $component['data'] : [];
            $props = \is_array($component['props'] ?? null) ? $component['props'] : [];
            $mode = (string) ($props['mode'] ?? $data['mode'] ?? 'manual');
            $limit = max(1, min(50, (int) ($props['limit'] ?? $data['limit'] ?? 10)));

            $data['products'] = match ($mode) {
                'recommend' => $this->productQueryService->page(['is_recommend' => true, 'status' => 'active'], 1, $limit)['list'],
                'hot' => $this->productQueryService->page(['is_hot' => true, 'status' => 'active'], 1, $limit)['list'],
                'new' => $this->productQueryService->page(['is_new' => true, 'status' => 'active'], 1, $limit)['list'],
                default => $this->manualProducts($data, $limit),
            };
            $component['data'] = $data;

            return $component;
        }, $payload['components'] ?? []));

        return $payload;
    }

    private function manualProducts(array $data, int $limit): array
    {
        $ids = array_values(array_filter(array_map(
            static fn ($id): int => (int) $id,
            \is_array($data['product_ids'] ?? null) ? $data['product_ids'] : []
        )));

        if ($ids === []) {
            return [];
        }

        $result = $this->productQueryService->page(['ids' => array_slice($ids, 0, $limit), 'status' => 'active'], 1, $limit);
        $products = $result['list'];
        $sort = array_flip($ids);
        usort($products, static fn (array $a, array $b): int => ($sort[(int) ($a['id'] ?? 0)] ?? 999999) <=> ($sort[(int) ($b['id'] ?? 0)] ?? 999999));

        return $products;
    }
}
