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

/**
 * 会员钱包实体：聚合积分余额、累计值以及成长值信息.
 */
final class MemberWalletEntity
{
    private ?int $id = null;

    private int $memberId;

    private string $type = 'balance';

    private float $balance = 0.0;

    private float $changeBalance = 0.0;

    private float $beforeBalance = 0.0;

    private float $afterBalance = 0.0;

    private float $frozenBalance = 0.0;

    private float $totalRecharge = 0.0;

    private float $totalConsume = 0.0;

    private string $payPassword = '';

    private string $status = 'active';

    private string $remark = '';

    private string $source = 'manual';

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

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }

    public function setChangeBalance(float $changeBalance): void
    {
        $this->changeBalance = $changeBalance;
    }

    public function setFrozenBalance(float $frozenBalance): void
    {
        $this->frozenBalance = $frozenBalance;
    }

    public function setTotalRecharge(float $totalRecharge): void
    {
        $this->totalRecharge = $totalRecharge;
    }

    public function setTotalConsume(float $totalConsume): void
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

    public function setBeforeBalance(float $beforeBalance): void
    {
        $this->beforeBalance = $beforeBalance;
    }

    public function setAfterBalance(float $afterBalance): void
    {
        $this->afterBalance = $afterBalance;
    }

    public function getBeforeBalance(): float
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

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getChangeBalance(): float
    {
        return $this->changeBalance;
    }

    public function getAfterBalance(): float
    {
        return $this->afterBalance;
    }

    public function getFrozenBalance(): float
    {
        return $this->frozenBalance;
    }

    public function getTotalRecharge(): float
    {
        return $this->totalRecharge;
    }

    public function getTotalConsume(): float
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
     * 调整积分，返回变更前后的余额.
     */
    public function changeBalance(): void
    {
        if ($this->changeBalance === 0.0) {
            throw new \InvalidArgumentException('变动值不能为 0');
        }

        $this->setBeforeBalance($this->balance);
        $after = (float) bcadd((string) $this->balance, (string) $this->changeBalance, 2);

        if ($after < 0) {
            throw new \RuntimeException('余额不足，无法扣减');
        }

        $this->setAfterBalance($after);
        $this->balance = $after;
        if ($this->changeBalance > 0) {
            $this->totalRecharge = (float) bcadd((string) $this->totalRecharge, (string) $this->changeBalance, 2);
        } else {
            $consume = (string) abs($this->changeBalance);
            $this->totalConsume = (float) bcadd((string) $this->totalConsume, $consume, 2);
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
