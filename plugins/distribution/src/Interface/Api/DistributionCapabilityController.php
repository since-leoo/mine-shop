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

namespace Plugin\Distribution\Interface\Api;

use App\Interface\Api\Middleware\ApiSignatureMiddleware;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Plugin\Distribution\Application\Api\AppApiDistributionQueryService;
use Plugin\Distribution\Interface\Request\DistributionResolveRequest;

#[Controller(prefix: '/api/v1/distribution')]
#[Middleware(ApiSignatureMiddleware::class)]
final class DistributionCapabilityController extends AbstractController
{
    public function __construct(private readonly AppApiDistributionQueryService $service, private readonly CurrentMember $member) {}

    #[GetMapping(path: 'status')]
    #[Middleware(TokenMiddleware::class)]
    public function status(): Result
    {
        return $this->success($this->service->status($this->member->id()));
    }

    #[GetMapping(path: 'share/resolve')]
    public function resolve(DistributionResolveRequest $request): Result
    {
        $data = $request->validated();
        $result = $this->service->resolve($data['invite_code'] ?? null, $data['token'] ?? null);
        unset($result['referrer_id']);
        return $this->success($result);
    }
}
