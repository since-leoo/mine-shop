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

namespace App\Application\Admin\Trade;

use App\Domain\Trade\Shipping\Entity\ShippingTemplateEntity;
use App\Domain\Trade\Shipping\Service\DomainShippingTemplateService;
use App\Infrastructure\Exception\System\BusinessException;

/**
 * 运费模板查询服务：处理所有读操作.
 */
final class AppShippingTemplateQueryService
{
    public function __construct(private readonly DomainShippingTemplateService $shippingTemplateService) {}

    /**
     * 分页查询运费模板列表.
     *
     * @param array<string, mixed> $params 查询参数
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array<string, mixed>
     */
    public function page(array $params, int $page = 1, int $pageSize = 10): array
    {
        return $this->shippingTemplateService->page($params, $page, $pageSize);
    }

    /**
     * 根据ID获取运费模板详情.
     *
     * @param int $id 模板ID
     * @return ShippingTemplateEntity 运费模板实体
     * @throws BusinessException 当模板不存在时
     */
    public function getById(int $id): ShippingTemplateEntity
    {
        return $this->shippingTemplateService->getEntity($id);
    }
}
