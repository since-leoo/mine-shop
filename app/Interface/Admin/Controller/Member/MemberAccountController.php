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

namespace App\Interface\Admin\Controller\Member;

use App\Application\Commad\AppMemberAccountCommandService;
use App\Application\Query\AppMemberWalletTransactionQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Member\MemberAccountRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/member/account')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class MemberAccountController extends AbstractController
{
    public function __construct(
        private readonly AppMemberAccountCommandService $accountCommandService,
        private readonly AppMemberWalletTransactionQueryService $walletTransactionQueryService,
        private readonly CurrentUser $currentUser,
    ) {}

    #[GetMapping(path: 'wallet/logs')]
    #[Permission(code: 'member:wallet:list')]
    public function walletLogs(MemberAccountRequest $request): Result
    {
        $payload = $request->validated();
        return $this->success(
            $this->walletTransactionQueryService->page(
                $payload,
                $this->getCurrentPage(),
                $this->getPageSize(),
            ),
        );
    }

    #[PostMapping(path: 'wallet/adjust')]
    #[Permission(code: 'member:wallet:adjust')]
    public function adjust(MemberAccountRequest $request): Result
    {
        $result = $this->accountCommandService->adjustBalance(
            $request->toDto($this->currentUser->id()),
            $this->operatorPayload()
        );

        return $this->success($result, '钱包调整成功');
    }

    /**
     * @return array{type: string, id: null|int, name: null|string}
     */
    private function operatorPayload(): array
    {
        $user = $this->currentUser->user();
        return [
            'type' => 'admin',
            'id' => $this->currentUser->id(),
            'name' => $user?->username,
        ];
    }
}
