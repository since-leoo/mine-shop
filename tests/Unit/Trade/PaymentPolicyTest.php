<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Payment\ValueObject\PaymentPolicy;
use App\Infrastructure\Service\Pay\YsdPayService;
use PHPUnit\Framework\TestCase;

final class PaymentPolicyTest extends TestCase
{
    private function order(): OrderEntity
    {
        $order = new OrderEntity();
        $order->setMemberId(7);
        $order->setOrderNo('ORDER-123');
        $order->setTotalAmount(1000);
        $order->setPayAmount(700);
        return $order;
    }

    public function testWechatUsesActualOrderDiscountedAmountAndMember(): void
    {
        $member = new MemberEntity();
        $member->setId(7);
        $member->setOpenid('member-openid');
        $payload = (new PaymentPolicy())->wechatPayload($this->order(), $member);
        self::assertSame('ORDER-123', $payload['out_trade_no']);
        self::assertSame(['total' => 700, 'currency' => 'CNY'], $payload['amount']);
        self::assertSame('member-openid', $payload['payer']['openid']);
    }

    public function testRejectsAnotherMembersOrder(): void
    {
        $this->expectException(\DomainException::class);
        (new PaymentPolicy())->assertOwner($this->order(), 8);
    }

    public function testRejectsDisabledBalanceChannel(): void
    {
        $this->expectException(\DomainException::class);
        (new PaymentPolicy())->assertBalance(new PaymentSetting(true, [], false, 0, false, []), 100, 0);
    }

    public function testDailyLimitIncludesEarlierPayments(): void
    {
        $this->expectException(\DomainException::class);
        (new PaymentPolicy())->assertBalance(new PaymentSetting(true, [], false, 0, true, ['daily_limit' => 1000]), 600, 500);
    }

    public function testDailyLimitAllowsExactLimit(): void
    {
        (new PaymentPolicy())->assertBalance(new PaymentSetting(true, [], false, 0, true, ['daily_limit' => 1000]), 500, 500);
        $this->addToAssertionCount(1);
    }

    public function testSdkEnvelopeIsUnwrappedAndAmountValidated(): void
    {
        $resource = ['out_trade_no' => 'ORDER-123', 'mchid' => 'm1', 'appid' => 'a1', 'transaction_id' => 'wx1', 'amount' => ['total' => 700, 'currency' => 'CNY']];
        $callback = YsdPayService::unwrapCallback(['id' => 'event1', 'resource' => ['ciphertext' => $resource]]);
        self::assertSame($resource, $callback);
        self::assertSame(700, (new PaymentPolicy())->validateWechatSuccess($this->order(), $callback, ['mch_id' => 'm1', 'mini_app_id' => 'a1']));
    }

    public function testRejectsIncorrectCallbackAmount(): void
    {
        $this->expectException(\DomainException::class);
        (new PaymentPolicy())->validateWechatSuccess($this->order(), ['out_trade_no' => 'ORDER-123', 'mchid' => 'm1', 'appid' => 'a1', 'transaction_id' => 'wx1', 'amount' => ['total' => 1, 'currency' => 'CNY']], ['mch_id' => 'm1', 'mini_app_id' => 'a1']);
    }

    public function testRejectsUnwrappedUnverifiedCallback(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        YsdPayService::unwrapCallback(['out_trade_no' => 'ORDER-123']);
    }
}
