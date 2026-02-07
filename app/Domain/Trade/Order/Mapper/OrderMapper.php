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

namespace App\Domain\Trade\Order\Mapper;

use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\Entity\OrderShipEntity;
use App\Domain\Trade\Order\ValueObject\OrderAddressValue;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderAddress;
use App\Infrastructure\Model\Order\OrderItem;
use Carbon\Carbon;

/**
 * 订单映射器类，用于将数据库模型转换为领域实体对象。
 */
class OrderMapper
{
    /**
     * 将订单模型转换为订单实体对象。
     *
     * @param Order $model 订单数据库模型对象
     * @return OrderEntity 转换后的订单实体对象
     */
    public static function fromModel(Order $model): OrderEntity
    {
        $entity = new OrderEntity();
        $entity->setId($model->id);
        $entity->setOrderNo($model->order_no);
        $entity->setMemberId($model->member_id);
        $entity->setOrderType($model->order_type);
        $entity->setStatus($model->status);
        $entity->setGoodsAmount($model->goods_amount);
        $entity->setShippingFee($model->shipping_fee);
        $entity->setDiscountAmount($model->discount_amount);
        $entity->setTotalAmount($model->total_amount);
        $entity->setPayAmount((int) $model->pay_amount);
        $entity->setPayTime($model->pay_time ? Carbon::parse($model->pay_time) : null);
        $entity->setPayNo((string) $model->pay_no);
        $entity->setPayMethod((string) $model->pay_method);
        $entity->setBuyerRemark((string) $model->buyer_remark);
        $entity->setSellerRemark((string) $model->seller_remark);
        $entity->setExpireTime($model->expire_time ? Carbon::parse($model->expire_time) : null);
        $entity->setShippingStatus($model->shipping_status);
        $entity->setPayStatus($model->pay_status);
        $entity->setPackageCount($model->package_count ?? 0);

        // 获取订单地址信息并映射到实体对象
        /** @var OrderAddress $address */
        $address = $model->address()->first();

        if ($address) {
            $addressEntity = new OrderAddressValue();
            $addressEntity->setProvince($address->province);
            $addressEntity->setCity($address->city);
            $addressEntity->setDistrict($address->district);
            $addressEntity->setDetail($address->detail);
            $addressEntity->setFullAddress($address->full_address);
            $addressEntity->setReceiverName($address->name);
            $addressEntity->setReceiverPhone($address->phone);
            $entity->setAddress($addressEntity);
        }

        // 获取订单项信息并映射到实体对象
        $items = $model->items()->get();

        if ($items->isNotEmpty()) {
            array_map(static function (OrderItem $item) use ($entity) {
                $itemEntity = new OrderItemEntity();
                $itemEntity->setProductId((int) $item->product_id);
                $itemEntity->setProductImage((string) $item->product_image);
                $itemEntity->setQuantity($item->quantity);
                $itemEntity->setSkuId($item->sku_id);
                $itemEntity->setSkuName($item->sku_name);
                $itemEntity->setProductName($item->product_name);
                $itemEntity->setSpecValues($item->spec_values);
                $itemEntity->setTotalPrice($item->total_price);
                $itemEntity->setUnitPrice($item->unit_price);
                $entity->setItems($itemEntity);
            }, $items->all());
        }

        // 获取订单包裹信息并映射到实体对象
        $package = $model->packages()->get();
        if ($package->isNotEmpty()) {
            $shipEntity = new OrderShipEntity();
            $shipEntity->setPackages($package->toArray());
            $entity->setShipEntity($shipEntity);
        }

        return $entity;
    }

    /**
     * 创建一个新的订单实体对象。
     *
     * @return OrderEntity 新的订单实体对象
     */
    public static function getNewEntity(): OrderEntity
    {
        return new OrderEntity();
    }
}
