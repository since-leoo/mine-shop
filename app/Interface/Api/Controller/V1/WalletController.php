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

use App\Application\Api\Member\AppApiMemberWalletQueryService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\WalletTransactionRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;

#[Controller(prefix: '/api/v1/member/wallet')]
#[Middleware(TokenMiddleware::class)]
final class WalletController extends AbstractController
{
    public function __construct(
        private readonly AppApiMemberWalletQueryService $walletQueryService,
        private readonly CurrentMember $currentMember,
    ) {}

    #[GetMapping(path: 'transactions')]
    public function transactions(WalletTransactionRequest $request): Result
    {
        $payload = $request->validated();

        return $this->success(
            $this->walletQueryService->transactions(
                $this->currentMember->id(),
                $payload['wallet_type'],
                (int) ($payload['page'] ?? 1),
                (int) ($payload['page_size'] ?? 20),
            )
        );
    }
}
