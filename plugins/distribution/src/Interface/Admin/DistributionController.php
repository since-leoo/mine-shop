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

namespace Plugin\Distribution\Interface\Admin;

use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Plugin\Distribution\Application\Admin\AppDistributionCommandService;
use Plugin\Distribution\Application\Admin\AppDistributionQueryService;
use Plugin\Distribution\Interface\Admin\Middleware\DistributionAdminMiddleware;
use Plugin\Distribution\Interface\Request\DistributionQueryRequest;
use Plugin\Distribution\Interface\Request\DistributionSettingsRequest;
use Plugin\Distribution\Interface\Request\WithdrawalReviewRequest;
use Plugin\Distribution\Interface\Transformer\DistributionTransformer;

#[Controller(prefix: '/admin/distribution')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: DistributionAdminMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DistributionController extends AbstractController
{
    public function __construct(private readonly AppDistributionQueryService $queries, private readonly AppDistributionCommandService $commands, private readonly CurrentUser $user, private readonly DistributionTransformer $transformer) {}

    #[GetMapping(path: 'overview')]
    public function overview(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->overview($this->queries->overview($request->validated())));
    }

    #[GetMapping(path: 'settings')]
    public function settings(): Result
    {
        return $this->success($this->transformer->settings($this->queries->settings()));
    }

    #[PutMapping(path: 'settings')]
    public function updateSettings(DistributionSettingsRequest $request): Result
    {
        return $this->success($this->commands->settings($request->toDto(), $this->user->id()));
    }

    #[GetMapping(path: 'members')]
    public function members(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->members($request->validated()), 'member', true));
    }

    #[GetMapping(path: 'members/{id:\d+}/team')]
    public function team(int $id, DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->members(array_replace($request->validated(), ['member_id' => $id])), 'member', true));
    }

    #[GetMapping(path: 'orders')]
    public function orders(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->orders($request->validated()), 'order', true));
    }

    #[GetMapping(path: 'commissions')]
    public function commissions(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->commissions($request->validated()), 'commission', true));
    }

    #[GetMapping(path: 'withdrawals')]
    public function withdrawals(DistributionQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->withdrawals($request->validated()), 'withdrawal', true, true));
    }

    #[PostMapping(path: 'withdrawals/{id:\d+}/review')]
    public function review(int $id, WithdrawalReviewRequest $request): Result
    {
        return $this->success($this->transformer->row($this->commands->review($id, $request->toDto(), $this->user->id()), 'withdrawal', true, true));
    }

    #[GetMapping(path: 'logs')]
    public function logs(DistributionQueryRequest $request): Result
    {
        return $this->success($this->queries->logs($request->validated()));
    }
}
