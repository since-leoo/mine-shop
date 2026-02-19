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

namespace App\Application\Admin\Shipping;

use App\Domain\Trade\Shipping\Contract\ShippingTemplateInput;
use App\Domain\Trade\Shipping\Entity\ShippingTemplateEntity;
use App\Domain\Trade\Shipping\Mapper\ShippingTemplateMapper;
use App\Domain\Trade\Shipping\Service\DomainShippingTemplateService;
use Hyperf\DbConnection\Db;

/**
 * 运费模板应用层命令服务.
 *
 * 负责协调领域服务，处理 DTO 到实体的转换。
 */
final class AppShippingTemplateCommandService
{
    public function __construct(
        private readonly DomainShippingTemplateService $shippingTemplateService,
    ) {}

    /**
     * 创建运费模板.
     *
     * @param ShippingTemplateInput $input 运费模板输入 DTO
     * @return ShippingTemplateEntity 创建后的运费模板实体
     */
    public function create(ShippingTemplateInput $input): ShippingTemplateEntity
    {
        // 使用 Mapper 将 DTO 转换为实体
        $entity = ShippingTemplateMapper::fromDto($input);
        return Db::transaction(fn () => $this->shippingTemplateService->create($entity));
    }

    /**
     * 更新运费模板.
     *
     * @param int $id 模板 ID
     * @param ShippingTemplateInput $input 运费模板输入 DTO
     * @return ShippingTemplateEntity 更新后的运费模板实体
     */
    public function update(int $id, ShippingTemplateInput $input): ShippingTemplateEntity
    {
        // 从数据库获取实体并更新
        $entity = $this->shippingTemplateService->getEntity($id);
        $entity->update($input);
        return Db::transaction(fn () => $this->shippingTemplateService->update($entity));
    }

    /**
     * 删除运费模板.
     *
     * @param int $id 模板 ID
     */
    public function delete(int $id): void
    {
        Db::transaction(fn () => $this->shippingTemplateService->delete($id));
    }
}
