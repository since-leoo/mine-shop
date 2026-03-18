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

namespace App\Domain\Trade\AfterSale\Repository;

use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Enum\AfterSaleStatus;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\AfterSale\AfterSale;
use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<AfterSale>
 */
final class AfterSaleRepository extends IRepository
{
    public function __construct(protected readonly AfterSale $model) {}

    public function createFromEntity(AfterSaleEntity $entity): AfterSale
    {
        $record = $this->create($entity->toArray());
        $entity->setId((int) $record->id);

        return $record;
    }

    public function updateFromEntity(AfterSaleEntity $entity): bool
    {
        $record = $this->findById($entity->getId());
        if ($record === null) {
            return false;
        }

        return $record->update($entity->toArray());
    }

    public function findActiveByOrderItemId(int $orderItemId): ?AfterSale
    {
        /** @var AfterSale|null $info */
        $info = $this->model::where('order_item_id', $orderItemId)
            ->whereNotIn('status', [
                AfterSaleStatus::COMPLETED->value,
                AfterSaleStatus::CLOSED->value,
            ])
            ->first();

        return $info ?: null;
    }

    public function paginateByMember(int $memberId, string $status = 'all', int $page = 1, int $pageSize = 10): LengthAwarePaginatorInterface
    {
        $query = $this->model::where('member_id', $memberId)
            ->with(['order', 'orderItem'])
            ->orderByDesc('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    public function findByIdAndMember(int $id, int $memberId): AfterSale
    {
        /** @var AfterSale|null $record */
        $record = $this->model::where('id', $id)
            ->where('member_id', $memberId)
            ->with(['order', 'orderItem'])
            ->first();

        if ($record === null) {
            throw new \RuntimeException('售后单不存在');
        }

        return $record;
    }

    /**
     * 后台分页查询售后单。
     *
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function pageForAdmin(array $params, int $page, int $pageSize): array
    {
        return $this->page($params, $page, $pageSize);
    }

    /**
     * 查询后台售后详情。
     */
    public function findDetailById(int $id): ?AfterSale
    {
        return AfterSale::query()
            ->with(['order', 'orderItem'])
            ->whereKey($id)
            ->first();
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->with(['order', 'orderItem'])
            ->when(! empty($params['after_sale_no']), static fn (Builder $builder) => $builder->where('after_sale_no', 'like', '%' . $params['after_sale_no'] . '%'))
            ->when(! empty($params['member_id']), static fn (Builder $builder) => $builder->where('member_id', (int) $params['member_id']))
            ->when(! empty($params['type']), static fn (Builder $builder) => $builder->where('type', $params['type']))
            ->when(! empty($params['status']) && $params['status'] !== 'all', static fn (Builder $builder) => $builder->where('status', $params['status']))
            ->when(! empty($params['order_no']), static fn (Builder $builder) => $builder->whereHas('order', static fn (Builder $orderQuery) => $orderQuery->where('order_no', 'like', '%' . $params['order_no'] . '%')))
            ->orderByDesc('id');
    }

    public function handleItems(Collection $items): Collection
    {
        return $items->map(static fn (AfterSale $item) => $item->toArray());
    }
}