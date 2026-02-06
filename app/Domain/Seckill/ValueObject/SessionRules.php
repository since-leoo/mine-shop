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

namespace App\Domain\Seckill\ValueObject;

/**
 * 场次规则值对象.
 */
final class SessionRules
{
    private readonly int $maxQuantityPerUser;

    private readonly int $totalQuantity;

    private readonly bool $allowOverSell;

    private readonly array $extraRules;

    public function __construct(array $rules)
    {
        $this->maxQuantityPerUser = (int) ($rules['max_quantity_per_user'] ?? 1);
        $this->totalQuantity = (int) ($rules['total_quantity'] ?? 0);
        $this->allowOverSell = (bool) ($rules['allow_over_sell'] ?? false);
        $this->extraRules = $rules['extra'] ?? [];

        $this->validate();
    }

    public function getMaxQuantityPerUser(): int
    {
        return $this->maxQuantityPerUser;
    }

    public function getTotalQuantity(): int
    {
        return $this->totalQuantity;
    }

    public function isAllowOverSell(): bool
    {
        return $this->allowOverSell;
    }

    public function getExtraRules(): array
    {
        return $this->extraRules;
    }

    public function canPurchase(int $quantity, int $userPurchasedQuantity): bool
    {
        // 检查是否超过每人限购
        if ($userPurchasedQuantity + $quantity > $this->maxQuantityPerUser) {
            return false;
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'max_quantity_per_user' => $this->maxQuantityPerUser,
            'total_quantity' => $this->totalQuantity,
            'allow_over_sell' => $this->allowOverSell,
            'extra' => $this->extraRules,
        ];
    }

    public static function default(): self
    {
        return new self([
            'max_quantity_per_user' => 1,
            'total_quantity' => 0,
            'allow_over_sell' => false,
        ]);
    }

    private function validate(): void
    {
        // 值对象只做基本的业务规则验证，格式验证在Request层
    }
}
