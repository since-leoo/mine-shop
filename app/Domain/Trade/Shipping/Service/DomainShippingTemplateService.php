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

namespace App\Domain\Trade\Shipping\Service;

use App\Domain\Trade\Shipping\Entity\ShippingTemplateEntity;
use App\Domain\Trade\Shipping\Mapper\ShippingTemplateMapper;
use App\Domain\Trade\Shipping\Repository\ShippingTemplateRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Shipping\ShippingTemplate;
use App\Interface\Common\ResultCode;

/**
 * 运费模板领域服务.
 *
 * 负责运费模板的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainShippingTemplateService extends IService
{
    public function __construct(public readonly ShippingTemplateRepository $repository) {}

    /**
     * 创建运费模板.
     *
     * @param ShippingTemplateEntity $entity 运费模板实体
     * @return ShippingTemplateEntity 创建后的运费模板实体（含 ID）
     */
    public function create(ShippingTemplateEntity $entity): ShippingTemplateEntity
    {
        $model = $this->repository->store($entity->toArray());
        $entity->setId((int) $model->id);
        return $entity;
    }

    /**
     * 更新运费模板.
     *
     * @param ShippingTemplateEntity $entity 更新后的实体
     * @return ShippingTemplateEntity 更新后的运费模板实体
     */
    public function update(ShippingTemplateEntity $entity): ShippingTemplateEntity
    {
        $this->repository->update($entity->getId(), $entity->toArray());
        return $entity;
    }

    /**
     * 删除运费模板.
     *
     * @param int $id 模板 ID
     * @throws BusinessException 当模板不存在或正在被商品使用时
     */
    public function delete(int $id): void
    {
        // 确认模板存在
        $this->getEntity($id);

        // 检查是否被商品使用
        if ($this->repository->isUsedByProducts($id)) {
            throw new BusinessException(ResultCode::FORBIDDEN, '该模板正在被商品使用，无法删除');
        }

        $this->repository->deleteById($id);
    }

    /**
     * 根据 ID 查找运费模板实体.
     *
     * @param int $id 模板 ID
     * @return ShippingTemplateEntity 运费模板实体
     * @throws BusinessException 当模板不存在时
     */
    public function getEntity(int $id): ShippingTemplateEntity
    {
        /** @var null|ShippingTemplate $model */
        $model = $this->findById($id);

        if (! $model) {
            throw new BusinessException(ResultCode::NOT_FOUND, '运费模板不存在');
        }

        return ShippingTemplateMapper::fromModel($model);
    }
}
