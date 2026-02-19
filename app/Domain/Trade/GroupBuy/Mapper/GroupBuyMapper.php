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

namespace App\Domain\Trade\GroupBuy\Mapper;

use App\Domain\Trade\GroupBuy\Contract\GroupBuyCreateInput;
use App\Domain\Trade\GroupBuy\Entity\GroupBuyEntity;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 团购活动 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
class GroupBuyMapper
{
    /**
     * 从持久化模型重建实体.
     *
     * @param GroupBuy $model 数据库模型
     * @return GroupBuyEntity 团购实体
     */
    public static function fromModel(GroupBuy $model): GroupBuyEntity
    {
        $entity = new GroupBuyEntity();
        $entity->setId((int) $model->id);
        $entity->setTitle($model->title);
        $entity->setDescription($model->description);
        $entity->setProductId((int) $model->product_id);
        $entity->setSkuId((int) $model->sku_id);
        $entity->setOriginalPrice((int) $model->original_price);
        $entity->setGroupPrice((int) $model->group_price);
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
     * 从 DTO 创建新实体.
     *
     * @param GroupBuyCreateInput $dto 创建输入 DTO
     * @return GroupBuyEntity 团购实体
     */
    public static function fromDto(GroupBuyCreateInput $dto): GroupBuyEntity
    {
        $entity = new GroupBuyEntity();
        $entity->create($dto);
        return $entity;
    }

    /**
     * 获取空实体.
     *
     * @deprecated 使用 fromDto 代替
     */
    public static function getNewEntity(): GroupBuyEntity
    {
        return new GroupBuyEntity();
    }
}
