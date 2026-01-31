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
use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderAddress;
use App\Infrastructure\Model\Order\OrderPackage;
use Carbon\Carbon;
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
        /** @var null|Order $order */
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
     * 创建订单.
     */
    public function save(OrderEntity $draft): void
    {
        $items = array_map(static function ($item) {return $item->toArray(); }, $draft->getItems());

        $model = $this->model->newQuery()->create($draft->toArray());

        $model->items()->createMany($items);
        $model->address()->create($draft->getAddress()->toArray());

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

    /**
     * @return array<string, mixed>
     */
    private function transformOrder(Order $order): array
    {
        /** @var null|Member $member */
        $member = $order->member;
        /** @var null|OrderAddress $address */
        $address = $order->address;
        $payTime = $order->pay_time;
        $expireTime = $order->expire_time;
        $createdAt = $order->created_at;
        $updatedAt = $order->updated_at;

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
            'pay_time' => $payTime instanceof Carbon ? $payTime->toDateTimeString() : null,
            'pay_no' => $order->pay_no,
            'pay_method' => $order->pay_method,
            'buyer_remark' => $order->buyer_remark,
            'seller_remark' => $order->seller_remark,
            'shipping_status' => $order->shipping_status,
            'package_count' => $order->package_count,
            'expire_time' => $expireTime instanceof Carbon ? $expireTime->toDateTimeString() : null,
            'created_at' => $order->created_at->toDateTimeString(),
            'updated_at' => $order->updated_at->toDateTimeString(),
            'member' => $member instanceof Member ? [
                'id' => $member->id,
                'nickname' => $member->nickname,
                'phone' => $member->phone,
            ] : null,
            'address' => $address instanceof OrderAddress ? [
                'name' => $address->receiver_name,
                'phone' => $address->receiver_phone,
                'province' => $address->province,
                'city' => $address->city,
                'district' => $address->district,
                'address' => $address->detail,
                'full_address' => $address->full_address,
            ] : null,
            'items' => $order->items->map(static fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'sku_name' => $item->sku_name,
                'product_image' => $item->product_image,
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
                'total_amount' => $item->total_price,
            ])->toArray(),
            'packages' => $order->packages->map(static function (OrderPackage $package) {
                $shippedAt = $package->shipped_at;
                $deliveredAt = $package->delivered_at;

                return [
                    'id' => $package->id,
                    'package_no' => $package->package_no,
                    'shipping_company' => $package->express_company,
                    'shipping_no' => $package->express_no,
                    'status' => $package->status,
                    'shipped_at' => $shippedAt instanceof Carbon ? $shippedAt->toDateTimeString() : null,
                    'delivered_at' => $deliveredAt instanceof Carbon ? $deliveredAt->toDateTimeString() : null,
                ];
            })->toArray(),
        ];
    }
}
