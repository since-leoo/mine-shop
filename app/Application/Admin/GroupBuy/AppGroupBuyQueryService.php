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

namespace App\Application\Admin\GroupBuy;

use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyService;
use App\Infrastructure\Model\GroupBuy\GroupBuy;

final class AppGroupBuyQueryService
{
    public function __construct(private readonly DomainGroupBuyService $groupBuyService) {}

    /** @return array<string, mixed> */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->groupBuyService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?GroupBuy
    {
        /** @var null|GroupBuy $groupBuy */
        $groupBuy = $this->groupBuyService->findById($id);
        $groupBuy?->load(['product:id,name,main_image', 'sku:id,sku_name,sale_price']);
        return $groupBuy;
    }

    public function stats(): array
    {
        return $this->groupBuyService->getStatistics();
    }
}
