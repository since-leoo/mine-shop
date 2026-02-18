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
        return [
            'total' => GroupBuy::count(),
            'enabled' => GroupBuy::where('is_enabled', true)->count(),
            'disabled' => GroupBuy::where('is_enabled', false)->count(),
            'active' => GroupBuy::where('status', 'active')->count(),
        ];
    }

    /** @return GroupBuy[] */
    public function findPendingActivitiesWithinMinutes(int $minutes): array
    {
        $deadline = Carbon::now()->addMinutes($minutes);
        return GroupBuy::where('status', GroupBuyStatus::PENDING->value)
            ->where('is_enabled', true)
            ->where('start_time', '<=', $deadline)
            ->get()->all();
    }

    /** @return GroupBuy[] */
    public function findActiveExpiredActivities(): array
    {
        return GroupBuy::where('status', GroupBuyStatus::ACTIVE->value)
            ->where('end_time', '<', Carbon::now())
            ->get()->all();
    }
}
