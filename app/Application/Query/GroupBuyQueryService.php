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

namespace App\Application\Query;

use App\Domain\GroupBuy\Service\GroupBuyService;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

/**
 * 团购活动查询服务：处理所有读操作.
 */
final class GroupBuyQueryService
{
    public function __construct(private readonly GroupBuyService $groupBuyService) {}

    /**
     * 分页查询团购活动.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->groupBuyService->page($filters, $page, $pageSize);
    }

    /**
     * 根据ID查找团购活动.
     */
    public function find(int $id): ?GroupBuy
    {
        /** @var GroupBuy|null $groupBuy */
        $groupBuy = $this->groupBuyService->findById($id);
        $groupBuy?->load(['product:id,name,main_image', 'sku:id,sku_name,sale_price']);
        return $groupBuy;
    }

    /**
     * 获取统计数据.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return $this->groupBuyService->getStatistics();
    }
}
