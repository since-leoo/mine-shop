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

namespace App\Domain\Order\Service;

use App\Domain\Order\Entity\OrderPaymentEntity;
use App\Domain\Order\Mapper\OrderPaymentMapper;
use App\Domain\Order\Repository\OrderPaymentRepository;
use App\Infrastructure\Model\Order\OrderPayment;

final class DomainOrderPaymentService
{
    public function __construct(
        private readonly OrderPaymentRepository $repository
    ) {}

    /**
     * 创建支付记录.
     */
    public function create(int $orderId, string $orderNo, int $memberId, string $paymentMethod, int $paymentAmount): OrderPayment
    {
        // 1. 通过 Mapper 获取新实体
        $entity = OrderPaymentMapper::getNewEntity();

        // 2. 调用实体的 create 行为方法
        $entity->create($orderId, $orderNo, $memberId, $paymentMethod, $paymentAmount);

        // 3. 持久化
        return $this->repository->create($entity->toArray());
    }

    /**
     * 标记为已支付.
     */
    public function markPaid(
        string $paymentNo,
        int $paidAmount,
        ?string $thirdPartyNo = null,
        ?array $callbackData = null
    ): ?OrderPayment {
        // 1. 获取实体
        $entity = $this->getEntityByPaymentNo($paymentNo);

        // 2. 调用实体行为方法
        $entity->markPaid($paidAmount, $thirdPartyNo, $callbackData);

        // 3. 持久化
        $this->repository->updateByPaymentNo($paymentNo, $entity->toArray());

        // 4. 返回更新后的 Model
        return $this->repository->findByPaymentNo($paymentNo);
    }

    /**
     * 通过订单号标记为已支付.
     */
    public function markPaidByOrderNo(
        string $orderNo,
        int $paidAmount,
        ?string $thirdPartyNo = null,
        ?array $callbackData = null
    ): ?OrderPayment {
        // 1. 获取实体
        $entity = $this->getEntityByOrderNo($orderNo);

        // 2. 调用实体行为方法
        $entity->markPaid($paidAmount, $thirdPartyNo, $callbackData);

        // 3. 持久化
        $this->repository->updateById($entity->getId(), $entity->toArray());

        // 4. 返回更新后的 Model
        return $this->repository->findById($entity->getId());
    }

    /**
     * 标记为失败.
     */
    public function markFailed(string $paymentNo, ?string $remark = null): ?OrderPayment
    {
        // 1. 获取实体
        $entity = $this->getEntityByPaymentNo($paymentNo);

        // 2. 调用实体行为方法
        $entity->markFailed($remark);

        // 3. 持久化
        $this->repository->updateByPaymentNo($paymentNo, $entity->toArray());

        // 4. 返回更新后的 Model
        return $this->repository->findByPaymentNo($paymentNo);
    }

    /**
     * 标记为已取消.
     */
    public function markCancelled(string $paymentNo, ?string $remark = null): ?OrderPayment
    {
        // 1. 获取实体
        $entity = $this->getEntityByPaymentNo($paymentNo);

        // 2. 调用实体行为方法
        $entity->markCancelled($remark);

        // 3. 持久化
        $this->repository->updateByPaymentNo($paymentNo, $entity->toArray());

        // 4. 返回更新后的 Model
        return $this->repository->findByPaymentNo($paymentNo);
    }

    /**
     * 更新第三方响应数据.
     */
    public function updateThirdPartyResponse(string $paymentNo, array $response): ?OrderPayment
    {
        // 1. 获取实体
        $entity = $this->getEntityByPaymentNo($paymentNo);

        // 2. 调用实体行为方法
        $entity->updateThirdPartyResponse($response);

        // 3. 持久化
        $this->repository->updateByPaymentNo($paymentNo, $entity->toArray());

        // 4. 返回更新后的 Model
        return $this->repository->findByPaymentNo($paymentNo);
    }

    /**
     * 增加退款金额.
     */
    public function addRefundAmount(string $paymentNo, int $amount): ?OrderPayment
    {
        // 1. 获取实体
        $entity = $this->getEntityByPaymentNo($paymentNo);

        // 2. 调用实体行为方法
        $entity->addRefundAmount($amount);

        // 3. 持久化
        $this->repository->updateByPaymentNo($paymentNo, $entity->toArray());

        // 4. 返回更新后的 Model
        return $this->repository->findByPaymentNo($paymentNo);
    }

    /**
     * 通过支付单号获取实体.
     */
    public function getEntityByPaymentNo(string $paymentNo): OrderPaymentEntity
    {
        $model = $this->repository->findByPaymentNo($paymentNo);

        if (! $model) {
            throw new \RuntimeException("支付记录不存在: payment_no={$paymentNo}");
        }

        return OrderPaymentMapper::fromModel($model);
    }

    /**
     * 通过订单号获取实体.
     */
    public function getEntityByOrderNo(string $orderNo): OrderPaymentEntity
    {
        $model = $this->repository->findByOrderNo($orderNo);

        if (! $model) {
            throw new \RuntimeException("支付记录不存在: order_no={$orderNo}");
        }

        return OrderPaymentMapper::fromModel($model);
    }

    /**
     * 通过订单 ID 获取实体.
     */
    public function getEntityByOrderId(int $orderId): OrderPaymentEntity
    {
        $model = $this->repository->findByOrderId($orderId);

        if (! $model) {
            throw new \RuntimeException("支付记录不存在: order_id={$orderId}");
        }

        return OrderPaymentMapper::fromModel($model);
    }

    /**
     * 通过第三方支付单号获取实体.
     */
    public function getEntityByThirdPartyNo(string $thirdPartyNo): OrderPaymentEntity
    {
        $model = $this->repository->findByThirdPartyNo($thirdPartyNo);

        if (! $model) {
            throw new \RuntimeException("支付记录不存在: third_party_no={$thirdPartyNo}");
        }

        return OrderPaymentMapper::fromModel($model);
    }
}
