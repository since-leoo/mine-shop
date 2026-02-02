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

namespace App\Interface\Api\Controller\V1;

use App\Application\Member\Contract\MemberQueryInterface;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Support\CurrentMember;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;

#[Controller(prefix: '/api/v1/member')]
#[Middleware(TokenMiddleware::class)]
final class MemberController extends AbstractController
{
    public function __construct(
        private readonly MemberQueryInterface $queryService,
        private readonly CurrentMember $currentMember
    ) {}

    #[GetMapping(path: 'profile')]
    public function profile(): Result
    {
        $member = $this->queryService->detail($this->currentMember->id());

        if ($member === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        return $this->success(['member' => $member], '获取成功');
    }
}
