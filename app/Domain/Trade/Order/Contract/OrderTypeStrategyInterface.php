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

namespace App\Domain\Trade\Order\Contract;

use App\Domain\Trade\Order\Entity\OrderEntity;
use Psr\Container\ContainerInterface;

interface OrderTypeStrategyInterface
{
    /**
     * 订单类型.
     */
    public function type(): string;

    /**
     * 订单创建前的验证（各策略自行决定验证逻辑）.
     */
    public function validate(OrderEntity $orderEntity): void;

    /**
     * 创建订单草稿（各策略自行决定草稿逻辑）.
     */
    public function buildDraft(OrderEntity $orderEntity): OrderEntity;

    /**
     * 计算并设置订单运费（各策略自行决定运费逻辑）.
     */
    public function applyFreight(OrderEntity $orderEntity): void;

    /**
     * 应用优惠券（各策略自行决定优惠券逻辑，一次只能用一张）.
     */
    public function applyCoupon(OrderEntity $orderEntity, ?int $couponId): void;

    /**
     * 从快照重建活动实体（异步 Job 中调用，各策略自行恢复所需上下文）.
     */
    public function rehydrate(OrderEntity $orderEntity, ContainerInterface $container): void;

    /**
     * 核销优惠券（异步 Job 入库后调用，各策略自行决定核销逻辑）.
     */

    /**
     * 订单创建成功后（异步 Job 入库后调用，各策略自行决定后续逻辑）.
     */
    public function postCreate(OrderEntity $orderEntity): void;
}
