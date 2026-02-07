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

use App\Domain\Order\Enum\PaymentStatus;

/**
 * 订单支付实体.
 */
final class OrderPaymentEntity
{
    private int $id = 0;

    private string $paymentNo = '';

    private int $orderId = 0;

    private string $orderNo = '';

    private int $memberId = 0;

    private string $paymentMethod = '';

    private int $paymentAmount = 0;

    private int $paidAmount = 0;

    private int $refundAmount = 0;

    private string $currency = 'CNY';

    private string $status = '';

    private ?string $thirdPartyNo = null;

    private ?array $thirdPartyResponse = null;

    private ?array $callbackData = null;

    private ?string $paidAt = null;

    private ?string $expiredAt = null;

    private ?string $remark = null;

    private ?array $extraData = null;

    /**
     * @var array<string, bool> dirty 追踪机制
     */
    private array $dirty = [];

    /**
     * 创建支付记录.
     */
    public function create(
        int $orderId,
        string $orderNo,
        int $memberId,
        string $paymentMethod,
        int $paymentAmount
    ): self {
        $this->setOrderId($orderId);
        $this->setOrderNo($orderNo);
        $this->setMemberId($memberId);
        $this->setPaymentMethod($paymentMethod);
        $this->setPaymentAmount($paymentAmount);
        $this->setStatus(PaymentStatus::PENDING->value);
        $this->setCurrency('CNY');

        // 设置过期时间（30分钟后）
        $this->setExpiredAt(date('Y-m-d H:i:s', time() + 1800));

        return $this;
    }

    /**
     * 标记为已支付.
     */
    public function markPaid(int $paidAmount, ?string $thirdPartyNo = null, ?array $callbackData = null): self
    {
        if ($this->status === PaymentStatus::PAID->value) {
            throw new \DomainException('支付记录已支付，不能重复支付');
        }

        $this->setStatus(PaymentStatus::PAID->value);
        $this->setPaidAmount($paidAmount);
        $this->setPaidAt(date('Y-m-d H:i:s'));

        if ($thirdPartyNo !== null) {
            $this->setThirdPartyNo($thirdPartyNo);
        }

        if ($callbackData !== null) {
            $this->setCallbackData($callbackData);
        }

        return $this;
    }

    /**
     * 标记为失败.
     */
    public function markFailed(?string $remark = null): self
    {
        $this->setStatus(PaymentStatus::FAILED->value);

        if ($remark !== null) {
            $this->setRemark($remark);
        }

        return $this;
    }

    /**
     * 标记为已取消.
     */
    public function markCancelled(?string $remark = null): self
    {
        if ($this->status === PaymentStatus::PAID->value) {
            throw new \DomainException('已支付的记录不能取消');
        }

        $this->setStatus(PaymentStatus::CANCELLED->value);

        if ($remark !== null) {
            $this->setRemark($remark);
        }

        return $this;
    }

    /**
     * 更新第三方响应数据.
     */
    public function updateThirdPartyResponse(array $response): self
    {
        $this->setThirdPartyResponse($response);
        return $this;
    }

    /**
     * 增加退款金额.
     */
    public function addRefundAmount(int $amount): self
    {
        if ($this->status !== PaymentStatus::PAID->value) {
            throw new \DomainException('只有已支付的记录才能退款');
        }

        $newRefundAmount = $this->refundAmount + $amount;

        if ($newRefundAmount > $this->paidAmount) {
            throw new \DomainException('退款金额不能超过实付金额');
        }

        $this->setRefundAmount($newRefundAmount);

        // 如果全额退款，更新状态
        if ($newRefundAmount === $this->paidAmount) {
            $this->setStatus(PaymentStatus::REFUNDED->value);
        }

        return $this;
    }

    /**
     * 转换为数组（用于持久化）.
     */
    public function toArray(): array
    {
        $data = [
            'payment_no' => $this->paymentNo,
            'order_id' => $this->orderId,
            'order_no' => $this->orderNo,
            'member_id' => $this->memberId,
            'payment_method' => $this->paymentMethod,
            'payment_amount' => $this->paymentAmount,
            'paid_amount' => $this->paidAmount,
            'refund_amount' => $this->refundAmount,
            'currency' => $this->currency,
            'status' => $this->status,
            'third_party_no' => $this->thirdPartyNo,
            'third_party_response' => $this->thirdPartyResponse,
            'callback_data' => $this->callbackData,
            'paid_at' => $this->paidAt,
            'expired_at' => $this->expiredAt,
            'remark' => $this->remark,
            'extra_data' => $this->extraData,
        ];

        // 如果没有 dirty 标记，返回所有非空字段
        if ($this->dirty === []) {
            return array_filter($data, static fn ($value) => $value !== null);
        }

        // 只返回 dirty 标记的字段
        return array_filter(
            $data,
            function ($value, string $field) {
                return isset($this->dirty[$field]) && $value !== null;
            },
            \ARRAY_FILTER_USE_BOTH
        );
    }

    // Setters with dirty tracking

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setPaymentNo(string $paymentNo): self
    {
        $this->paymentNo = $paymentNo;
        $this->markDirty('payment_no');
        return $this;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;
        $this->markDirty('order_id');
        return $this;
    }

    public function setOrderNo(string $orderNo): self
    {
        $this->orderNo = $orderNo;
        $this->markDirty('order_no');
        return $this;
    }

    public function setMemberId(int $memberId): self
    {
        $this->memberId = $memberId;
        $this->markDirty('member_id');
        return $this;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        $this->markDirty('payment_method');
        return $this;
    }

    public function setPaymentAmount(int $paymentAmount): self
    {
        $this->paymentAmount = $paymentAmount;
        $this->markDirty('payment_amount');
        return $this;
    }

    public function setPaidAmount(int $paidAmount): self
    {
        $this->paidAmount = $paidAmount;
        $this->markDirty('paid_amount');
        return $this;
    }

    public function setRefundAmount(int $refundAmount): self
    {
        $this->refundAmount = $refundAmount;
        $this->markDirty('refund_amount');
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        $this->markDirty('currency');
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->markDirty('status');
        return $this;
    }

    public function setThirdPartyNo(?string $thirdPartyNo): self
    {
        $this->thirdPartyNo = $thirdPartyNo;
        $this->markDirty('third_party_no');
        return $this;
    }

    public function setThirdPartyResponse(?array $thirdPartyResponse): self
    {
        $this->thirdPartyResponse = $thirdPartyResponse;
        $this->markDirty('third_party_response');
        return $this;
    }

    public function setCallbackData(?array $callbackData): self
    {
        $this->callbackData = $callbackData;
        $this->markDirty('callback_data');
        return $this;
    }

    public function setPaidAt(?string $paidAt): self
    {
        $this->paidAt = $paidAt;
        $this->markDirty('paid_at');
        return $this;
    }

    public function setExpiredAt(?string $expiredAt): self
    {
        $this->expiredAt = $expiredAt;
        $this->markDirty('expired_at');
        return $this;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;
        $this->markDirty('remark');
        return $this;
    }

    public function setExtraData(?array $extraData): self
    {
        $this->extraData = $extraData;
        $this->markDirty('extra_data');
        return $this;
    }

    // Getters

    public function getId(): int
    {
        return $this->id;
    }

    public function getPaymentNo(): string
    {
        return $this->paymentNo;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getPaymentAmount(): int
    {
        return $this->paymentAmount;
    }

    public function getPaidAmount(): int
    {
        return $this->paidAmount;
    }

    public function getRefundAmount(): int
    {
        return $this->refundAmount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getThirdPartyNo(): ?string
    {
        return $this->thirdPartyNo;
    }

    public function getThirdPartyResponse(): ?array
    {
        return $this->thirdPartyResponse;
    }

    public function getCallbackData(): ?array
    {
        return $this->callbackData;
    }

    public function getPaidAt(): ?string
    {
        return $this->paidAt;
    }

    public function getExpiredAt(): ?string
    {
        return $this->expiredAt;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getExtraData(): ?array
    {
        return $this->extraData;
    }

    /**
     * 标记字段为已修改.
     */
    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }
}
