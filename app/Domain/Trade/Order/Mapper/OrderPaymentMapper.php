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

namespace App\Domain\Trade\Order\Mapper;

use App\Domain\Trade\Order\Entity\OrderPaymentEntity;
use App\Infrastructure\Model\Order\OrderPayment;

class OrderPaymentMapper
{
    /**
     * 从 Model 转换为 Entity.
     */
    public static function fromModel(OrderPayment $model): OrderPaymentEntity
    {
        $entity = new OrderPaymentEntity();

        $entity->setId($model->id);
        $entity->setPaymentNo($model->payment_no);
        $entity->setOrderId($model->order_id);
        $entity->setOrderNo($model->order_no);
        $entity->setMemberId($model->member_id);
        $entity->setPaymentMethod($model->payment_method);
        $entity->setPaymentAmount($model->payment_amount);
        $entity->setPaidAmount($model->paid_amount);
        $entity->setRefundAmount($model->refund_amount);
        $entity->setCurrency($model->currency);
        $entity->setStatus($model->status);
        $entity->setThirdPartyNo($model->third_party_no);
        $entity->setThirdPartyResponse($model->third_party_response);
        $entity->setCallbackData($model->callback_data);
        $entity->setPaidAt($model->paid_at?->toDateTimeString());
        $entity->setExpiredAt($model->expired_at?->toDateTimeString());
        $entity->setRemark($model->remark);
        $entity->setExtraData($model->extra_data);

        return $entity;
    }

    /**
     * 获取新实体.
     */
    public static function getNewEntity(): OrderPaymentEntity
    {
        return new OrderPaymentEntity();
    }
}
