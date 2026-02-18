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

namespace HyperfTests\Unit\Domain\Member\Entity;

use App\Domain\Member\Entity\MemberWalletEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class MemberWalletEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $wallet = $this->makeWallet();
        self::assertSame(1, $wallet->getMemberId());
        self::assertSame('balance', $wallet->getType());
        self::assertSame(10000, $wallet->getBalance());
        self::assertSame('active', $wallet->getStatus());
    }

    public function testChangeBalanceIncrease(): void
    {
        $wallet = $this->makeWallet(10000);
        $wallet->setChangeBalance(5000);
        $wallet->changeBalance();
        self::assertSame(10000, $wallet->getBeforeBalance());
        self::assertSame(15000, $wallet->getAfterBalance());
        self::assertSame(15000, $wallet->getBalance());
        self::assertSame(5000, $wallet->getTotalRecharge());
    }

    public function testChangeBalanceDecrease(): void
    {
        $wallet = $this->makeWallet(10000);
        $wallet->setChangeBalance(-3000);
        $wallet->changeBalance();
        self::assertSame(10000, $wallet->getBeforeBalance());
        self::assertSame(7000, $wallet->getAfterBalance());
        self::assertSame(7000, $wallet->getBalance());
        self::assertSame(3000, $wallet->getTotalConsume());
    }

    public function testChangeBalanceZeroThrows(): void
    {
        $wallet = $this->makeWallet(10000);
        $wallet->setChangeBalance(0);
        $this->expectException(\Throwable::class);
        $wallet->changeBalance();
    }

    public function testChangeBalanceInsufficientThrows(): void
    {
        $wallet = $this->makeWallet(1000);
        $wallet->setChangeBalance(-5000);
        $this->expectException(\Throwable::class);
        $wallet->changeBalance();
    }

    public function testFrozenBalance(): void
    {
        $wallet = $this->makeWallet();
        $wallet->setFrozenBalance(2000);
        self::assertSame(2000, $wallet->getFrozenBalance());
    }

    public function testToArray(): void
    {
        $wallet = $this->makeWallet(10000);
        $arr = $wallet->toArray();
        self::assertSame(1, $arr['member_id']);
        self::assertSame('balance', $arr['type']);
        self::assertSame(10000, $arr['balance']);
    }

    private function makeWallet(int $balance = 10000): MemberWalletEntity
    {
        $wallet = new MemberWalletEntity();
        $wallet->setMemberId(1);
        $wallet->setType('balance');
        $wallet->setBalance($balance);
        $wallet->setStatus('active');
        return $wallet;
    }
}
