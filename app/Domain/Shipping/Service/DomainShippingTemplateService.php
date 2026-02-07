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

namespace App\Domain\Shipping\Service;

use App\Domain\Shipping\Contract\ShippingTemplateInput;
use App\Domain\Shipping\Entity\ShippingTemplateEntity;
use App\Domain\Shipping\Mapper\ShippingTemplateMapper;
use App\Domain\Shipping\Repository\ShippingTemplateRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Shipping\ShippingTemplate;
use App\Interface\Common\ResultCode;

/**
 * 运费模板领域服务：封装运费模板相关的核心业务逻辑.
 */
final class DomainShippingTemplateService extends IService
{
    public function __construct(public readonly ShippingTemplateRepository $repository) {}

    /**
     * 创建运费模板.
     *
     * @param ShippingTemplateInput $input 运费模板输入数据
     * @return ShippingTemplateEntity 创建后的运费模板实体
     */
    public function create(ShippingTemplateInput $input): ShippingTemplateEntity
    {
        $entity = ShippingTemplateMapper::getNewEntity();
        $entity->create($input);

        $model = $this->repository->store($entity->toArray());
        $entity->setId((int) $model->id);

        return $entity;
    }

    /**
     * 更新运费模板.
     *
     * @param int $id 模板ID
     * @param ShippingTemplateInput $input 运费模板输入数据
     * @return ShippingTemplateEntity 更新后的运费模板实体
     */
    public function update(int $id, ShippingTemplateInput $input): ShippingTemplateEntity
    {
        $entity = $this->getEntity($id);
        $entity->update($input);

        $this->repository->update($id, $entity->toArray());

        return $entity;
    }

    /**
     * 删除运费模板.
     *
     * @param int $id 模板ID
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
     * 根据ID查找运费模板实体.
     *
     * @param int $id 模板ID
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
