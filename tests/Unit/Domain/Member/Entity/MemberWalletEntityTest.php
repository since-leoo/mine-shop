<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Entity;

use App\Domain\Member\Entity\MemberWalletEntity;
use PHPUnit\Framework\TestCase;

class MemberWalletEntityTest extends TestCase
{
    private function makeWallet(int $balance = 10000): MemberWalletEntity
    {
        $wallet = new MemberWalletEntity();
        $wallet->setMemberId(1);
        $wallet->setType('balance');
        $wallet->setBalance($balance);
        $wallet->setStatus('active');
        return $wallet;
    }

    public function testBasicProperties(): void
    {
        $wallet = $this->makeWallet();
        $this->assertSame(1, $wallet->getMemberId());
        $this->assertSame('balance', $wallet->getType());
        $this->assertSame(10000, $wallet->getBalance());
        $this->assertSame('active', $wallet->getStatus());
    }

    public function testChangeBalanceIncrease(): void
    {
        $wallet = $this->makeWallet(10000);
        $wallet->setChangeBalance(5000);
        $wallet->changeBalance();
        $this->assertSame(10000, $wallet->getBeforeBalance());
        $this->assertSame(15000, $wallet->getAfterBalance());
        $this->assertSame(15000, $wallet->getBalance());
        $this->assertSame(5000, $wallet->getTotalRecharge());
    }

    public function testChangeBalanceDecrease(): void
    {
        $wallet = $this->makeWallet(10000);
        $wallet->setChangeBalance(-3000);
        $wallet->changeBalance();
        $this->assertSame(10000, $wallet->getBeforeBalance());
        $this->assertSame(7000, $wallet->getAfterBalance());
        $this->assertSame(7000, $wallet->getBalance());
        $this->assertSame(3000, $wallet->getTotalConsume());
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
        $this->assertSame(2000, $wallet->getFrozenBalance());
    }

    public function testToArray(): void
    {
        $wallet = $this->makeWallet(10000);
        $arr = $wallet->toArray();
        $this->assertSame(1, $arr['member_id']);
        $this->assertSame('balance', $arr['type']);
        $this->assertSame(10000, $arr['balance']);
    }
}
