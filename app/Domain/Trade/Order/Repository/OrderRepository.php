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
 * 订单仓储.
 *
 * 负责订单的持久化操作，包括创建、更新、查询等。
 * 支持订单状态流转：待付款 → 已付款 → 已发货 → 已完成/已取消。
 *
 * @extends IRepository<Order>
 */
final class OrderRepository extends IRepository
{
    public function __construct(protected readonly Order $model) {}

    /**
     * 处理列表项，加载关联数据.
     *
     * @param Collection $items 订单集合
     * @return Collection 加载关联后的订单集合
     */
    public function handleItems(Collection $items): Collection
    {
        return $items->map(static fn (Order $order) => $order->loads(['member', 'items', 'address', 'packages']));
    }

    /**
     * 获取订单统计数据.
     *
     * @param array $filters 筛选条件
     * @return array 各状态订单数量统计
     */
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

    /**
     * 获取订单详情.
     *
     * @param int $id 订单 ID
     * @return array|null 订单详情数组，不存在返回 null
     */
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
     *
     * 同时创建订单主表、订单商品明细和收货地址。
     *
     * @param OrderEntity $entity 订单实体
     * @return OrderEntity 包含 ID 和订单号的实体
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
     * 订单发货.
     *
     * 更新订单状态为已发货，并创建物流包裹记录。
     * 使用行锁防止并发操作。
     *
     * @param OrderEntity $entity 包含发货信息的订单实体
     * @throws \RuntimeException 订单不存在时抛出
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
     *
     * 更新订单状态为已取消。使用行锁防止并发操作。
     *
     * @param OrderEntity $entity 订单实体
     * @throws \RuntimeException 订单不存在时抛出
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
     *
     * 更新订单状态为已完成。使用行锁防止并发操作。
     *
     * @param OrderEntity $entity 订单实体
     * @throws \RuntimeException 订单不存在时抛出
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

    /**
     * 标记订单已支付.
     *
     * 更新订单支付状态和支付信息。使用行锁防止并发操作。
     *
     * @param OrderEntity $entity 包含支付信息的订单实体
     * @throws \RuntimeException 订单不存在时抛出
     */
    public function paid(OrderEntity $entity): void
    {
        /** @var null|Order $order */
        $order = $this->findByIdForLock($entity->getId());

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        $order->paid($entity);
    }

    /**
     * 根据 ID 查找订单.
     *
     * @param int $id 订单 ID
     * @return Order|null 订单模型
     * @throws \RuntimeException 订单不存在时抛出
     */
    public function findById(int $id): ?Order
    {
        /** @var null|Order $order */
        $order = parent::findById($id);

        if (! $order) {
            throw new \RuntimeException('订单不存在');
        }

        return $order;
    }

    /**
     * 根据订单号查找订单.
     *
     * @param string $orderNo 订单号
     * @return Order|null 订单模型
     * @throws \RuntimeException 订单不存在时抛出
     */
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
     *
     * @param int $memberId 会员 ID
     * @param string $status 订单状态筛选（all/pending/paid/shipped/completed/after_sale）
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginatorInterface 分页结果
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
     *
     * @param int $memberId 会员 ID
     * @param string $orderNo 订单号
     * @return Order|null 订单模型，不存在或不属于该会员返回 null
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
     * 统计会员各状态订单数量.
     *
     * @param int $memberId 会员 ID
     * @return array [待付款, 待发货, 待收货, 已完成, 售后] 数量数组
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
     * 查询已超时的待付款订单.
     *
     * 用于定时任务自动关闭超时订单。
     *
     * @param int $limit 最大查询数量
     * @return \Hyperf\Database\Model\Collection<int, Order> 超时订单集合
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

    /**
     * 处理搜索条件.
     *
     * 支持的筛选条件：
     * - order_no: 订单号（模糊匹配）
     * - pay_no: 支付单号（模糊匹配）
     * - member_id: 会员 ID
     * - status: 订单状态
     * - pay_status: 支付状态
     * - member_phone: 收货人手机号（模糊匹配）
     * - product_name: 商品名称（模糊匹配）
     * - start_date/end_date: 创建时间范围
     *
     * @param Builder $query 查询构建器
     * @param array $params 搜索参数
     * @return Builder 处理后的查询构建器
     */
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
     * 导出数据提供者.
     *
     * 将订单数据展开为商品明细行，每个订单商品生成一行数据。
     * 使用游标查询避免内存溢出。
     *
     * @param array $params 筛选参数
     * @return iterable 订单数据生成器
     */
    public function getExportData(array $params): iterable
    {
        $query = $this->perQuery($this->getQuery()->with(['member', 'items', 'address']), $params);

        foreach ($query->cursor() as $order) {
            $orderData = $order->toArray();
            $items = $order->items;

            if ($items->isEmpty()) {
                // 无商品明细时输出一行订单信息
                yield $orderData;
                continue;
            }

            // 每个商品明细展开为一行，合并订单级字段
            foreach ($items as $item) {
                yield array_merge($orderData, $item->toArray());
            }
        }
    }

    /**
     * 根据状态字符串应用查询条件.
     *
     * @param Builder $query 查询构建器
     * @param string $status 状态字符串
     * @return Builder 应用条件后的查询构建器
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
