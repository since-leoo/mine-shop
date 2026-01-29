<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Order\Enum\OrderStatus;
use App\Domain\Order\Enum\PaymentStatus;
use App\Domain\Order\Enum\ShippingStatus;
use App\Domain\Order\ValueObject\OrderAddressValue;
use App\Domain\Order\ValueObject\OrderPriceValue;

final class OrderDraftEntity
{
    private string $orderType = 'normal';

    private int $memberId = 0;

    private string $orderNo = '';

    private string $buyerRemark = '';

    /**
     * @var OrderDraftItemEntity[]
     */
    private array $items = [];

    private ?OrderAddressValue $address = null;

    private ?OrderPriceValue $priceDetail = null;

    public function setOrderType(string $orderType): void
    {
        $this->orderType = $orderType;
    }

    public function getOrderType(): string
    {
        return $this->orderType;
    }

    public function setMemberId(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setOrderNo(string $orderNo): void
    {
        $this->orderNo = $orderNo;
    }

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setBuyerRemark(string $buyerRemark): void
    {
        $this->buyerRemark = $buyerRemark;
    }

    public function getBuyerRemark(): string
    {
        return $this->buyerRemark;
    }

    public function addItem(OrderDraftItemEntity $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return OrderDraftItemEntity[]
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

    /**
     * @return array{
     *     order: array<string, mixed>,
     *     items: array<int, array<string, mixed>>,
     *     address: null|array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        $price = $this->priceDetail;
        if (! $price) {
            throw new \RuntimeException('订单草稿缺少金额信息');
        }

        return [
            'order' => [
                'member_id' => $this->memberId,
                'order_type' => $this->orderType,
                'buyer_remark' => $this->buyerRemark,
                'goods_amount' => $price->getGoodsAmount(),
                'discount_amount' => $price->getDiscountAmount(),
                'shipping_fee' => $price->getShippingFee(),
                'total_amount' => $price->getTotalAmount(),
                'pay_amount' => $price->getPayAmount(),
                'status' => OrderStatus::PENDING->value,
                'pay_status' => PaymentStatus::PENDING->value,
                'shipping_status' => ShippingStatus::PENDING->value,
            ],
            'items' => array_map(static fn (OrderDraftItemEntity $item) => [
                'product_id' => $item->getProductId(),
                'sku_id' => $item->getSkuId(),
                'product_name' => $item->getProductName(),
                'sku_name' => $item->getSkuName(),
                'product_image' => $item->getProductImage(),
                'spec_values' => $item->getSpecValues(),
                'unit_price' => $item->getUnitPrice(),
                'quantity' => $item->getQuantity(),
                'total_price' => $item->getTotalPrice(),
            ], $this->items),
            'address' => $this->address
                ? [
                    'receiver_name' => $this->address->getReceiverName(),
                    'receiver_phone' => $this->address->getReceiverPhone(),
                    'province' => $this->address->getProvince(),
                    'city' => $this->address->getCity(),
                    'district' => $this->address->getDistrict(),
                    'detail' => $this->address->getDetail(),
                    'full_address' => $this->address->getFullAddress(),
                ]
                : null,
        ];
    }
}
