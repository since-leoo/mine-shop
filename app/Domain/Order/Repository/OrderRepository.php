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

namespace App\Domain\Order\Repository;

use App\Domain\Order\Entity\OrderEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Order\Order;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * @extends IRepository<Order>
 */
final class OrderRepository extends IRepository
{
    public function __construct(protected readonly Order $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->with(['member', 'items', 'address', 'packages'])
            ->when(isset($params['order_no']), static fn (Builder $q) => $q->where('order_no', 'like', '%' . $params['order_no'] . '%'))
            ->when(isset($params['pay_no']), static fn (Builder $q) => $q->where('pay_no', 'like', '%' . $params['pay_no'] . '%'))
            ->when(isset($params['member_id']), static fn (Builder $q) => $q->where('member_id', (int) $params['member_id']))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['pay_status']), static fn (Builder $q) => $q->where('pay_status', $params['pay_status']))
            ->when(isset($params['member_phone']), static fn (Builder $q) => $q->whereHas('member', static function (Builder $memberQuery) use ($params) {
                $memberQuery->where('phone', 'like', '%' . $params['member_phone'] . '%');
            }))
            ->when(isset($params['product_name']), static fn (Builder $q) => $q->whereHas('items', static function (Builder $itemQuery) use ($params) {
                $itemQuery->where('product_name', 'like', '%' . $params['product_name'] . '%');
            }))
            ->when(isset($params['start_date']), static fn (Builder $q) => $q->whereDate('created_at', '>=', $params['start_date']))
            ->when(isset($params['end_date']), static fn (Builder $q) => $q->whereDate('created_at', '<=', $params['end_date']))
            ->orderByDesc('id');
    }

    public function handleItems(Collection $items): Collection
    {
        return $items->map(static fn (Order $order) => $order->loads(['member', 'items', 'address', 'packages']));
    }

    public function stats(array $filters): array
    {
        $query = $this->perQuery($this->getQuery(), $filters);
        $total = (clone $query)->count();
        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'total' => $total,
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'paid' => (int) ($statusCounts['paid'] ?? 0),
            'shipped' => (int) ($statusCounts['shipped'] ?? 0),
            'completed' => (int) ($statusCounts['completed'] ?? 0),
        ];
    }

    public function findDetail(int $id): ?array
    {
        /** @var null|Order $order */
        $order = $this->getQuery()
            ->with(['member', 'items', 'address', 'packages'])
            ->find($id);

        return $order ? $order->loads(['member', 'items', 'address', 'packages']) : null;
    }

    /**
     * 创建订单.
     */
    public function save(OrderEntity $entity): void
    {
        $items = array_map(static function ($item) {return $item->toArray(); }, $entity->getItems());

        $model = $this->model->newQuery()->create($entity->toArray());

        $model->items()->createMany($items);
        $model->address()->create($entity->getAddress()->toArray());

        $model->refresh();
    }

    /**
     * 发货.
     */
    public function ship(OrderEntity $entity): void
    {
        $order = $this->getQuery()->whereKey($entity->getId())->lockForUpdate()->first();
        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        $order->status = $entity->getStatus();
        $order->shipping_status = $entity->getShippingStatus();
        $order->package_count = $entity->getPackageCount();
        $order->save();

        $order->packages()->createMany($entity->getShipEntity()->getPackagePayloads());
    }

    /**
     * 取消订单.
     */
    public function cancel(OrderEntity $entity): void
    {
        $order = $this->getQuery()->whereKey($entity->getId())->lockForUpdate()->first();
        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        $order->status = $entity->getStatus();
        $order->shipping_status = $entity->getShippingStatus();
        $order->pay_status = $entity->getPayStatus();
        $order->save();
    }

    /**
     * 通过ID获取订单.
     */
    public function getEntityById(int $id): ?OrderEntity
    {
        /** @var null|Order $order */
        $order = $this->getQuery()->whereKey($id)->first();
        return $order ? OrderEntity::fromModel($order) : null;
    }
}
