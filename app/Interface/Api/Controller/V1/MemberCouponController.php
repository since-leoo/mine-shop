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

use App\Application\Api\Member\MemberCouponApiService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/api/v1/member/coupons')]
#[Middleware(TokenMiddleware::class)]
final class MemberCouponController extends AbstractController
{
    public function __construct(
        private readonly MemberCouponApiService $couponApiService,
        private readonly CurrentMember $currentMember,
        private readonly RequestInterface $request
    ) {}

    #[GetMapping(path: '')]
    public function index(): Result
    {
        $status = (string) $this->request->query('status', 'default');
        $list = $this->couponApiService->list($this->currentMember->id(), $status);

        return $this->success(['list' => $list]);
    }
}
