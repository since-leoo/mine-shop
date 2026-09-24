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

namespace Plugin\CustomerService\Interface\Admin\Controller;

use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;
use Plugin\CustomerService\Application\Admin\AppCustomerServiceCommandService;
use Plugin\CustomerService\Application\Admin\AppCustomerServiceQueryService;
use Plugin\CustomerService\Application\Socket\CustomerServiceSocketTicketService;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceFaq;

#[Controller(prefix: '/admin/customer-service')]
#[Middleware(AccessTokenMiddleware::class)]
#[Middleware(PermissionMiddleware::class)]
final class CustomerServiceController extends AbstractController
{
    public function __construct(private readonly AppCustomerServiceCommandService $command, private readonly AppCustomerServiceQueryService $query, private readonly CurrentUser $currentUser, private readonly RequestInterface $request, private readonly CustomerServiceSocketTicketService $tickets) {}

    #[PostMapping(path: 'socket-ticket')]
    #[Permission(code: 'customer-service:conversation:read')]
    public function socketTicket(): Result
    {
        $adminUserId = $this->currentUser->id();
        $agent = $this->command->agent($adminUserId, $this->currentUser->user()?->nickname ?? '客服');
        return $this->success($this->tickets->issueAgentTicket((int) $agent->id, $adminUserId));
    }

    #[GetMapping(path: 'queue')]
    #[Permission(code: 'customer-service:conversation:read')]
    public function queue(): Result
    {
        return $this->success($this->query->queue());
    }

    #[GetMapping(path: 'conversations')]
    #[Permission(code: 'customer-service:conversation:read')]
    public function conversations(): Result
    {
        return $this->success($this->query->conversations($this->request->all(), $this->getCurrentPage(), $this->getPageSize()));
    }

    #[GetMapping(path: 'conversations/{conversationNo}/messages')]
    #[Permission(code: 'customer-service:conversation:read')]
    public function messages(string $conversationNo): Result
    {
        return $this->success($this->query->messages($conversationNo));
    }

    #[PostMapping(path: 'conversations/{conversationNo}/messages')]
    #[Permission(code: 'customer-service:conversation:read')]
    public function sendMessage(string $conversationNo): Result
    {
        $agent = $this->command->agent($this->currentUser->id(), $this->currentUser->user()?->nickname ?? '客服');
        $message = $this->command->sendText((int) $agent->id, $conversationNo, (string) $this->request->input('client_message_id', bin2hex(random_bytes(16))), (string) $this->request->input('content', ''));
        return $this->success($this->query->formatMessage($message), '消息已发送');
    }

    #[PostMapping(path: 'conversations/{conversationNo}/accept')]
    #[Permission(code: 'customer-service:conversation:accept')]
    public function accept(string $conversationNo): Result
    {
        return $this->success($this->command->accept($this->currentUser->id(), $this->currentUser->user()?->nickname ?? '客服', $conversationNo));
    }

    #[PostMapping(path: 'conversations/{conversationNo}/transfer')]
    #[Permission(code: 'customer-service:conversation:transfer')]
    public function transfer(string $conversationNo): Result
    {
        $this->command->transfer($this->currentUser->id(), $this->currentUser->user()?->nickname ?? '客服', $conversationNo, (int) $this->request->input('agent_id'));
        return $this->success([], '会话已转接');
    }

    #[PostMapping(path: 'conversations/{conversationNo}/close')]
    #[Permission(code: 'customer-service:conversation:close')]
    public function close(string $conversationNo): Result
    {
        $this->command->close($this->currentUser->id(), $this->currentUser->user()?->nickname ?? '客服', $conversationNo);
        return $this->success([], '会话已关闭');
    }

    #[GetMapping(path: 'agents')]
    public function agents(): Result
    {
        return $this->success($this->query->agents());
    }

    #[GetMapping(path: 'agents/me')]
    #[Permission(code: 'customer-service:conversation:read')]
    public function currentAgent(): Result
    {
        $agent = $this->command->agent($this->currentUser->id(), $this->currentUser->user()?->nickname ?? '客服');
        return $this->success($this->query->agentByAdminUserId((int) $agent->admin_user_id));
    }

    #[PostMapping(path: 'agents/{id}/status')]
    public function status(int $id): Result
    {
        $agent = CustomerServiceAgent::query()->find($id);
        if ($agent === null || (int) $agent->admin_user_id !== $this->currentUser->id()) {
            throw new \DomainException('只能修改自己的坐席状态');
        }
        return $this->success($this->command->updateAgentStatus($this->currentUser->id(), $this->currentUser->user()?->nickname ?? '客服', (string) $this->request->input('status')));
    }

    #[GetMapping(path: 'statistics')]
    public function statistics(): Result
    {
        return $this->success($this->query->statistics());
    }

    #[GetMapping(path: 'faqs')]
    #[Permission(code: 'customer-service:faq:list')]
    public function faqs(): Result
    {
        return $this->success($this->query->faqs($this->getCurrentPage(), $this->getPageSize()));
    }

    #[PostMapping(path: 'faqs')]
    #[Permission(code: 'customer-service:faq:create')]
    public function createFaq(): Result
    {
        return $this->success($this->command->saveFaq(null, $this->request->all()), '常见问题已创建');
    }

    #[PutMapping(path: 'faqs/{id}')]
    #[Permission(code: 'customer-service:faq:update')]
    public function updateFaq(int $id): Result
    {
        return $this->success($this->command->saveFaq($id, $this->request->all()), '常见问题已更新');
    }

    #[DeleteMapping(path: 'faqs/{id}')]
    #[Permission(code: 'customer-service:faq:delete')]
    public function deleteFaq(int $id): Result
    {
        CustomerServiceFaq::query()->whereKey($id)->delete();
        return $this->success([], '常见问题已删除');
    }
}
