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

use App\Domain\Member\Api\MemberCenterReadService;

final class MemberCenterQueryApiService
{
    public function __construct(private readonly MemberCenterReadService $readService) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(int $memberId): array
    {
        return $this->readService->profile($memberId);
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(int $memberId): array
    {
        return $this->readService->overview($memberId);
    }
}
