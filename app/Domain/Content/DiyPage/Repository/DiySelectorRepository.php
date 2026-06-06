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

namespace App\Domain\Content\DiyPage\Repository;

use App\Infrastructure\Model\Coupon\Coupon;
use App\Infrastructure\Model\GroupBuy\GroupBuy;
use App\Infrastructure\Model\Product\Category;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Seckill\SeckillSession;
use Hyperf\Database\Model\Builder;
use Hyperf\Paginator\AbstractPaginator;

class DiySelectorRepository
{
    /**
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function products(array $params, int $page, int $pageSize): array
    {
        $query = Product::query()
            ->select(['id', 'name', 'main_image', 'min_price', 'max_price', 'status', 'is_recommend', 'is_hot', 'is_new'])
            ->when(! empty($params['keyword']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%'))
            ->when(! empty($params['category_id']), static fn (Builder $q) => $q->where('category_id', (int) $params['category_id']))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->orderByDesc('sort')
            ->orderByDesc('id');

        /** @var AbstractPaginator $paginator */
        $paginator = $query->paginate($pageSize, ['*'], 'per_page', $page);

        return [
            'list' => array_map(static fn (Product $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'main_image' => $item->main_image,
                'min_price' => (int) $item->min_price,
                'max_price' => (int) $item->max_price,
                'status' => (string) $item->status,
                'is_recommend' => (bool) $item->is_recommend,
                'is_hot' => (bool) $item->is_hot,
                'is_new' => (bool) $item->is_new,
            ], $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function categories(array $params): array
    {
        return Category::query()
            ->select(['id', 'parent_id', 'name', 'icon', 'thumbnail', 'level', 'sort', 'status'])
            ->when(! empty($params['keyword']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%'))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(static fn (Category $item) => [
                'id' => (int) $item->id,
                'parent_id' => (int) $item->parent_id,
                'name' => (string) $item->name,
                'icon' => $item->icon,
                'thumbnail' => $item->thumbnail,
                'level' => (int) $item->level,
                'sort' => (int) $item->sort,
                'status' => (string) $item->status,
            ])
            ->toArray();
    }

    /**
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function coupons(array $params, int $page, int $pageSize): array
    {
        $query = Coupon::query()
            ->select(['id', 'name', 'type', 'value', 'min_amount', 'total_quantity', 'used_quantity', 'start_time', 'end_time', 'status'])
            ->when(! empty($params['keyword']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['keyword'] . '%'))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->orderByDesc('id');

        /** @var AbstractPaginator $paginator */
        $paginator = $query->paginate($pageSize, ['*'], 'per_page', $page);

        return [
            'list' => array_map(static fn (Coupon $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'type' => $item->type,
                'value' => (int) $item->value,
                'min_amount' => (int) $item->min_amount,
                'total_quantity' => (int) $item->total_quantity,
                'used_quantity' => (int) $item->used_quantity,
                'start_time' => $item->start_time?->toDateTimeString(),
                'end_time' => $item->end_time?->toDateTimeString(),
                'status' => (string) $item->status,
            ], $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function seckills(array $params, int $page, int $pageSize): array
    {
        $query = SeckillSession::query()
            ->with(['activity:id,title,status,is_enabled'])
            ->select(['id', 'activity_id', 'start_time', 'end_time', 'status', 'total_quantity', 'sold_quantity', 'sort_order', 'is_enabled'])
            ->when(! empty($params['keyword']), static function (Builder $q) use ($params): void {
                $q->whereHas('activity', static fn (Builder $activityQuery) => $activityQuery->where('title', 'like', '%' . $params['keyword'] . '%'));
            })
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(\array_key_exists('is_enabled', $params), static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']))
            ->orderByDesc('sort_order')
            ->orderByDesc('id');

        /** @var AbstractPaginator $paginator */
        $paginator = $query->paginate($pageSize, ['*'], 'per_page', $page);

        return [
            'list' => array_map(static fn (SeckillSession $item) => [
                'id' => (int) $item->id,
                'activity_id' => (int) $item->activity_id,
                'title' => (string) ($item->activity?->title ?? ''),
                'start_time' => $item->start_time?->toDateTimeString(),
                'end_time' => $item->end_time?->toDateTimeString(),
                'status' => (string) $item->status,
                'total_quantity' => (int) $item->total_quantity,
                'sold_quantity' => (int) $item->sold_quantity,
                'is_enabled' => (bool) $item->is_enabled,
            ], $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function groupBuys(array $params, int $page, int $pageSize): array
    {
        $query = GroupBuy::query()
            ->select(['id', 'title', 'product_id', 'sku_id', 'group_price', 'min_people', 'max_people', 'start_time', 'end_time', 'status', 'total_quantity', 'sold_quantity', 'is_enabled'])
            ->when(! empty($params['keyword']), static fn (Builder $q) => $q->where('title', 'like', '%' . $params['keyword'] . '%'))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(\array_key_exists('is_enabled', $params), static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']))
            ->orderByDesc('sort_order')
            ->orderByDesc('id');

        /** @var AbstractPaginator $paginator */
        $paginator = $query->paginate($pageSize, ['*'], 'per_page', $page);

        return [
            'list' => array_map(static fn (GroupBuy $item) => [
                'id' => (int) $item->id,
                'title' => (string) $item->title,
                'product_id' => (int) $item->product_id,
                'sku_id' => (int) $item->sku_id,
                'group_price' => (int) $item->group_price,
                'min_people' => (int) $item->min_people,
                'max_people' => (int) $item->max_people,
                'start_time' => $item->start_time?->toDateTimeString(),
                'end_time' => $item->end_time?->toDateTimeString(),
                'status' => (string) $item->status,
                'total_quantity' => (int) $item->total_quantity,
                'sold_quantity' => (int) $item->sold_quantity,
                'is_enabled' => (bool) $item->is_enabled,
            ], $paginator->items()),
            'total' => $paginator->total(),
        ];
    }
}
