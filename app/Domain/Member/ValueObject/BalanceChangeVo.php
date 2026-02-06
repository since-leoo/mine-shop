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

namespace App\Domain\Member\ValueObject;

/**
 * 余额变更值对象.
 */
final class BalanceChangeVo
{
    public function __construct(
        public readonly int $memberId,
        public readonly string $walletType,
        public readonly float $beforeBalance,
        public readonly float $afterBalance,
        public readonly float $changeAmount,
        public readonly bool $success = true,
        public readonly string $message = '',
    ) {}

    /**
     * 创建成功的结果.
     */
    public static function success(
        int $memberId,
        string $walletType,
        float $beforeBalance,
        float $afterBalance,
        float $changeAmount
    ): self {
        return new self(
            memberId: $memberId,
            walletType: $walletType,
            beforeBalance: $beforeBalance,
            afterBalance: $afterBalance,
            changeAmount: $changeAmount,
            success: true,
            message: '余额变更成功'
        );
    }

    /**
     * 创建失败的结果.
     */
    public static function fail(string $message): self
    {
        return new self(
            memberId: 0,
            walletType: '',
            beforeBalance: 0.0,
            afterBalance: 0.0,
            changeAmount: 0.0,
            success: false,
            message: $message
        );
    }
}
