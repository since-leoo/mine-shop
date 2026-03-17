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

namespace App\Domain\Trade\AfterSale\Mapper;

use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Infrastructure\Model\AfterSale\AfterSale;

class AfterSaleMapper
{
    public static function getNewEntity(): AfterSaleEntity
    {
        return new AfterSaleEntity();
    }

    public static function fromModel(AfterSale $model): AfterSaleEntity
    {
        $entity = new AfterSaleEntity();
        $entity->setId((int) $model->id);
        $entity->setAfterSaleNo((string) $model->after_sale_no);
        $entity->setOrderId((int) $model->order_id);
        $entity->setOrderItemId((int) $model->order_item_id);
        $entity->setMemberId((int) $model->member_id);
        $entity->setType((string) $model->type);
        $entity->setStatus((string) $model->status);
        $entity->setRefundStatus((string) $model->refund_status);
        $entity->setReturnStatus((string) $model->return_status);
        $entity->setApplyAmount((int) $model->apply_amount);
        $entity->setRefundAmount((int) $model->refund_amount);
        $entity->setQuantity((int) $model->quantity);
        $entity->setReason((string) $model->reason);
        $entity->setDescription($model->description);
        $entity->setImages($model->images);
        $entity->setBuyerReturnLogisticsCompany($model->buyer_return_logistics_company);
        $entity->setBuyerReturnLogisticsNo($model->buyer_return_logistics_no);
        $entity->setReshipLogisticsCompany($model->reship_logistics_company);
        $entity->setReshipLogisticsNo($model->reship_logistics_no);

        return $entity;
    }
}