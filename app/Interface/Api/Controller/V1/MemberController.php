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

use App\Application\Api\Member\MemberCenterQueryApiService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;

#[Controller(prefix: '/api/v1/member')]
#[Middleware(TokenMiddleware::class)]
final class MemberController extends AbstractController
{
    public function __construct(
        private readonly MemberCenterQueryApiService $memberCenterService,
        private readonly CurrentMember $currentMember
    ) {}

    #[GetMapping(path: 'profile')]
    public function profile(): Result
    {
        $profile = $this->memberCenterService->profile($this->currentMember->id());
        return $this->success(['member' => $profile], '获取成功');
    }

    #[GetMapping(path: 'center')]
    public function center(): Result
    {
        $overview = $this->memberCenterService->overview($this->currentMember->id());
        return $this->success($overview, '获取成功');
    }
}
