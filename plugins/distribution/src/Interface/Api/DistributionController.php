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
use Hyperf\HttpServer\Annotation\PostMapping;
use Plugin\Distribution\Application\Api\AppApiDistributionCommandService;
use Plugin\Distribution\Application\Api\AppApiDistributionQueryService;
use Plugin\Distribution\Interface\Request\DistributionQueryRequest;
use Plugin\Distribution\Interface\Request\ShareRequest;
use Plugin\Distribution\Interface\Request\WithdrawalRequest;
use Plugin\Distribution\Interface\Transformer\DistributionTransformer;

#[Controller(prefix: '/api/v1/distribution')]
#[Middleware(ApiSignatureMiddleware::class)]
#[Middleware(TokenMiddleware::class)]
final class DistributionController extends AbstractController
{
    public function __construct(private readonly AppApiDistributionQueryService $queries, private readonly AppApiDistributionCommandService $commands, private readonly CurrentMember $member, private readonly DistributionTransformer $transformer) {}

    #[GetMapping(path: 'overview')]
    public function overview(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->overview($this->queries->overview($this->member->id(), $request->validated())));
    }

    #[GetMapping(path: 'team')]
    public function team(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->team($this->member->id(), $request->validated()), 'member'));
    }

    #[GetMapping(path: 'orders')]
    public function orders(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->orders($this->member->id(), $request->validated()), 'order'));
    }

    #[GetMapping(path: 'commissions')]
    public function commissions(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->commissions($this->member->id(), $request->validated()), 'commission'));
    }

    #[GetMapping(path: 'withdrawals')]
    public function withdrawals(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->withdrawals($this->member->id(), $request->validated()), 'withdrawal'));
    }

    #[GetMapping(path: 'ledger')]
    public function ledger(DistributionQueryRequest $request): Result
    {
        return $this->success($this->queries->ledger($this->member->id(), $request->validated()));
    }

    #[GetMapping(path: 'withdrawal-policy')]
    public function withdrawalPolicy(): Result
    {
        return $this->success($this->queries->withdrawalPolicy());
    }

    #[PostMapping(path: 'withdrawals')]
    public function withdraw(WithdrawalRequest $request): Result
    {
        return $this->success($this->transformer->row($this->commands->withdraw($this->member->id(), $request->toDto()), 'withdrawal'));
    }

    #[PostMapping(path: 'share')]
    public function share(ShareRequest $request): Result
    {
        return $this->success($this->commands->share($this->member->id(), $request->toDto()));
    }
}
