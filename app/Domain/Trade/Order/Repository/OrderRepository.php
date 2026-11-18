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
use App\Domain\Trade\Order\Mapper\OrderExportMapper;
use App\Domain\Trade\Order\ValueObject\OrderLogValue;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Error\OrderError;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;
use App\Infrastructure\Model\Order\OrderLog;
use Carbon\Carbon;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;

/** 业务方法。 *.
 * @extends IRepository<Order>
 */
final class OrderRepository extends IRepository
{
    public function __construct(protected readonly Order $model) {}

    public function recordLog(int $orderId, OrderLogValue $log): void
    {
        OrderLog::create($log->toArray($orderId));
    }

    /** 后台列表只查询展示和操作所需字段，完整关联由详情接口加载。 */
    public function page(array $params = [], ?int $page = null, ?int $pageSize = null): array
    {
        $query = $this->perQuery($this->getQuery(), $params)
            ->select([
                'id', 'order_no', 'member_id', 'status', 'pay_amount', 'pay_status',
                'pay_no', 'pay_method', 'shipping_status', 'expire_time', 'created_at',
            ])
            ->with([
                'member',
                'items:order_id,product_name,sku_name,product_image,quantity',
                'address:order_id,name,phone',
            ]);

        $result = $query->paginate(
            perPage: $pageSize,
            pageName: self::PER_PAGE_PARAM_NAME,
            page: $page,
        );
        foreach ($result->items() as $order) {
            $order->member?->setAppends([]);
        }

        return $this->handlePage($result);
    }

    /** 业务方法。 */
    public function stats(array $filters): array
    {
        $query = $this->perQuery($this->getQuery(), $filters);
        $total = (clone $query)->count();
        $statusCounts = (clone $query)
            ->reorder()
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

    /** 业务方法。 */
    public function findDetail(int $id): ?array
    {
        /** @var null|Order $order */
        $order = $this->getQuery()
            ->with(['member', 'items', 'address', 'packages'])
            ->find($id);

        return $order?->loads(['member', 'items', 'address', 'packages']);
    }

    /** 业务方法。 */
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

    /** 业务方法。 */
    public function ship(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw OrderError::NOT_FOUND->exception();
        }

        $order->ship($entity);

        $order->packages()->createMany($entity->getShipEntity()->getPackagePayloads());
    }

    /** 业务方法。 */
    public function cancel(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw OrderError::NOT_FOUND->exception();
        }
        $order->cancel($entity);
    }

    /** 业务方法。 */
    public function complete(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw OrderError::NOT_FOUND->exception();
        }

        $order->complete($entity);
    }

    /** 业务方法。 */
    public function paid(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw OrderError::NOT_FOUND->exception();
        }

        $order->paid($entity);
    }

    /** 业务方法。 */
    public function findById(int $id): ?Order
    {
        /** @var null|Order $order */
        $order = parent::findById($id);

        if (! $order) {
            throw OrderError::NOT_FOUND->exception();
        }

        return $order;
    }

    /** 业务方法。 */
    public function findByOrderNo(string $orderNo): ?Order
    {
        /** @var null|Order $order */
        $order = $this->model::where('order_no', $orderNo)->first();

        if (! $order) {
            throw OrderError::NOT_FOUND->exception();
        }

        return $order;
    }

    /** 业务方法。 */
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

    /** 业务方法。 */
    public function findMemberOrderDetail(int $memberId, string $orderNo): ?Order
    {
        return $this->getQuery()
            ->where('member_id', $memberId)
            ->where('order_no', $orderNo)
            ->with(['items', 'address', 'packages', 'logs'])
            ->first();
    }

    /** 业务方法。 */
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

    /** 业务方法。 */
    /** @return Collection<int, Order> */
    public function findExpiredPendingOrders(int $limit = 200): Collection
    {
        return $this->getQuery()
            ->where('status', OrderStatus::PENDING->value)
            ->where('expire_time', '<=', Carbon::now())
            ->with('items')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Order> */
    public function findAutoConfirmableOrders(Carbon $shippedBefore, int $limit = 200): Collection
    {
        return $this->getQuery()
            ->where('status', OrderStatus::SHIPPED->value)
            ->whereHas('packages', static function (Builder $query) use ($shippedBefore) {
                $query->whereNotNull('shipped_at')
                    ->where('shipped_at', '<=', $shippedBefore);
            })
            ->whereDoesntHave('packages', static function (Builder $query) use ($shippedBefore) {
                $query->whereNull('shipped_at')
                    ->orWhere('shipped_at', '>', $shippedBefore);
            })
            ->with(['items', 'address', 'packages'])
            ->limit($limit)
            ->get();
    }

    /** 业务方法。 */
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

    /** 业务方法。 */
    public function getExportData(array $params): iterable
    {
        $query = $this->perQuery($this->getQuery()->with(['member', 'items', 'address']), $params);

        foreach ($query->cursor() as $order) {
            // cursor() 不保证关联已经完成预加载；显式加载后再转数组，确保
            // ExportColumn 的 member.* / address.* 点号路径可以取到值。
            $orderData = $order->loads(['member', 'address']);
            $items = $order->items;

            if ($items->isEmpty()) {
                // 业务说明。
                yield $orderData;
                continue;
            }

            // 业务说明。
            foreach ($items as $item) {
                yield OrderExportMapper::toRow($orderData, $item->toArray());
            }
        }
    }

    /** 业务方法。 */
    public function findOrderItemForAfterSale(int $memberId, int $orderId, int $orderItemId): ?OrderItem
    {
        return OrderItem::query()
            ->where('id', $orderItemId)
            ->where('order_id', $orderId)
            ->whereHas('order', static function (Builder $query) use ($memberId) {
                $query->where('member_id', $memberId);
            })
            ->with('order')
            ->first();
    }

    /**
     * 获取订单信息，用于更新.
     */
    public function findByOrderNoForUpdate(string $orderNo): ?Order
    {
        return $this->getQuery()->where('order_no', $orderNo)->lockForUpdate()->first();
    }

    /**
     * 根据订单号获取订单信息.
     */
    public function findExistingByOrderNo(string $orderNo): ?Order
    {
        return $this->getQuery()->where('order_no', $orderNo)->first();
    }

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
