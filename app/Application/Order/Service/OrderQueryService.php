<?php

declare(strict_types=1);

namespace App\Application\Order\Service;

use App\Domain\Order\Service\OrderService;
use Hyperf\Di\Annotation\Inject;

final class OrderQueryService
{
    #[Inject]
    private readonly OrderService $orderService;

    /**
     * @param array<string, mixed> $filters
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->orderService->page($filters, $page, $pageSize);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function stats(array $filters): array
    {
        return $this->orderService->stats($filters);
    }

    public function detail(int $id): ?array
    {
        return $this->orderService->findDetail($id);
    }
}
