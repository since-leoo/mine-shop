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

namespace App\Domain\Trade\Order\Entity;

use App\Domain\Trade\Order\Contract\OrderPreviewInput;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Enum\PaymentStatus;
use App\Domain\Trade\Order\Enum\ShippingStatus;
use App\Domain\Trade\Order\Trait\OrderSettingsTrait;
use App\Domain\Trade\Order\ValueObject\OrderAddressValue;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use Carbon\Carbon;

final class OrderEntity
{
    use OrderSettingsTrait;

    private int $id = 0;

    private string $orderNo = '';

    private int $memberId = 0;

    private string $orderType = 'normal';

    private int $goodsAmount = 0;

    private int $shippingFee = 0;

    private int $discountAmount = 0;

    private int $totalAmount = 0;

    private int $payAmount = 0;

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

    /** @var int 优惠券抵扣金额（分） */
    private int $couponAmount = 0;

    /** @var array<int, int> 已应用的 coupon_user IDs（用于 submit 后标记已使用） */
    private array $appliedCouponUserIds = [];

    /** @var array<string, mixed> 运行时元数据（不持久化），供策略读取上下文 */
    private array $extras = [];

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

    public function getGoodsAmount(): int
    {
        return $this->goodsAmount;
    }

    public function setGoodsAmount(int $goodsAmount): void
    {
        $this->goodsAmount = $goodsAmount;
    }

    public function getShippingFee(): int
    {
        return $this->shippingFee;
    }

    public function setShippingFee(int $shippingFee): void
    {
        $this->shippingFee = $shippingFee;
    }

    public function getDiscountAmount(): int
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(int $discountAmount): void
    {
        $this->discountAmount = $discountAmount;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(int $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getPayAmount(): int
    {
        return $this->payAmount;
    }

    public function setPayAmount(int $payAmount): void
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
        $this->addItem($item);
    }

    public function addItem(OrderItemEntity $item): void
    {
        $item->ensureQuantityPositive();
        $this->items[] = $item;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function replaceItemsFromPayload(array $items): void
    {
        $this->items = [];
        foreach ($items as $payload) {
            if (! \is_array($payload)) {
                continue;
            }
            $this->addItem(OrderItemEntity::fromPayload($payload));
        }
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

    /**
     * @param array<string, mixed> $payload
     */
    public function useAddressPayload(array $payload): void
    {
        $this->setAddress(OrderAddressValue::fromArray($payload));
    }

    public function getAddress(): ?OrderAddressValue
    {
        return $this->address;
    }

    public function getCouponAmount(): int
    {
        return $this->couponAmount;
    }

    public function setCouponAmount(int $couponAmount): void
    {
        $this->couponAmount = $couponAmount;
    }

    /**
     * @return array<int, int>
     */
    public function getAppliedCouponUserIds(): array
    {
        return $this->appliedCouponUserIds;
    }

    /**
     * @param array<int, int> $ids
     */
    public function setAppliedCouponUserIds(array $ids): void
    {
        $this->appliedCouponUserIds = $ids;
    }

    public function setExtra(string $key, mixed $value): void
    {
        $this->extras[$key] = $value;
    }

    public function getExtra(string $key, mixed $default = null): mixed
    {
        return $this->extras[$key] ?? $default;
    }

    public function syncPriceDetailFromItems(): void
    {
        $sum = 0;
        foreach ($this->items as $item) {
            $sum += $item->getTotalPrice();
        }

        $detail = $this->priceDetail ?? new OrderPriceValue();
        $detail->setGoodsAmount($sum);
        $this->setPriceDetail($detail);
    }

    public function setPriceDetail(OrderPriceValue $priceDetail): void
    {
        $this->priceDetail = $priceDetail;
        $this->setGoodsAmount($priceDetail->getGoodsAmount());
        $this->setDiscountAmount($priceDetail->getDiscountAmount());
        $this->setShippingFee($priceDetail->getShippingFee());
        $this->setTotalAmount($priceDetail->getTotalAmount());
        $this->setPayAmount($priceDetail->getPayAmount());
    }

    public function getPriceDetail(): ?OrderPriceValue
    {
        return $this->priceDetail;
    }

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

    public function complete(): void
    {
        if (! \in_array($this->getStatus(), [OrderStatus::SHIPPED->value, OrderStatus::PARTIAL_SHIPPED->value], true)) {
            throw new \RuntimeException('当前订单状态不可完成');
        }

        $this->setStatus(OrderStatus::COMPLETED->value);
        $this->setShippingStatus(ShippingStatus::DELIVERED->value);
    }

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

    public function markPaid(): void
    {
        if ($this->getStatus() !== OrderStatus::PENDING->value) {
            throw new \RuntimeException('当前订单状态不可支付');
        }

        $this->setPayStatus(PaymentStatus::PAID->value);
        $this->setStatus(OrderStatus::PAID->value);
        $this->setPayTime(Carbon::now());
    }

    /**
     * 从预览输入契约初始化订单实体.
     */
    public function initFromInput(OrderPreviewInput $input): void
    {
        $this->setMemberId($input->getMemberId());
        $this->setOrderType($input->getOrderType());
        $this->replaceItemsFromPayload($input->getGoodsRequestList());
        $this->setBuyerRemark($input->getBuyerRemark());

        // 秒杀上下文元数据（不持久化，供策略使用）
        if ($input->getActivityId() !== null) {
            $this->setExtra('activity_id', $input->getActivityId());
        }
        if ($input->getSessionId() !== null) {
            $this->setExtra('session_id', $input->getSessionId());
        }

        // 拼团上下文元数据（不持久化，供策略使用）
        if ($input->getGroupBuyId() !== null) {
            $this->setExtra('group_buy_id', $input->getGroupBuyId());
        }
        if ($input->getGroupNo() !== null) {
            $this->setExtra('group_no', $input->getGroupNo());
        }
    }

    /**
     * 价格校验（分为单位比较），防止前端价格篡改.
     *
     * @param int $frontendAmountCent 前端传入的总金额（分）
     * @throws \DomainException 当前端金额与后端计算金额不一致时
     */
    public function verifyPrice(int $frontendAmountCent): void
    {
        if ($frontendAmountCent !== $this->getPayAmount()) {
            throw new \DomainException('商品价格已变动，请重新下单');
        }
    }

    public function guardPreorderAllowed(bool $allowPreorder): void
    {
        if (! $allowPreorder && $this->orderType === 'preorder') {
            throw new \DomainException('当前商品不支持预订单');
        }
    }

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
            'pay_amount' => $this->getPayAmount(),
            'pay_status' => $this->getPayStatus(),
            'pay_time' => $this->getPayTime()?->toDateTimeString(),
            'pay_no' => $this->getPayNo(),
            'pay_method' => $this->getPayMethod(),
            'buyer_remark' => $this->getBuyerRemark(),
            'seller_remark' => $this->getSellerRemark(),
            'expire_time' => $this->getExpireTime(),
            'shipping_status' => $this->getShippingStatus(),
            'package_count' => $this->getPackageCount(),
        ];
    }
}
