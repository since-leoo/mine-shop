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

namespace App\Domain\Member\Entity;

use App\Domain\Member\Contract\MemberWalletInput;
use App\Domain\Member\ValueObject\BalanceChangeVo;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

/**
 * 会员钱包实体：聚合积分余额、累计值以及成长值信息.
 * 余额钱包金额单位为分（int），积分钱包为整数.
 */
final class MemberWalletEntity
{
    private ?int $id = null;

    private int $memberId;

    private string $type = 'balance';

    private int $balance = 0;

    private int $changeBalance = 0;

    private int $beforeBalance = 0;

    private int $afterBalance = 0;

    private int $frozenBalance = 0;

    private int $totalRecharge = 0;

    private int $totalConsume = 0;

    private string $payPassword = '';

    private string $status = 'active';

    private string $remark = '';

    private string $source = 'manual';

    /**
     * 调整余额行为方法：接收 DTO，执行余额变更.
     */
    public function adjustBalance(MemberWalletInput $dto): BalanceChangeVo
    {
        $this->setChangeBalance((int) $dto->getValue());
        $this->setSource($dto->getSource());
        $this->setRemark($dto->getRemark());

        // 调用余额变更逻辑
        $this->changeBalance();

        return BalanceChangeVo::success(
            memberId: $this->memberId,
            walletType: $this->type,
            beforeBalance: $this->beforeBalance,
            afterBalance: $this->afterBalance,
            changeAmount: $this->changeBalance
        );
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setMemberId(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setBalance(int $balance): void
    {
        $this->balance = $balance;
    }

    public function setChangeBalance(int $changeBalance): void
    {
        $this->changeBalance = $changeBalance;
    }

    public function setFrozenBalance(int $frozenBalance): void
    {
        $this->frozenBalance = $frozenBalance;
    }

    public function setTotalRecharge(int $totalRecharge): void
    {
        $this->totalRecharge = $totalRecharge;
    }

    public function setTotalConsume(int $totalConsume): void
    {
        $this->totalConsume = $totalConsume;
    }

    public function setPayPassword(string $payPassword): void
    {
        $this->payPassword = ! empty($payPassword) ? password_hash($payPassword, \PASSWORD_BCRYPT) : '';
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setRemark(string $remark): void
    {
        $this->remark = $remark;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function setBeforeBalance(int $beforeBalance): void
    {
        $this->beforeBalance = $beforeBalance;
    }

    public function setAfterBalance(int $afterBalance): void
    {
        $this->afterBalance = $afterBalance;
    }

    public function getBeforeBalance(): int
    {
        return $this->beforeBalance;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getChangeBalance(): int
    {
        return $this->changeBalance;
    }

    public function getAfterBalance(): int
    {
        return $this->afterBalance;
    }

    public function getFrozenBalance(): int
    {
        return $this->frozenBalance;
    }

    public function getTotalRecharge(): int
    {
        return $this->totalRecharge;
    }

    public function getTotalConsume(): int
    {
        return $this->totalConsume;
    }

    public function getPayPassword(): string
    {
        return $this->payPassword;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRemark(): string
    {
        return $this->remark;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * 执行余额变更（整数运算，单位为分或积分）.
     */
    public function changeBalance(): void
    {
        if ($this->changeBalance === 0) {
            throw new BusinessException(ResultCode::FAIL, '变动值不能为 0');
        }

        $this->setBeforeBalance($this->balance);
        $after = $this->balance + $this->changeBalance;

        // 领域不变量：余额不能为负数
        if ($after < 0) {
            throw new BusinessException(
                ResultCode::FAIL,
                \sprintf('余额不足，当前余额：%d，变动金额：%d', $this->balance, $this->changeBalance)
            );
        }

        $this->setAfterBalance($after);
        $this->balance = $after;

        // 更新累计充值或消费
        if ($this->changeBalance > 0) {
            $this->totalRecharge += $this->changeBalance;
        } else {
            $this->totalConsume += abs($this->changeBalance);
        }
    }

    public function toArray(): array
    {
        return [
            'member_id' => $this->getMemberId(),
            'type' => $this->getType(),
            'balance' => $this->getBalance(),
            'change_balance' => $this->getChangeBalance(),
            'before_balance' => $this->getBeforeBalance(),
            'after_balance' => $this->getAfterBalance(),
            'frozen_balance' => $this->getFrozenBalance(),
            'total_recharge' => $this->getTotalRecharge(),
            'pay_password' => $this->getPayPassword(),
        ];
    }
}
