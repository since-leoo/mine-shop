<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Application\Admin;

use Plugin\Since\GroupBuy\Infrastructure\Model\GroupBuy;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyService;

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
