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
use App\Domain\Trade\Shipping\Service\DomainShippingTemplateService;
use Hyperf\DbConnection\Db;

/**
 * 运费模板命令服务：处理所有写操作.
 */
final class AppShippingTemplateCommandService
{
    public function __construct(
        private readonly DomainShippingTemplateService $shippingTemplateService,
    ) {}

    /**
     * 创建运费模板.
     *
     * @param ShippingTemplateInput $input 运费模板输入数据
     * @return ShippingTemplateEntity 创建后的运费模板实体
     */
    public function create(ShippingTemplateInput $input): ShippingTemplateEntity
    {
        return Db::transaction(fn () => $this->shippingTemplateService->create($input));
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
        return Db::transaction(fn () => $this->shippingTemplateService->update($id, $input));
    }

    /**
     * 删除运费模板.
     *
     * @param int $id 模板ID
     */
    public function delete(int $id): void
    {
        Db::transaction(fn () => $this->shippingTemplateService->delete($id));
    }
}
