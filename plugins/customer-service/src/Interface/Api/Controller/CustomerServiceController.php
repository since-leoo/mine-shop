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

namespace Plugin\CustomerService\Interface\Api\Controller;

use App\Interface\Api\Middleware\ApiSignatureMiddleware;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Plugin\CustomerService\Application\Api\AppApiCustomerServiceCommandService;
use Plugin\CustomerService\Application\Api\AppApiCustomerServiceQueryService;
use Plugin\CustomerService\Application\Socket\CustomerServiceSocketTicketService;

#[Controller(prefix: '/api/v1/customer-service')]
#[Middleware(ApiSignatureMiddleware::class)]
#[Middleware(TokenMiddleware::class)]
final class CustomerServiceController extends AbstractController
{
    public function __construct(
        private readonly AppApiCustomerServiceCommandService $commandService,
        private readonly AppApiCustomerServiceQueryService $queryService,
        private readonly CurrentMember $currentMember,
        private readonly RequestInterface $request,
        private readonly CustomerServiceSocketTicketService $ticketService,
    ) {}

    #[PostMapping(path: 'socket-ticket')]
    public function socketTicket(): Result
    {
        return $this->success($this->ticketService->issueMemberTicket($this->currentMember->id()));
    }

    #[GetMapping(path: 'config')]
    public function config(): Result
    {
        return $this->success($this->queryService->config());
    }

    #[PostMapping(path: 'conversations')]
    public function open(): Result
    {
        $conversation = $this->commandService->open($this->currentMember->id(), 'miniapp', (string) $this->request->input('subject', ''));
        return $this->success($conversation->toArray(), '客服会话已创建');
    }

    #[GetMapping(path: 'conversations/{conversationNo}/messages')]
    public function messages(string $conversationNo): Result
    {
        $messages = $this->queryService->messages($this->currentMember->id(), $conversationNo, (int) $this->request->query('before_id', 0) ?: null, (int) $this->request->query('limit', 30));
        return $this->success(array_map(static fn ($message) => $message->toArray(), $messages));
    }

    #[PostMapping(path: 'conversations/{conversationNo}/messages')]
    public function send(string $conversationNo): Result
    {
        $message = $this->commandService->sendText($this->currentMember->id(), $conversationNo, (string) $this->request->input('client_message_id'), (string) $this->request->input('text'));
        return $this->success($message, '消息已发送');
    }

    #[PostMapping(path: 'conversations/{conversationNo}/close')]
    public function close(string $conversationNo): Result
    {
        $this->commandService->close($this->currentMember->id(), $conversationNo);
        return $this->success([], '会话已关闭');
    }
}
