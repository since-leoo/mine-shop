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

namespace App\Domain\Trade\AfterSale\Listener;

use App\Domain\Member\Enum\MemberWalletTransactionType;
use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\AfterSale\Mapper\AfterSaleMapper;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Enum\PaymentStatus;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Payment\Enum\PayType;
use App\Infrastructure\Model\AfterSale\AfterSale;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

final class ProcessAfterSaleRefundSucceededListener implements ListenerInterface
{
    public function __construct(
        private readonly AfterSaleRepository $afterSaleRepository,
        private readonly DomainOrderPaymentService $orderPaymentService,
        private readonly OrderPaymentRepository $orderPaymentRepository,
        private readonly DomainOrderService $orderService,
        private readonly DomainMemberWalletService $walletService,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function listen(): array
    {
        return [
            AfterSaleRefundSucceeded::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof AfterSaleRefundSucceeded) {
            return;
        }

        /** @var AfterSale $afterSaleModel */
        $afterSaleModel = $this->afterSaleRepository->findById($event->afterSaleId);
        if ($afterSaleModel === null) {
            throw new RuntimeException('售后单不存在');
        }

        $afterSaleEntity = AfterSaleMapper::fromModel($afterSaleModel);
        $afterSaleEntity->markRefunded();
        $this->afterSaleRepository->updateFromEntity($afterSaleEntity);

        $payment = $this->orderPaymentService->addRefundAmount($event->paymentNo, $event->refundAmount);
        $paymentMethod = $event->paymentMethod;
        if ($paymentMethod === '') {
            $paymentMethod = $this->orderPaymentRepository->findByPaymentNo($event->paymentNo)?->payment_method ?? '';
        }

        if ($payment !== null && $payment->status === PaymentStatus::REFUNDED->value) {
            $orderEntity = $this->orderService->getEntity($event->orderId);
            $orderEntity->setPayStatus(PaymentStatus::REFUNDED->value);
            $orderEntity->setStatus(OrderStatus::REFUNDED->value);
            $this->orderService->update($orderEntity);
        }

        if ($paymentMethod === PayType::BALANCE->value) {
            $this->refundBalance($event);
        }
    }

    /**
     * 处理退回陀额.
     */
    private function refundBalance(AfterSaleRefundSucceeded $event): void
    {
        $walletEntity = $this->walletService->getEntity($event->memberId, 'balance');
        $walletEntity->setChangeBalance($event->refundAmount);
        $walletEntity->setSource(MemberWalletTransactionType::Refund->value);
        $walletEntity->setRemark('订单退款：' . $event->refundNo);
        $walletEntity->changeBalance();
        $this->walletService->saveEntity($walletEntity);

        $this->dispatcher->dispatch(new MemberBalanceAdjusted(
            memberId: $event->memberId,
            walletId: $walletEntity->getId(),
            walletType: 'balance',
            changeAmount: $event->refundAmount,
            beforeBalance: $walletEntity->getBeforeBalance(),
            afterBalance: $walletEntity->getAfterBalance(),
            source: MemberWalletTransactionType::Refund->value,
            remark: '订单退款：' . $event->refundNo,
            operator: [
                'type' => 'admin',
                'id' => $event->operatorId,
                'name' => $event->operatorName,
            ],
            relatedType: 'after_sale',
            relatedId: $event->afterSaleId,
        ));
    }
}
