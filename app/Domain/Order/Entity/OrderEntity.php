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

namespace App\Domain\Order\Entity;

use App\Domain\Order\Enum\OrderStatus;
use App\Domain\Order\Enum\PaymentStatus;
use App\Domain\Order\Enum\ShippingStatus;
use App\Domain\Order\Trait\OrderSettingsTrait;
use App\Domain\Order\ValueObject\OrderAddressValue;
use App\Domain\Order\ValueObject\OrderPriceValue;
use App\Infrastructure\Model\Order\Order;
use Carbon\Carbon;

final class OrderEntity
{
    use OrderSettingsTrait;

    private int $id = 0;

    private string $orderNo = '';

    private int $memberId = 0;

    private string $orderType = 'normal';

    private float $goodsAmount = 0;

    private float $shippingFee = 0;

    private float $discountAmount = 0;

    private float $totalAmount = 0;

    private float $payAmount = 0;

    private string $payNo = '';

    private string $payMethod = '';

    private string $buyerRemark = '';

    private string $sellerRemark = '';

    private int $packageCount = 0;

    private ?Carbon $payTime = null;

    private ?Carbon $expireTime = null;

    private string $payStatus = PaymentStatus::PENDING->value;

    private string $status = OrderStatus::PENDING->value;

    private string $shippingStatus = ShippingStatus::PENDING->value;

    private ?OrderShipEntity $shipEntity = null;

    /**
     * @var OrderItemEntity[]
     */
    private array $items = [];

    private ?OrderAddressValue $address = null;

    private ?OrderPriceValue $priceDetail = null;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): void
    {
        $this->orderNo = $orderNo;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setMemberId(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function setOrderType(string $orderType): void
    {
        $this->orderType = $orderType;
    }

    public function getOrderType(): string
    {
        return $this->orderType;
    }

    public function getGoodsAmount(): float
    {
        return $this->goodsAmount;
    }

    public function setGoodsAmount(float $goodsAmount): void
    {
        $this->goodsAmount = $goodsAmount;
    }

    public function getShippingFee(): float
    {
        return $this->shippingFee;
    }

    public function setShippingFee(float $shippingFee): void
    {
        $this->shippingFee = $shippingFee;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(float $discountAmount): void
    {
        $this->discountAmount = $discountAmount;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getPayAmount(): float
    {
        return $this->payAmount;
    }

    public function setPayAmount(float $payAmount): void
    {
        $this->payAmount = $payAmount;
    }

    public function getPayTime(): ?Carbon
    {
        return $this->payTime;
    }

    public function setPayTime(?Carbon $payTime): void
    {
        $this->payTime = $payTime;
    }

    public function getPayNo(): string
    {
        return $this->payNo;
    }

    public function setPayNo(string $payNo): void
    {
        $this->payNo = $payNo;
    }

    public function getPayMethod(): string
    {
        return $this->payMethod;
    }

    public function setPayMethod(string $payMethod): void
    {
        $this->payMethod = $payMethod;
    }

    public function getBuyerRemark(): string
    {
        return $this->buyerRemark;
    }

    public function setBuyerRemark(string $buyerRemark): void
    {
        $this->buyerRemark = $buyerRemark;
    }

    public function getSellerRemark(): string
    {
        return $this->sellerRemark;
    }

    public function setSellerRemark(string $sellerRemark): void
    {
        $this->sellerRemark = $sellerRemark;
    }

    public function getExpireTime(): ?Carbon
    {
        return $this->expireTime;
    }

    public function setExpireTime(?Carbon $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    public function getStatus(): string
    {
        return $this->status ?: OrderStatus::PENDING->value;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getShippingStatus(): string
    {
        return $this->shippingStatus ?: ShippingStatus::PENDING->value;
    }

    public function setShippingStatus(string $shippingStatus): void
    {
        $this->shippingStatus = $shippingStatus;
    }

    public function getPayStatus(): string
    {
        return $this->payStatus ?: PaymentStatus::PENDING->value;
    }

    public function setPayStatus(string $payStatus): void
    {
        $this->payStatus = $payStatus;
    }

    public function setShipEntity(OrderShipEntity $orderShipEntity): void
    {
        $this->shipEntity = $orderShipEntity;
    }

    public function getShipEntity(): ?OrderShipEntity
    {
        return $this->shipEntity;
    }

    public function getPackageCount(): int
    {
        return $this->packageCount;
    }

    public function setPackageCount(int $packageCount): void
    {
        $this->packageCount = max(0, $packageCount);
    }

    public function setItems(OrderItemEntity $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return OrderItemEntity[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function setAddress(OrderAddressValue $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?OrderAddressValue
    {
        return $this->address;
    }

    public function setPriceDetail(OrderPriceValue $priceDetail): void
    {
        $this->priceDetail = $priceDetail;
    }

    public function getPriceDetail(): ?OrderPriceValue
    {
        return $this->priceDetail;
    }

    public static function fromModel(Order $model): self
    {
        $entity = new self();
        $entity->setId((int) $model->id);
        $entity->setOrderNo((string) $model->order_no);
        $entity->setMemberId((int) $model->member_id);
        $entity->setOrderType((string) $model->order_type);
        $entity->setStatus((string) $model->status);
        $entity->setGoodsAmount((float) $model->goods_amount);
        $entity->setShippingFee((float) $model->shipping_fee);
        $entity->setDiscountAmount((float) $model->discount_amount);
        $entity->setTotalAmount((float) $model->total_amount);
        $entity->setPayAmount((float) $model->pay_amount);
        $entity->setPayTime($model->pay_time ? Carbon::parse($model->pay_time) : null);
        $entity->setPayNo((string) $model->pay_no);
        $entity->setPayMethod((string) $model->pay_method);
        $entity->setBuyerRemark((string) $model->buyer_remark);
        $entity->setSellerRemark((string) $model->seller_remark);
        $entity->setExpireTime($model->expire_time ? Carbon::parse($model->expire_time) : null);
        $entity->setShippingStatus((string) $model->shipping_status);
        $entity->setPayStatus((string) $model->pay_status);
        $entity->setPackageCount((int) ($model->package_count ?? 0));
        return $entity;
    }

    /**
     * 发货.
     */
    public function ship(): self
    {
        if (! \in_array($this->getStatus(), [OrderStatus::PAID->value, OrderStatus::PARTIAL_SHIPPED->value], true)) {
            throw new \RuntimeException('当前订单状态不可发货');
        }

        $packages = $this->getShipEntity()->getPackages();
        if (empty($packages)) {
            throw new \RuntimeException('请至少提供一个包裹信息');
        }

        $this->setStatus(OrderStatus::SHIPPED->value);
        $this->setShippingStatus(ShippingStatus::SHIPPED->value);
        $this->setPackageCount($this->getPackageCount() + \count($packages));

        return $this;
    }

    /**
     * 取消订单.
     */
    public function cancel(): void
    {
        if (! \in_array($this->getStatus(), [OrderStatus::PENDING->value, OrderStatus::PAID->value], true)) {
            throw new \RuntimeException('当前订单状态不可取消');
        }

        $this->setStatus(OrderStatus::CANCELLED->value);
        $this->setShippingStatus(ShippingStatus::PENDING->value);
        $this->setPayStatus(
            $this->getPayStatus() === PaymentStatus::PAID->value
                ? PaymentStatus::REFUNDED->value
                : PaymentStatus::CANCELLED->value
        );
    }

    /**
     * 转换成数组.
     */
    public function toArray(): array
    {
        return [
            'order_no' => $this->getOrderNo(),
            'member_id' => $this->getMemberId(),
            'order_type' => $this->getOrderType(),
            'status' => $this->getStatus(),
            'goods_amount' => $this->getGoodsAmount(),
            'shipping_fee' => $this->getShippingFee(),
            'discount_amount' => $this->getDiscountAmount(),
            'total_amount' => $this->getTotalAmount(),
            'buyer_remark' => $this->getBuyerRemark(),
            'seller_remark' => $this->getSellerRemark(),
            'expire_time' => $this->getExpireTime(),
            'shipping_status' => $this->getShippingStatus(),
        ];
    }
}
