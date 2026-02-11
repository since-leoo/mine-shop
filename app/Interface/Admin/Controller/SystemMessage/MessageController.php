<?php

declare(strict_types=1);

namespace App\Interface\Admin\Controller\SystemMessage;

use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;
use App\Interface\Common\Controller\SystemMessageAbstractController;
use App\Interface\Admin\Request\SystemMessage\CreateMessageRequest;
use App\Interface\Admin\Request\SystemMessage\UpdateMessageRequest;
use App\Domain\Infrastructure\SystemMessage\Service\MessageService;

#[Controller(prefix: 'admin/system-message')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
class MessageController extends SystemMessageAbstractController
{
    #[Inject]
    protected MessageService $messageService;

    #[GetMapping('index')]
    #[Permission(code: 'system-message:index')]
    public function index(): Result
    {
        $filters = [
            'type' => $this->request->input('type'), 'status' => $this->request->input('status'),
            'sender_id' => $this->request->input('sender_id'), 'recipient_type' => $this->request->input('recipient_type'),
            'priority' => $this->request->input('priority'), 'date_from' => $this->request->input('date_from'),
            'date_to' => $this->request->input('date_to'),
        ];
        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);
        $result = $this->messageService->getRepository()->list($filters, $page, $pageSize);
        return $this->success($result);
    }

    #[GetMapping('read/{id}')]
    #[Permission(code: 'system-message:read')]
    public function read(int $id): Result
    {
        $message = $this->messageService->getRepository()->findById($id);
        if (! $message) { return $this->error('消息不存在', 404); }
        return $this->success($message);
    }

    #[PostMapping('save')]
    #[Permission(code: 'system-message:save')]
    public function save(CreateMessageRequest $request): Result
    {
        $data = $request->validated();
        $data['sender_id'] = $this->currentUser->user()->id;
        $message = $this->messageService->create($data);
        return $this->success($message, '消息创建成功');
    }

    #[PutMapping('update/{id}')]
    #[Permission(code: 'system-message:update')]
    public function update(int $id, UpdateMessageRequest $request): Result
    {
        $data = $request->validated();
        $message = $this->messageService->update($id, $data);
        return $this->success($message, '消息更新成功');
    }

    #[DeleteMapping('delete')]
    #[Permission(code: 'system-message:delete')]
    public function delete(): Result
    {
        $ids = $this->request->input('ids', []);
        if (empty($ids)) { return $this->error('请选择要删除的消息'); }
        $deleted = 0; $failed = 0;
        foreach ((array) $ids as $id) {
            try { if ($this->messageService->delete($id)) { ++$deleted; } else { ++$failed; } } catch (\Throwable $e) { ++$failed; }
        }
        return $this->success(['deleted' => $deleted, 'failed' => $failed], '删除操作完成');
    }

    #[PostMapping('send')]
    #[Permission(code: 'system-message:send')]
    public function send(): Result
    {
        $id = $this->request->input('id');
        if (! $id) { return $this->error('消息ID不能为空'); }
        try { $result = $this->messageService->send($id); return $this->success(['result' => $result], '消息发送成功'); } catch (\Throwable $e) { return $this->error('消息发送失败：' . $e->getMessage()); }
    }

    #[PostMapping('schedule')]
    #[Permission(code: 'system-message:schedule')]
    public function schedule(): Result
    {
        $id = $this->request->input('id');
        $scheduledAt = $this->request->input('scheduled_at');
        if (! $id || ! $scheduledAt) { return $this->error('消息ID和调度时间不能为空'); }
        try { $result = $this->messageService->schedule($id, Carbon::parse($scheduledAt)); return $this->success(['result' => $result], '消息调度成功'); } catch (\Throwable $e) { return $this->error('消息调度失败：' . $e->getMessage()); }
    }

    #[PostMapping('batchSend')]
    #[Permission(code: 'system-message:batchSend')]
    public function batchSend(): Result
    {
        $ids = $this->request->input('ids', []);
        if (empty($ids) || ! \is_array($ids)) { return $this->error('请选择要发送的消息'); }
        $sent = 0; $failed = 0;
        foreach ($ids as $id) { try { if ($this->messageService->send($id)) { ++$sent; } else { ++$failed; } } catch (\Throwable $e) { ++$failed; } }
        return $this->success(['sent' => $sent, 'failed' => $failed], '批量发送完成');
    }

    #[GetMapping('search')]
    #[Permission(code: 'system-message:index')]
    public function search(): Result
    {
        $keyword = $this->request->input('keyword', '');
        if (empty($keyword)) { return $this->error('搜索关键词不能为空'); }
        $filters = ['type' => $this->request->input('type'), 'status' => $this->request->input('status')];
        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);
        $result = $this->messageService->getRepository()->search($keyword, $filters, $page, $pageSize);
        return $this->success($result);
    }

    #[GetMapping('statistics')]
    #[Permission(code: 'system-message:statistics')]
    public function statistics(): Result { return $this->success($this->messageService->getStatistics()); }

    #[GetMapping('popular')]
    #[Permission(code: 'system-message:index')]
    public function popular(): Result
    {
        $limit = (int) $this->request->input('limit', 10);
        return $this->success($this->messageService->getRepository()->getPopularMessages($limit));
    }

    #[GetMapping('recent')]
    #[Permission(code: 'system-message:index')]
    public function recent(): Result
    {
        $days = (int) $this->request->input('days', 7);
        $limit = (int) $this->request->input('limit', 20);
        return $this->success($this->messageService->getRepository()->getRecentMessages($days, $limit));
    }
}
