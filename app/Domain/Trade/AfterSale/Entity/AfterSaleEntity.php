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

namespace App\Domain\Trade\AfterSale\Entity;

use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Domain\Trade\AfterSale\Enum\AfterSaleRefundStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleReturnStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleType;
use App\Infrastructure\Error\AfterSaleError;

final class AfterSaleEntity
{
    private int $id = 0;

    private string $afterSaleNo = '';

    private int $orderId = 0;

    private int $orderItemId = 0;

    private int $memberId = 0;

    private string $type = AfterSaleType::REFUND_ONLY->value;

    private string $status = AfterSaleStatus::PENDING_REVIEW->value;

    private string $refundStatus = AfterSaleRefundStatus::PENDING->value;

    private string $returnStatus = AfterSaleReturnStatus::NOT_REQUIRED->value;

    private int $applyAmount = 0;

    private int $refundAmount = 0;

    private int $quantity = 1;

    private string $reason = '';

    private ?string $description = null;

    private ?string $rejectReason = null;

    private ?array $images = null;

    private ?string $buyerReturnLogisticsCompany = null;

    private ?string $buyerReturnLogisticsNo = null;

    private ?string $reshipLogisticsCompany = null;

    private ?string $reshipLogisticsNo = null;

    public static function apply(AfterSaleApplyInput $input): self
    {
        $entity = new self();
        $type = AfterSaleType::tryFrom($input->getType());
        if ($type === null) {
            throw AfterSaleError::TYPE_INVALID->exception();
        }
        if ($input->getQuantity() < 1) {
            throw AfterSaleError::QUANTITY_INVALID->exception();
        }
        if ($input->getApplyAmount() < 0) {
            throw AfterSaleError::AMOUNT_INVALID->exception();
        }

        $entity->orderId = $input->getOrderId();
        $entity->orderItemId = $input->getOrderItemId();
        $entity->memberId = $input->getMemberId();
        $entity->type = $type->value;
        $entity->status = AfterSaleStatus::PENDING_REVIEW->value;
        $entity->refundStatus = AfterSaleRefundStatus::PENDING->value;
        $entity->returnStatus = AfterSaleReturnStatus::NOT_REQUIRED->value;
        $entity->applyAmount = $input->getApplyAmount();
        $entity->refundAmount = $input->getApplyAmount();
        $entity->quantity = $input->getQuantity();
        $entity->reason = trim($input->getReason());
        $entity->description = $input->getDescription();
        $entity->images = $input->getImages();

        return $entity;
    }

    public function approve(): self
    {
        $this->assertStatus([AfterSaleStatus::PENDING_REVIEW], AfterSaleError::APPROVAL_STATUS_INVALID);

        if ($this->type === AfterSaleType::REFUND_ONLY->value) {
            $this->status = AfterSaleStatus::WAITING_REFUND->value;
            $this->returnStatus = AfterSaleReturnStatus::NOT_REQUIRED->value;
            return $this;
        }

        $this->status = AfterSaleStatus::WAITING_BUYER_RETURN->value;
        $this->returnStatus = AfterSaleReturnStatus::PENDING->value;
        return $this;
    }

    public function reject(): self
    {
        $this->assertStatus([AfterSaleStatus::PENDING_REVIEW], AfterSaleError::REJECTION_STATUS_INVALID);
        $this->status = AfterSaleStatus::CLOSED->value;
        return $this;
    }

    public function cancel(): self
    {
        $this->assertStatus([AfterSaleStatus::PENDING_REVIEW], AfterSaleError::CANCELLATION_STATUS_INVALID);
        $this->status = AfterSaleStatus::CLOSED->value;
        return $this;
    }

    public function submitBuyerReturn(string $company, string $trackingNo): self
    {
        if ($this->type === AfterSaleType::REFUND_ONLY->value) {
            throw AfterSaleError::REFUND_ONLY_RETURN_UNSUPPORTED->exception();
        }

        $this->assertStatus([AfterSaleStatus::WAITING_BUYER_RETURN], AfterSaleError::BUYER_RETURN_STATUS_INVALID);

        $this->buyerReturnLogisticsCompany = trim($company);
        $this->buyerReturnLogisticsNo = trim($trackingNo);
        $this->returnStatus = AfterSaleReturnStatus::BUYER_SHIPPED->value;
        $this->status = AfterSaleStatus::WAITING_SELLER_RECEIVE->value;
        return $this;
    }

    public function markRefunding(): self
    {
        $this->assertStatus([AfterSaleStatus::WAITING_REFUND], AfterSaleError::REFUND_START_STATUS_INVALID);
        $this->status = AfterSaleStatus::REFUNDING->value;
        $this->refundStatus = AfterSaleRefundStatus::PROCESSING->value;
        return $this;
    }

    public function markRefunded(): self
    {
        $this->assertStatus([AfterSaleStatus::REFUNDING], AfterSaleError::REFUND_COMPLETE_STATUS_INVALID);
        $this->status = AfterSaleStatus::COMPLETED->value;
        $this->refundStatus = AfterSaleRefundStatus::REFUNDED->value;
        return $this;
    }

    public function markRefundFailed(): self
    {
        $this->assertStatus([AfterSaleStatus::REFUNDING], AfterSaleError::REFUND_FAILURE_STATUS_INVALID);
        $this->status = AfterSaleStatus::WAITING_REFUND->value;
        $this->refundStatus = AfterSaleRefundStatus::FAILED->value;
        return $this;
    }

    public function markSellerReceived(): self
    {
        $this->assertStatus([AfterSaleStatus::WAITING_SELLER_RECEIVE], AfterSaleError::SELLER_RECEIPT_STATUS_INVALID);
        $this->returnStatus = AfterSaleReturnStatus::SELLER_RECEIVED->value;
        $this->status = $this->type === AfterSaleType::EXCHANGE->value
            ? AfterSaleStatus::WAITING_RESHIP->value
            : AfterSaleStatus::WAITING_REFUND->value;
        return $this;
    }

    public function markReshipped(string $company, string $trackingNo): self
    {
        if ($this->type !== AfterSaleType::EXCHANGE->value) {
            throw AfterSaleError::RESHIP_TYPE_INVALID->exception();
        }

        $this->assertStatus([AfterSaleStatus::WAITING_RESHIP], AfterSaleError::RESHIP_STATUS_INVALID);

        $this->reshipLogisticsCompany = trim($company);
        $this->reshipLogisticsNo = trim($trackingNo);
        $this->returnStatus = AfterSaleReturnStatus::SELLER_RESHIPPED->value;
        $this->status = AfterSaleStatus::RESHIPPED->value;
        return $this;
    }

    public function confirmExchangeReceived(): self
    {
        if ($this->type !== AfterSaleType::EXCHANGE->value) {
            throw AfterSaleError::EXCHANGE_RECEIPT_TYPE_INVALID->exception();
        }

        $this->assertStatus([AfterSaleStatus::RESHIPPED], AfterSaleError::EXCHANGE_RECEIPT_STATUS_INVALID);

        $this->returnStatus = AfterSaleReturnStatus::BUYER_RECEIVED->value;
        $this->status = AfterSaleStatus::COMPLETED->value;
        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'after_sale_no' => $this->afterSaleNo,
            'order_id' => $this->orderId,
            'order_item_id' => $this->orderItemId,
            'member_id' => $this->memberId,
            'type' => $this->type,
            'status' => $this->status,
            'refund_status' => $this->refundStatus,
            'return_status' => $this->returnStatus,
            'apply_amount' => $this->applyAmount,
            'refund_amount' => $this->refundAmount,
            'quantity' => $this->quantity,
            'reason' => $this->reason,
            'description' => $this->description,
            'reject_reason' => $this->rejectReason,
            'images' => $this->images,
            'buyer_return_logistics_company' => $this->buyerReturnLogisticsCompany,
            'buyer_return_logistics_no' => $this->buyerReturnLogisticsNo,
            'reship_logistics_company' => $this->reshipLogisticsCompany,
            'reship_logistics_no' => $this->reshipLogisticsNo,
        ], static fn ($value) => $value !== null);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getAfterSaleNo(): string
    {
        return $this->afterSaleNo;
    }

    public function setAfterSaleNo(string $afterSaleNo): self
    {
        $this->afterSaleNo = $afterSaleNo;
        return $this;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getOrderItemId(): int
    {
        return $this->orderItemId;
    }

    public function setOrderItemId(int $orderItemId): self
    {
        $this->orderItemId = $orderItemId;
        return $this;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setMemberId(int $memberId): self
    {
        $this->memberId = $memberId;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getRefundStatus(): string
    {
        return $this->refundStatus;
    }

    public function setRefundStatus(string $refundStatus): self
    {
        $this->refundStatus = $refundStatus;
        return $this;
    }

    public function getReturnStatus(): string
    {
        return $this->returnStatus;
    }

    public function setReturnStatus(string $returnStatus): self
    {
        $this->returnStatus = $returnStatus;
        return $this;
    }

    public function getApplyAmount(): int
    {
        return $this->applyAmount;
    }

    public function setApplyAmount(int $applyAmount): self
    {
        $this->applyAmount = $applyAmount;
        return $this;
    }

    public function getRefundAmount(): int
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(int $refundAmount): self
    {
        $this->refundAmount = $refundAmount;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getRejectReason(): ?string
    {
        return $this->rejectReason;
    }

    public function setRejectReason(?string $rejectReason): self
    {
        $this->rejectReason = $rejectReason;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): self
    {
        $this->images = $images;
        return $this;
    }

    public function getBuyerReturnLogisticsCompany(): ?string
    {
        return $this->buyerReturnLogisticsCompany;
    }

    public function setBuyerReturnLogisticsCompany(?string $company): self
    {
        $this->buyerReturnLogisticsCompany = $company;
        return $this;
    }

    public function getReturnLogisticsCompany(): ?string
    {
        return $this->buyerReturnLogisticsCompany;
    }

    public function getBuyerReturnLogisticsNo(): ?string
    {
        return $this->buyerReturnLogisticsNo;
    }

    public function setBuyerReturnLogisticsNo(?string $trackingNo): self
    {
        $this->buyerReturnLogisticsNo = $trackingNo;
        return $this;
    }

    public function getReturnLogisticsNo(): ?string
    {
        return $this->buyerReturnLogisticsNo;
    }

    public function getReshipLogisticsCompany(): ?string
    {
        return $this->reshipLogisticsCompany;
    }

    public function setReshipLogisticsCompany(?string $company): self
    {
        $this->reshipLogisticsCompany = $company;
        return $this;
    }

    public function getReshipLogisticsNo(): ?string
    {
        return $this->reshipLogisticsNo;
    }

    public function setReshipLogisticsNo(?string $trackingNo): self
    {
        $this->reshipLogisticsNo = $trackingNo;
        return $this;
    }

    /**
     * @param array<int, AfterSaleStatus> $statuses
     */
    private function assertStatus(array $statuses, AfterSaleError $error): void
    {
        foreach ($statuses as $status) {
            if ($this->status === $status->value) {
                return;
            }
        }

        throw $error->exception();
    }
}
