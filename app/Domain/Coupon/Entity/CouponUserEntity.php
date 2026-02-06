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

namespace App\Domain\Coupon\Entity;

use App\Domain\Coupon\Contract\CouponUserInput;

/**
 * 用户优惠券实体.
 */
final class CouponUserEntity
{
    private int $id = 0;

    private ?int $couponId = null;

    private ?int $memberId = null;

    private ?int $orderId = null;

    private ?string $status = 'unused';

    private ?string $receivedAt = null;

    private ?string $usedAt = null;

    private ?string $expireAt = null;

    public function __construct() {}

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCouponId(): ?int
    {
        return $this->couponId;
    }

    public function setCouponId(?int $couponId): self
    {
        $this->couponId = $couponId;
        return $this;
    }

    public function getMemberId(): ?int
    {
        return $this->memberId;
    }

    public function setMemberId(?int $memberId): self
    {
        $this->memberId = $memberId;
        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(?int $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getReceivedAt(): ?string
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(?string $receivedAt): self
    {
        $this->receivedAt = $receivedAt;
        return $this;
    }

    public function getUsedAt(): ?string
    {
        return $this->usedAt;
    }

    public function setUsedAt(?string $usedAt): self
    {
        $this->usedAt = $usedAt;
        return $this;
    }

    public function getExpireAt(): ?string
    {
        return $this->expireAt;
    }

    public function setExpireAt(?string $expireAt): self
    {
        $this->expireAt = $expireAt;
        return $this;
    }

    public static function issue(int $couponId, int $memberId, string $receivedAt, string $expireAt): self
    {
        $entity = new self();
        $entity->couponId = $couponId;
        $entity->memberId = $memberId;
        $entity->status = 'unused';
        $entity->receivedAt = $receivedAt;
        $entity->expireAt = $expireAt;
        return $entity;
    }

    /**
     * Create a new CouponUserEntity from a DTO.
     */
    public function create(CouponUserInput $dto): self
    {
        $this->couponId = $dto->getCouponId();
        $this->memberId = $dto->getMemberId();
        $this->orderId = $dto->getOrderId();
        $this->status = $dto->getStatus() ?? 'unused';
        $this->receivedAt = $dto->getReceivedAt();
        $this->usedAt = $dto->getUsedAt();
        $this->expireAt = $dto->getExpireAt();

        return $this;
    }

    /**
     * Update the CouponUserEntity from a DTO.
     * Only updates properties that are non-null in the DTO.
     */
    public function update(CouponUserInput $dto): self
    {
        if ($dto->getCouponId() !== null) {
            $this->couponId = $dto->getCouponId();
        }

        if ($dto->getMemberId() !== null) {
            $this->memberId = $dto->getMemberId();
        }

        if ($dto->getOrderId() !== null) {
            $this->orderId = $dto->getOrderId();
        }

        if ($dto->getStatus() !== null) {
            $this->status = $dto->getStatus();
        }

        if ($dto->getReceivedAt() !== null) {
            $this->receivedAt = $dto->getReceivedAt();
        }

        if ($dto->getUsedAt() !== null) {
            $this->usedAt = $dto->getUsedAt();
        }

        if ($dto->getExpireAt() !== null) {
            $this->expireAt = $dto->getExpireAt();
        }

        return $this;
    }

    public function markExpired(): self
    {
        if ($this->getStatus() !== 'unused') {
            throw new \RuntimeException('仅未使用的优惠券可以置为过期');
        }
        return $this->setStatus('expired');
    }

    public function markUsed(?string $usedAt = null, ?int $orderId = null): self
    {
        if ($this->getStatus() !== 'unused') {
            throw new \RuntimeException('仅未使用的优惠券可以置为已使用');
        }

        $this->setStatus('used')
            ->setUsedAt($usedAt ?? date('Y-m-d H:i:s'))
            ->setOrderId($orderId);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'coupon_id' => $this->couponId,
            'member_id' => $this->memberId,
            'order_id' => $this->orderId,
            'status' => $this->status,
            'received_at' => $this->receivedAt,
            'used_at' => $this->usedAt,
            'expire_at' => $this->expireAt,
        ], static fn ($value) => $value !== null);
    }
}
