<?php

declare(strict_types=1);

namespace App\Domain\Order\Repository;

use App\Domain\Order\Entity\OrderDraftEntity;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Enum\OrderStatus;
use App\Domain\Order\Enum\PaymentStatus;
use App\Domain\Order\Enum\ShippingStatus;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderLog;
use App\Infrastructure\Model\Order\OrderPackage;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use RuntimeException;

/**
 * @extends IRepository<Order>
 */
final class OrderRepository extends IRepository
{
    public function __construct(protected readonly Order $model) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->with([
                'member:id,nickname,phone',
                'items:id,order_id,product_name,sku_name,product_image,unit_price,quantity,total_price',
                'address:id,order_id,receiver_name,receiver_phone,province,city,district,detail,full_address',
                'packages:id,order_id,package_no,express_company,express_no,status,shipped_at,delivered_at',
            ])
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
        return $items->map(fn (Order $order) => $this->transformOrder($order));
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
        /** @var Order $order */
        $order = $this->getQuery()
            ->with([
                'member:id,nickname,phone',
                'items:id,order_id,product_name,sku_name,product_image,unit_price,quantity,total_price',
                'address:id,order_id,receiver_name,receiver_phone,province,city,district,detail,full_address',
                'packages:id,order_id,package_no,express_company,express_no,status,shipped_at,delivered_at',
            ])
            ->find($id);

        return $order ? $this->transformOrder($order) : null;
    }

    /**
     * 创建订单
     *
     * @param OrderDraftEntity $draft
     * @return OrderEntity
     */
    public function createFromDraft(OrderDraftEntity $draft): OrderEntity
    {
        $payload = $draft->toArray();

        $model = $this->model->newQuery()->create($payload['order']);

        if ($payload['items'] !== []) {
            $model->items()->createMany($payload['items']);
        }

        if ($payload['address']) {
            $model->address()->create($payload['address']);
        }

        $model->refresh();
        return OrderEntity::fromModel($model);
    }

    /**
     * 发货
     *
     * @param OrderEntity $entity
     */
    public function ship(OrderEntity $entity): void
    {
        $order = $this->getQuery()->whereKey($entity->getId())->lockForUpdate()->first();
        if (! $order) {
            throw new RuntimeException('订单不存在');
        }

        $order->status = $entity->getStatus();
        $order->shipping_status = $entity->getShippingStatus();
        $order->package_count = $entity->getPackageCount();
        $order->save();

        $order->packages()->createMany($entity->getShipEntity()->getPackages());
    }

    /**
     * 取消订单
     *
     * @param OrderEntity $entity
     */
    public function cancel(OrderEntity $entity): void
    {
        $order = $this->getQuery()->whereKey($entity->getId())->lockForUpdate()->first();
        if (! $order) {
            throw new RuntimeException('订单不存在');
        }

        $order->status = $entity->getStatus();
        $order->shipping_status = $entity->getShippingStatus();
        $order->pay_status = $entity->getPayStatus();
        $order->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'member_id' => $order->member_id,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'goods_amount' => $order->goods_amount,
            'shipping_fee' => $order->shipping_fee,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'pay_amount' => $order->pay_amount,
            'pay_status' => $order->pay_status,
            'pay_time' => $order->pay_time?->toDateTimeString(),
            'pay_no' => $order->pay_no,
            'pay_method' => $order->pay_method,
            'buyer_remark' => $order->buyer_remark,
            'seller_remark' => $order->seller_remark,
            'shipping_status' => $order->shipping_status,
            'package_count' => $order->package_count,
            'expire_time' => $order->expire_time?->toDateTimeString(),
            'created_at' => $order->created_at?->toDateTimeString(),
            'updated_at' => $order->updated_at?->toDateTimeString(),
            'member' => $order->member
                ? [
                    'id' => $order->member->id,
                    'nickname' => $order->member->nickname,
                    'phone' => $order->member->phone,
                ]
                : null,
            'address' => $order->address
                ? [
                    'name' => $order->address->receiver_name,
                    'phone' => $order->address->receiver_phone,
                    'province' => $order->address->province,
                    'city' => $order->address->city,
                    'district' => $order->address->district,
                    'address' => $order->address->detail,
                    'full_address' => $order->address->full_address,
                ]
                : null,
            'items' => $order->items->map(static fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'sku_name' => $item->sku_name,
                'product_image' => $item->product_image,
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
                'total_amount' => $item->total_price,
            ])->toArray(),
            'packages' => $order->packages->map(static fn (OrderPackage $package) => [
                'id' => $package->id,
                'package_no' => $package->package_no,
                'shipping_company' => $package->express_company,
                'shipping_no' => $package->express_no,
                'status' => $package->status,
                'shipped_at' => $package->shipped_at?->toDateTimeString(),
                'delivered_at' => $package->delivered_at?->toDateTimeString(),
            ])->toArray(),
        ];
    }

    public function findAggregate(int $id): ?OrderEntity
    {
        $order = $this->getQuery()->whereKey($id)->first();
        return $order ? OrderEntity::fromModel($order) : null;
    }
}
