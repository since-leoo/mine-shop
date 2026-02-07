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

namespace App\Domain\Trade\Payment;

use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Enum\MemberWalletTransactionType;
use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Payment\Enum\PayType;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Service\Pay\YsdPayService;
use App\Infrastructure\Traits\PaymentTrait;
use App\Interface\Common\ResultCode;
use Hyperf\DbConnection\Annotation\Transactional;
use Yansongda\Artful\Exception\ContainerException;

class DomainPayService
{
    use PaymentTrait;

    private OrderEntity $orderEntity;

    private MemberEntity $memberEntity;

    public function __construct(
        private readonly DomainOrderService $orderService,
        private readonly YsdPayService $payService,
        private readonly DomainMemberWalletService $walletService,
        private readonly DomainOrderPaymentService $paymentService,
    ) {}

    /**
     * 初始化.
     * @return $this
     */
    public function init(OrderEntity $orderEntity, MemberEntity $memberEntity): self
    {
        $this->orderEntity = $orderEntity;
        $this->memberEntity = $memberEntity;
        return $this;
    }

    /**
     * 支付.
     * @throws ContainerException
     */
    public function payByWechat(array $config = []): array
    {
        $baseConfig = config('pay.wechat.default');

        if (! isset($baseConfig[$this->orderEntity->getPayMethod()])) {
            throw new BusinessException(ResultCode::METHOD_NOT_ALLOWED, '支付方式不存在');
        }

        // 提取系统对应支付方式的配置
        $methodConfig = $baseConfig[$this->orderEntity->getPayMethod()];
        // 处理合并pay 配置信息
        $config = array_merge($methodConfig, $config);

        // 创建支付记录
        $this->paymentService->create(
            $this->orderEntity->getId(),
            $this->orderEntity->getOrderNo(),
            $this->memberEntity->getId(),
            $this->orderEntity->getPayMethod(),
            $this->orderEntity->getTotalAmount()
        );

        $payInfo = $this->payService->pay(self::build(), $config);

        if (empty($payInfo)) {
            throw new BusinessException(ResultCode::FAIL, '支付失败,获取支付信息失败');
        }

        return $payInfo;
    }

    /**
     * 余额支付.
     */
    #[Transactional]
    public function payByBalance(): array
    {
        // 创建支付记录
        $payment = $this->paymentService->create(
            $this->orderEntity->getId(),
            $this->orderEntity->getOrderNo(),
            $this->memberEntity->getId(),
            PayType::BALANCE->value,
            $this->orderEntity->getTotalAmount()
        );

        // 通过 WalletService 加载钱包实体（而非依赖 MemberEntity 上未加载的 wallet）
        $walletEntity = $this->walletService->getEntity(
            $this->memberEntity->getId(),
            'balance'
        );

        // 先设置变动金额（负数表示消费扣款），再执行变更
        $walletEntity->setChangeBalance(-$this->orderEntity->getPayAmount());
        $walletEntity->setSource(MemberWalletTransactionType::Consume->value);
        $walletEntity->setRemark('余额支付:' . $this->orderEntity->getOrderNo() . '订单支付成功');
        $walletEntity->changeBalance();

        // 更新订单支付信息
        $this->orderEntity->setPayMethod(PayType::BALANCE->value);
        $this->orderEntity->setPayNo($payment->payment_no);
        $this->orderEntity->markPaid();
        $this->orderService->update($this->orderEntity);

        // 标记支付记录为已支付
        $this->paymentService->markPaid($payment->payment_no, $this->orderEntity->getTotalAmount(), $payment->payment_no);

        // 持久化钱包变更
        $this->walletService->saveEntity($walletEntity);

        // 发送钱包余额变动记录事件
        event(new MemberBalanceAdjusted(
            $this->memberEntity->getId(),
            $walletEntity->getId(),
            $walletEntity->getType(),
            -$this->orderEntity->getPayAmount(),
            $walletEntity->getBeforeBalance(),
            $walletEntity->getAfterBalance(),
            'order',
            '订单支付:' . $this->orderEntity->getOrderNo(),
            ['type' => 'member', 'id' => $this->memberEntity->getId(), 'name' => $this->memberEntity->getNickname()]
        ));

        return ['is_paid' => true];
    }

    /**
     * 支付回调通知.
     */
    public function notify(OrderEntity $orderEntity, array $callback = []): void
    {
        if ($orderEntity->getPayStatus() === OrderStatus::PAID->value) {
            return;
        }

        if (! empty($callback['trade_state']) && $callback['trade_state'] === 'SUCCESS') {
            // 更新订单支付信息
            $orderEntity->setPayMethod(PayType::WECHAT->value);
            $orderEntity->setPayNo($callback['transaction_id']);
            $orderEntity->setPayAmount($callback['amount']);
            $orderEntity->markPaid();
            $this->orderService->update($orderEntity);

            // 更新支付记录
            $this->paymentService->markPaidByOrderNo(
                $orderEntity->getOrderNo(),
                $callback['amount'],
                $callback['transaction_id'],
                $callback
            );
        }
    }
}
