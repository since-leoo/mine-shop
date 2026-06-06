<?php

declare(strict_types=1);

namespace App\Infrastructure\Crontab;

use App\Domain\Member\Service\DomainMemberPointsExpireService;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Log\LoggerInterface;

#[Crontab(
    name: 'points-expire',
    rule: '0 3 * * *',
    callback: 'execute',
    memo: '会员积分过期扣减',
    enable: true
)]
class PointsExpireCrontab
{
    public function __construct(
        private readonly DomainMemberPointsExpireService $pointsExpireService,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(): void
    {
        try {
            $result = $this->pointsExpireService->expireDuePoints();
            $this->logger->info('[PointsExpire] expired member points', $result);
        } catch (\Throwable $e) {
            $this->logger->error('[PointsExpire] failed to expire member points', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
