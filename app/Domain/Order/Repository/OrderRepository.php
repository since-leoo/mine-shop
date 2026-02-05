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

        return $order?->loads(['member', 'items', 'address', 'packages']);
    }

    /**
     * 创建订单.
     */
    public function save(OrderEntity $entity): OrderEntity
    {
        $items = array_map(static function ($item) {return $item->toArray(); }, $entity->getItems());

        $model = $this->model->newQuery()->create($entity->toArray());

        $model->items()->createMany($items);
        $model->address()->create($entity->getAddress()->toArray());

        $model->refresh();

        $entity->setOrderNo($model->order_no);

        return $entity;
    }

    /**
     * 发货.
     */
    public function ship(OrderEntity $entity): void
    {
        /** @var ?Order $order */
        $order = $this->findByIdForLock($entity->getId());

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
        /** @var Order $order */
        $order = $this->model::whereKey($entity->getId())->lockForUpdate()->first();

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        $order->status = $entity->getStatus();
        $order->shipping_status = $entity->getShippingStatus();
        $order->pay_status = $entity->getPayStatus();
        $order->save();
    }

    public function paid(OrderEntity $entity): void
    {
        /** @var Order $order */
        $order = $this->model::whereKey($entity->getId())->lockForUpdate()->first();

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        $order->status = $entity->getStatus();
        $order->pay_status = $entity->getPayStatus();
        $order->pay_no = $entity->getPayNo();
        $order->pay_time = $entity->getPayTime();
        $order->pay_method = $entity->getPayMethod();
        $order->save();
    }

    public function findByOrderNo(string $orderNo): ?Order
    {
        return $this->model::where('order_no', $orderNo)->first();
    }

    /**
     * 统计指定会员在特定状态集合下的订单数量.
     */
    public function countByMemberAndStatuses(int $memberId): array
    {
        $where = static fn (Builder $query) => $query->where('member_id', $memberId);
        $pending = $this->model->pending()->where($where)->count();
        $paid = $this->model->paid()->where($where)->count();
        $shipped = $this->model->shipped()->where($where)->count();
        $completed = $this->model->completed()->where($where)->count();
        $afterSale = $this->model->afterSale()->where($where)->count();

        return [$pending, $paid, $shipped, $completed, $afterSale];
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->with(['items', 'address'])
            ->when(! empty($params['order_no']), static fn (Builder $q) => $q->where('order_no', 'like', '%' . $params['order_no'] . '%'))
            ->when(! empty($params['pay_no']), static fn (Builder $q) => $q->where('pay_no', 'like', '%' . $params['pay_no'] . '%'))
            ->when(! empty($params['member_id']), static fn (Builder $q) => $q->where('member_id', (int) $params['member_id']))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(! empty($params['pay_status']), static fn (Builder $q) => $q->where('pay_status', $params['pay_status']))
            ->when(! empty($params['member_phone']), static fn (Builder $q) => $q->whereHas('address', static function (Builder $memberQuery) use ($params) {
                $memberQuery->where('phone', 'like', '%' . $params['member_phone'] . '%');
            }))
            ->when(! empty($params['product_name']), static fn (Builder $q) => $q->whereHas('items', static function (Builder $itemQuery) use ($params) {
                $itemQuery->where('product_name', 'like', '%' . $params['product_name'] . '%');
            }))
            ->when(! empty($params['start_date']), static fn (Builder $q) => $q->whereDate('created_at', '>=', $params['start_date']))
            ->when(! empty($params['end_date']), static fn (Builder $q) => $q->whereDate('created_at', '<=', $params['end_date']))
            ->orderByDesc('id');
    }
}
