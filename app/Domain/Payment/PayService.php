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

namespace App\Domain\Payment;

use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Enum\MemberWalletTransactionType;
use App\Domain\Member\Service\MemberWalletService;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Enum\OrderStatus;
use App\Domain\Order\Service\OrderService;
use App\Domain\Payment\Enum\PayType;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Service\Pay\YsdPayService;
use App\Infrastructure\Traits\PaymentTrait;
use App\Interface\Common\ResultCode;
use Yansongda\Artful\Exception\ContainerException;

class PayService
{
    use PaymentTrait;

    private OrderEntity $orderEntity;

    private MemberEntity $memberEntity;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly YsdPayService $payService,
        private readonly MemberWalletService $walletService,
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

        $payInfo = $this->payService->pay(self::build(), $config);

        if (empty($payInfo)) {
            throw new BusinessException(ResultCode::FAIL, '支付失败,获取支付信息失败');
        }

        return $payInfo;
    }

    /**
     * 余额支付.
     */
    public function payByBalance(): array
    {
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

        $this->orderEntity->setPayMethod(PayType::BALANCE->value);
        $this->orderEntity->setPayNo(uniqid());
        $this->orderEntity->setPayAmount($this->orderEntity->getTotalAmount());
        $this->orderEntity->markPaid();

        $this->orderService->update($this->orderEntity);
        // 持久化钱包变更
        $this->walletService->saveEntity($walletEntity);

        return [];
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
            $orderEntity->setPayMethod(PayType::WECHAT->value);
            $orderEntity->setPayNo($callback['transaction_id']);
            $orderEntity->setPayAmount($callback['amount']);
            $orderEntity->markPaid();
            $this->orderService->update($orderEntity);
        }
    }
}
