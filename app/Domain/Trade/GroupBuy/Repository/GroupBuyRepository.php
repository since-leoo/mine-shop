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

namespace App\Domain\Trade\GroupBuy\Repository;

use App\Domain\Trade\GroupBuy\Enum\GroupBuyStatus;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\GroupBuy\GroupBuy;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;

/**
 * @extends IRepository<GroupBuy>
 */
final class GroupBuyRepository extends IRepository
{
    public function __construct(protected readonly GroupBuy $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['title']), static fn (Builder $q) => $q->where('title', 'like', '%' . $params['title'] . '%'))
            ->when(isset($params['keyword']), static fn (Builder $q) => $q->where('title', 'like', '%' . $params['keyword'] . '%'))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['is_enabled']), static fn (Builder $q) => $q->where('is_enabled', (bool) $params['is_enabled']))
            ->when(isset($params['product_id']), static fn (Builder $q) => $q->where('product_id', $params['product_id']))
            ->with(['product:id,name,main_image', 'sku:id,product_id,sku_name,sale_price'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc');
    }

    /**
     * 导出数据提供者.
     */
    public function getExportData(array $params): iterable
    {
        $query = $this->perQuery($this->getQuery()->with(['product']), $params);

        foreach ($query->cursor() as $item) {
            yield $item;
        }
    }

    public function getStatistics(): array
    {
        $query = $this->getQuery();

        return [
            'total' => (clone $query)->count(),
            'enabled' => (clone $query)->where('is_enabled', true)->count(),
            'disabled' => (clone $query)->where('is_enabled', false)->count(),
            'active' => (clone $query)->where('status', GroupBuyStatus::ACTIVE->value)->count(),
        ];
    }

    /** @return GroupBuy[] */
    public function findPendingActivitiesWithinMinutes(int $minutes): array
    {
        $deadline = Carbon::now()->addMinutes($minutes);
        return $this->getQuery()
            ->where('status', GroupBuyStatus::PENDING->value)
            ->where('is_enabled', true)
            ->where('start_time', '<=', $deadline)
            ->get()->all();
    }

    /** @return GroupBuy[] */
    public function findActiveExpiredActivities(): array
    {
        return $this->getQuery()
            ->where('status', GroupBuyStatus::ACTIVE->value)
            ->where('end_time', '<', Carbon::now())
            ->get()->all();
    }

    public function findPromotionActivities(int $limit = 20): Collection
    {
        $now = Carbon::now();

        return $this->getQuery()
            ->with('product:id,name,main_image')
            ->where('is_enabled', 1)
            ->where(static function (Builder $query) use ($now) {
                $query->whereIn('status', [GroupBuyStatus::ACTIVE->value, GroupBuyStatus::PENDING->value])
                    ->orWhere(static function (Builder $orQuery) use ($now) {
                        $orQuery
                            ->where('start_time', '<=', $now)
                            ->where('end_time', '>=', $now)
                            ->whereNotIn('status', [
                                GroupBuyStatus::ENDED->value,
                                GroupBuyStatus::CANCELLED->value,
                                GroupBuyStatus::SOLD_OUT->value,
                            ]);
                    });
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
