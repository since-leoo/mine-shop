<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Application\Api\Payment;

use App\Application\Api\Payment\AppApiOrderPaymentService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Member\Api\Command\DomainApiMemberCommandService;
use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Trade\Order\Api\Command\DomainApiOrderCommandService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Payment\DomainPayService;
use App\Infrastructure\Exception\System\BusinessException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AppApiOrderPaymentServiceTest extends TestCase
{
    public function testBalancePaymentRejectsAmountBelowConfiguredMinimum(): void
    {
        $service = $this->makeService(
            order: $this->makeOrder(payAmount: 999),
            payment: $this->makePaymentSetting(balanceConfig: ['min_amount' => 1000]),
            payService: $this->createMock(DomainPayService::class),
        );

        try {
            $service->payment(1, ['order_no' => 'ORD202606060001', 'pay_method' => 'balance']);
            self::fail('Expected BusinessException');
        } catch (BusinessException $exception) {
            self::assertSame('余额支付金额不能低于 10.00 元', $exception->getResponse()->message);
        }
    }

    public function testBalancePaymentRejectsAmountAboveConfiguredMaximum(): void
    {
        $service = $this->makeService(
            order: $this->makeOrder(payAmount: 5001),
            payment: $this->makePaymentSetting(balanceConfig: ['max_amount' => 5000]),
            payService: $this->createMock(DomainPayService::class),
        );

        try {
            $service->payment(1, ['order_no' => 'ORD202606060001', 'pay_method' => 'balance']);
            self::fail('Expected BusinessException');
        } catch (BusinessException $exception) {
            self::assertSame('余额支付金额不能高于 50.00 元', $exception->getResponse()->message);
        }
    }

    public function testBalancePaymentPassesThroughWhenNoLimitConfigExists(): void
    {
        $payService = $this->createMock(DomainPayService::class);
        $payService->expects(self::once())
            ->method('init')
            ->willReturnSelf();
        $payService->expects(self::once())
            ->method('payByBalance')
            ->willReturn(['is_paid' => true]);

        $service = $this->makeService(
            order: $this->makeOrder(payAmount: 999),
            payment: $this->makePaymentSetting(balanceConfig: ['display_name' => '钱包']),
            payService: $payService,
        );

        self::assertSame(['is_paid' => true], $service->payment(1, ['order_no' => 'ORD202606060001', 'pay_method' => 'balance']));
    }

    public function testBalancePaymentRejectsAmountAboveConfiguredDailyLimit(): void
    {
        $service = $this->makeService(
            order: $this->makeOrder(payAmount: 10001),
            payment: $this->makePaymentSetting(balanceConfig: ['daily_limit' => 10000]),
            payService: $this->createMock(DomainPayService::class),
        );

        try {
            $service->payment(1, ['order_no' => 'ORD202606060001', 'pay_method' => 'balance']);
            self::fail('Expected BusinessException');
        } catch (BusinessException $exception) {
            self::assertSame('余额支付金额不能超过每日限额 100.00 元', $exception->getResponse()->message);
        }
    }

    private function makeService(OrderEntity $order, PaymentSetting $payment, DomainPayService $payService): AppApiOrderPaymentService
    {
        $orderCommandService = $this->createMock(DomainApiOrderCommandService::class);
        $orderCommandService->method('getEntity')->willReturn($order);

        $memberCommandService = $this->createMock(DomainApiMemberCommandService::class);
        $memberCommandService->method('getEntity')->willReturn($this->makeMember());

        $mallSettingService = $this->createMock(DomainMallSettingService::class);
        $mallSettingService->method('payment')->willReturn($payment);

        return new AppApiOrderPaymentService(
            $orderCommandService,
            $memberCommandService,
            $mallSettingService,
            $payService,
        );
    }

    private function makePaymentSetting(array $balanceConfig): PaymentSetting
    {
        return new PaymentSetting(
            false,
            [],
            true,
            7,
            true,
            $balanceConfig,
        );
    }

    private function makeOrder(int $payAmount): OrderEntity
    {
        $order = new OrderEntity();
        $order->setId(10);
        $order->setMemberId(1);
        $order->setOrderNo('ORD202606060001');
        $order->setPayAmount($payAmount);
        $order->setTotalAmount($payAmount);

        return $order;
    }

    private function makeMember(): MemberEntity
    {
        $member = new MemberEntity();
        $member->setId(1);
        $member->setNickname('tester');

        return $member;
    }
}
