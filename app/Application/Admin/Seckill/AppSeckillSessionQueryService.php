<?php

declare(strict_types=1);

namespace App\Application\Admin\Seckill;

use App\Domain\Trade\Seckill\Service\DomainSeckillSessionService;
use App\Infrastructure\Model\Seckill\SeckillSession;

final class AppSeckillSessionQueryService
{
    public function __construct(private readonly DomainSeckillSessionService $sessionService) {}

    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->sessionService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?SeckillSession
    {
        /** @var null|SeckillSession $session */
        $session = $this->sessionService->findById($id);
        $session?->load(['activity', 'products']);
        return $session;
    }

    public function findByActivityId(int $activityId): array
    {
        return $this->sessionService->findByActivityId($activityId);
    }
}
