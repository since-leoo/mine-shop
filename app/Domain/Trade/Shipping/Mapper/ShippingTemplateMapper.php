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

namespace App\Domain\Trade\Shipping\Mapper;

use App\Domain\Trade\Shipping\Contract\ShippingTemplateInput;
use App\Domain\Trade\Shipping\Entity\ShippingTemplateEntity;
use App\Infrastructure\Model\Shipping\ShippingTemplate;

/**
 * 运费模板 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
final class ShippingTemplateMapper
{
    /**
     * 从持久化模型重建实体.
     *
     * @param ShippingTemplate $model 数据库模型
     * @return ShippingTemplateEntity 运费模板实体
     */
    public static function fromModel(ShippingTemplate $model): ShippingTemplateEntity
    {
        $entity = new ShippingTemplateEntity();

        $entity->setId((int) $model->id);
        $entity->setName($model->name);
        $entity->setChargeType($model->charge_type);
        $entity->setRules($model->rules);
        $entity->setFreeRules($model->free_rules);
        $entity->setIsDefault($model->is_default);
        $entity->setStatus($model->status);

        return $entity;
    }

    /**
     * 从 DTO 创建新实体.
     *
     * @param ShippingTemplateInput $dto 运费模板输入 DTO
     * @return ShippingTemplateEntity 运费模板实体
     */
    public static function fromDto(ShippingTemplateInput $dto): ShippingTemplateEntity
    {
        $entity = new ShippingTemplateEntity();
        $entity->create($dto);
        return $entity;
    }

    /**
     * 将 Entity 转换为持久化数组.
     *
     * @return array<string, mixed>
     */
    public static function toArray(ShippingTemplateEntity $entity): array
    {
        return $entity->toArray();
    }

    /**
     * 获取新实体.
     *
     * @deprecated 使用 fromDto 代替
     */
    public static function getNewEntity(): ShippingTemplateEntity
    {
        return new ShippingTemplateEntity();
    }
}
