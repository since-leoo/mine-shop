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

namespace Plugin\Since\Seckill\Application\Admin;

use Plugin\Since\Seckill\Domain\Service\DomainSeckillSessionService;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillSession;

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
