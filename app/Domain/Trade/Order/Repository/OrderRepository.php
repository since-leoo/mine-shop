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

namespace App\Domain\Trade\Order\Repository;

use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Order\Order;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
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

        $entity->setId($model->id);
        $entity->setOrderNo($model->order_no);

        return $entity;
    }

    /**
     * 发货.
     */
    public function ship(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        $order->ship($entity);

        $order->packages()->createMany($entity->getShipEntity()->getPackagePayloads());
    }

    /**
     * 取消订单.
     */
    public function cancel(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }
        $order->cancel($entity);
    }

    /**
     * 确认收货（完成订单）.
     */
    public function complete(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        $order->complete($entity);
    }

    public function paid(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        $order->paid($entity);
    }

    public function findById(int $id): ?Order
    {
        /** @var null|Order $order */
        $order = parent::findById($id);

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        return $order;
    }

    public function findByOrderNo(string $orderNo): ?Order
    {
        /** @var null|Order $order */
        $order = $this->model::where('order_no', $orderNo)->first();

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        return $order;
    }

    /**
     * 会员订单分页列表.
     */
    public function paginateByMember(
        int $memberId,
        string $status = 'all',
        int $page = 1,
        int $pageSize = 10
    ): LengthAwarePaginatorInterface {
        $query = $this->getQuery()
            ->where('member_id', $memberId)
            ->with(['items', 'address']);

        $query = $this->applyStatusScope($query, $status);

        return $query
            ->orderByDesc('created_at')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 会员订单详情（按订单号）.
     */
    public function findMemberOrderDetail(int $memberId, string $orderNo): ?Order
    {
        return $this->getQuery()
            ->where('member_id', $memberId)
            ->where('order_no', $orderNo)
            ->with(['items', 'address', 'packages', 'logs'])
            ->first();
    }

    /**
     * 统计指定会员在特定状态集合下的订单数量.
     */
    public function countByMemberAndStatuses(int $memberId): array
    {
        $where = static fn (Builder $query) => $query->where('member_id', $memberId);
        $pending = $this->model->pendingStatus()->where($where)->count();
        $paid = $this->model->paidStatus()->where($where)->count();
        $shipped = $this->model->shippedStatus()->where($where)->count();
        $completed = $this->model->completedStatus()->where($where)->count();
        $afterSale = $this->model->afterSaleStatus()->where($where)->count();

        return [$pending, $paid, $shipped, $completed, $afterSale];
    }

    /**
     * 查询已超时的待付款订单（expire_time <= now）.
     *
     * @return \Hyperf\Database\Model\Collection<int, Order>
     */
    public function findExpiredPendingOrders(int $limit = 200): \Hyperf\Database\Model\Collection
    {
        return $this->getQuery()
            ->where('status', OrderStatus::PENDING->value)
            ->where('expire_time', '<=', Carbon::now())
            ->with('items')
            ->limit($limit)
            ->get();
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

    /**
     * 根据状态字符串应用对应的 scope.
     */
    private function applyStatusScope(Builder $query, string $status): Builder
    {
        return match ($status) {
            'pending' => $query->where('status', OrderStatus::PENDING->value),
            'paid' => $query->where('status', OrderStatus::PAID->value),
            'shipped' => $query->whereIn('status', [OrderStatus::PARTIAL_SHIPPED->value, OrderStatus::SHIPPED->value]),
            'completed' => $query->where('status', OrderStatus::COMPLETED->value),
            'after_sale' => $query->whereIn('status', [OrderStatus::REFUNDED->value, OrderStatus::CANCELLED->value]),
            default => $query,
        };
    }
}
