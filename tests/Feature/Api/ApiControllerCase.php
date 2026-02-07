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

namespace HyperfTests\Feature\Api;

use HyperfTests\HttpTestCase;

abstract class ApiControllerCase extends HttpTestCase
{
    protected function issueMemberToken(int $memberId): string
    {
        /** @var TokenService $tokenService */
        $tokenService = make(TokenService::class);

        return $tokenService
            ->using('api')
            ->buildAccessToken('member:' . $memberId);
    }

    protected function authHeadersByMemberId(int $memberId): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->issueMemberToken($memberId),
        ];
    }
}
