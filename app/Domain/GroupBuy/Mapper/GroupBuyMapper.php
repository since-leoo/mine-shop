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

namespace App\Domain\GroupBuy\Mapper;

use App\Domain\GroupBuy\Entity\GroupBuyEntity;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 团购活动映射器.
 */
class GroupBuyMapper
{
    /**
     * 从 Model 转换为 Entity.
     */
    public static function fromModel(GroupBuy $model): GroupBuyEntity
    {
        $entity = new GroupBuyEntity();

        $entity->setId((int) $model->id);
        $entity->setTitle($model->title);
        $entity->setDescription($model->description);
        $entity->setProductId((int) $model->product_id);
        $entity->setSkuId((int) $model->sku_id);
        $entity->setOriginalPrice((float) $model->original_price);
        $entity->setGroupPrice((float) $model->group_price);
        $entity->setMinPeople((int) $model->min_people);
        $entity->setMaxPeople((int) $model->max_people);
        $entity->setStartTime($model->start_time->format('Y-m-d H:i:s'));
        $entity->setEndTime($model->end_time->format('Y-m-d H:i:s'));
        $entity->setGroupTimeLimit((int) $model->group_time_limit);
        $entity->setStatus($model->status);
        $entity->setTotalQuantity((int) $model->total_quantity);
        $entity->setSoldQuantity((int) $model->sold_quantity);
        $entity->setGroupCount((int) $model->group_count);
        $entity->setSuccessGroupCount((int) $model->success_group_count);
        $entity->setSortOrder((int) $model->sort_order);
        $entity->setIsEnabled((bool) $model->is_enabled);
        $entity->setRules($model->rules);
        $entity->setImages($model->images);
        $entity->setRemark($model->remark);

        return $entity;
    }

    /**
     * 获取新实体.
     */
    public static function getNewEntity(): GroupBuyEntity
    {
        return new GroupBuyEntity();
    }
}
