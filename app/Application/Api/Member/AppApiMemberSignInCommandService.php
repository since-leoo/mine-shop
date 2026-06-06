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

namespace App\Application\Api\Member;

use App\Domain\Member\Service\DomainMemberPointsService;
use Psr\EventDispatcher\EventDispatcherInterface;

final class AppApiMemberSignInCommandService
{
    public function __construct(
        private readonly DomainMemberPointsService $pointsService,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @return array{signed: bool, points: int, already_signed: bool}
     */
    public function signIn(int $memberId): array
    {
        $event = $this->pointsService->grantSignInPoints($memberId);
        if ($event === null) {
            return [
                'signed' => true,
                'points' => 0,
                'already_signed' => true,
            ];
        }

        $this->dispatcher->dispatch($event);

        return [
            'signed' => true,
            'points' => (int) $event->changeAmount,
            'already_signed' => false,
        ];
    }
}
