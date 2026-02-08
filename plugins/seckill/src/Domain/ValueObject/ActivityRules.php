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

namespace Plugin\Since\Seckill\Domain\ValueObject;

final class ActivityRules
{
    private readonly int $maxQuantityPerUser;

    private readonly int $minPurchaseQuantity;

    private readonly bool $requireMemberLevel;

    private readonly ?int $requiredMemberLevelId;

    private readonly bool $allowRefund;

    private readonly int $refundDeadlineHours;

    private readonly array $extraRules;

    public function __construct(array $rules)
    {
        $this->maxQuantityPerUser = (int) ($rules['max_quantity_per_user'] ?? 1);
        $this->minPurchaseQuantity = (int) ($rules['min_purchase_quantity'] ?? 1);
        $this->requireMemberLevel = (bool) ($rules['require_member_level'] ?? false);
        $this->requiredMemberLevelId = isset($rules['required_member_level_id']) ? (int) $rules['required_member_level_id'] : null;
        $this->allowRefund = (bool) ($rules['allow_refund'] ?? false);
        $this->refundDeadlineHours = (int) ($rules['refund_deadline_hours'] ?? 24);
        $this->extraRules = $rules['extra'] ?? [];
        $this->validate();
    }

    public function getMaxQuantityPerUser(): int
    {
        return $this->maxQuantityPerUser;
    }

    public function getMinPurchaseQuantity(): int
    {
        return $this->minPurchaseQuantity;
    }

    public function isRequireMemberLevel(): bool
    {
        return $this->requireMemberLevel;
    }

    public function getRequiredMemberLevelId(): ?int
    {
        return $this->requiredMemberLevelId;
    }

    public function isAllowRefund(): bool
    {
        return $this->allowRefund;
    }

    public function getRefundDeadlineHours(): int
    {
        return $this->refundDeadlineHours;
    }

    public function getExtraRules(): array
    {
        return $this->extraRules;
    }

    public function canUserPurchase(int $quantity, ?int $userMemberLevelId = null): bool
    {
        if ($quantity < $this->minPurchaseQuantity || $quantity > $this->maxQuantityPerUser) {
            return false;
        }
        if ($this->requireMemberLevel && $userMemberLevelId !== $this->requiredMemberLevelId) {
            return false;
        }
        return true;
    }

    public function toArray(): array
    {
        return [
            'max_quantity_per_user' => $this->maxQuantityPerUser, 'min_purchase_quantity' => $this->minPurchaseQuantity,
            'require_member_level' => $this->requireMemberLevel, 'required_member_level_id' => $this->requiredMemberLevelId,
            'allow_refund' => $this->allowRefund, 'refund_deadline_hours' => $this->refundDeadlineHours, 'extra' => $this->extraRules,
        ];
    }

    public static function default(): self
    {
        return new self(['max_quantity_per_user' => 1, 'min_purchase_quantity' => 1, 'require_member_level' => false, 'allow_refund' => false, 'refund_deadline_hours' => 24]);
    }

    private function validate(): void
    {
        if ($this->minPurchaseQuantity > $this->maxQuantityPerUser) {
            throw new \InvalidArgumentException('最小购买数量不能大于最大购买数量');
        }
        if ($this->requireMemberLevel && $this->requiredMemberLevelId === null) {
            throw new \InvalidArgumentException('要求会员等级时必须指定等级ID');
        }
    }
}
