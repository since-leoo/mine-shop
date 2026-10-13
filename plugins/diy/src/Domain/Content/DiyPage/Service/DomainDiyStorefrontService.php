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

namespace Plugin\Diy\Domain\Content\DiyPage\Service;

use App\Domain\Infrastructure\Plugin\Contract\DiyCatalogDataSourceInterface;

/** 重新读取可售目录，发布时的选择器快照不能作为价格、上架状态或活动有效性的依据。 */
final class DomainDiyStorefrontService
{
    public function __construct(private readonly DiyCatalogDataSourceInterface $catalog) {}

    public function resolve(array $payload): array
    {
        foreach ($payload['components'] ?? [] as $index => $component) {
            if (($component['enabled'] ?? true) === false) {
                continue;
            }
            $type = $component['type'] ?? '';
            $props = $component['props'] ?? [];
            $data = $component['data'] ?? [];
            $limit = max(1, min(50, (int) ($props['limit'] ?? $data['limit'] ?? 10)));
            if ($type === 'product-group' || $type === 'product-rank') {
                $source = $type === 'product-rank'
                    ? (string) ($props['rankType'] ?? 'hot')
                    : (string) ($props['source'] ?? $data['source'] ?? $props['mode'] ?? $data['mode'] ?? 'recommend');
                $filters = ['status' => 'active'];
                $ids = [];
                unset($data['unavailable_reason']);
                switch ($source) {
                    case 'manual':
                        $ids = $this->ids($data['product_ids'] ?? $data['productIds'] ?? array_column($data['products'] ?? [], 'id'));
                        $filters['ids'] = $ids;
                        break;
                    case 'category':
                        $filters['category_id'] = (int) ($props['categoryId'] ?? $props['category_id'] ?? 0);
                        break;
                    case 'recommend': $filters['is_recommend'] = true;
                        break;
                    case 'hot': $filters['is_hot'] = true;
                        break;
                    case 'new': $filters['is_new'] = true;
                        break;
                    case 'sales': $props['sort'] = 'sales_desc';
                        break;
                    default:
                        // 标签关系和活动类型尚无目录查询契约，不回退成不相干的普通商品。
                        $data['unavailable_reason'] = 'unsupported_source';
                }
                $sort = [
                    'price_asc' => ['min_price', 'asc'], 'price_desc' => ['min_price', 'desc'],
                    'sales' => ['real_sales', 'desc'], 'sales_desc' => ['real_sales', 'desc'],
                    'new' => ['id', 'desc'], 'newest' => ['id', 'desc'],
                ][$props['sort'] ?? 'default'] ?? null;
                if ($sort !== null) {
                    [$filters['order_by'], $filters['order_by_direction']] = $sort;
                }
                $invalid = isset($data['unavailable_reason']) || ($source === 'manual' && $ids === [])
                    || ($source === 'category' && $filters['category_id'] <= 0);
                $data['products'] = $invalid ? [] : ($this->catalog->storefrontProducts($filters, 1, $limit)['list'] ?? []);
                if ($source === 'manual') {
                    $order = array_flip($ids);
                    usort($data['products'], static fn (array $a, array $b): int => ($order[(int) $a['id']] ?? \PHP_INT_MAX) <=> ($order[(int) $b['id']] ?? \PHP_INT_MAX));
                }
            } elseif ($type === 'group-buy-group') {
                $ids = $this->ids($data['groupBuyIds'] ?? $data['group_buy_ids'] ?? array_column($data['activities'] ?? [], 'id'));
                $activities = $ids === [] ? [] : ($this->catalog->groupBuys(['ids' => $ids, 'status' => 'active', 'is_enabled' => true], 1, min(20, $limit))['list'] ?? []);
                $data['activities'] = array_values(array_filter($activities, fn (array $activity): bool => $this->active($activity)));
            } elseif ($type === 'seckill-group') {
                $sessionId = (int) ($data['sessionId'] ?? $data['session_id'] ?? $data['session']['id'] ?? 0);
                $activityId = (int) ($data['activityId'] ?? $data['activity_id'] ?? $props['activityId'] ?? 0);
                $sessions = $sessionId <= 0 ? [] : ($this->catalog->seckills(['ids' => [$sessionId], 'activity_id' => $activityId, 'status' => 'active', 'is_enabled' => true], 1, 1)['list'] ?? []);
                $session = $sessions[0] ?? null;
                $data['session'] = $session !== null && $this->active($session) ? $session : null;
                // 现有场次目录只提供会场信息；商品价格由秒杀专题实时加载。
                $data['products'] = [];
                $data['items'] = [];
            }
            $payload['components'][$index]['data'] = $data;
        }
        return $payload;
    }

    private function ids(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
    }

    private function active(array $activity): bool
    {
        $now = time();
        $start = strtotime((string) ($activity['start_time'] ?? ''));
        $end = strtotime((string) ($activity['end_time'] ?? ''));
        return ($activity['is_enabled'] ?? false) && ($activity['status'] ?? '') === 'active'
            && $start !== false && $start <= $now && $end !== false && $end > $now;
    }
}
