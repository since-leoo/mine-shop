<?php

declare(strict_types=1);

namespace App\Interface\Admin\Controller\SystemMessage;

use App\Domain\Infrastructure\SystemMessage\Service\MessageService;
use App\Interface\Common\Controller\SystemMessageAbstractController;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PutMapping;

#[Controller(prefix: 'admin/system-message/user')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
class UserMessageController extends SystemMessageAbstractController
{
    #[Inject]
    protected MessageService $messageService;

    #[GetMapping('index')]
    public function index(): Result
    {
        $filters = [
            'is_read' => $this->request->input('is_read'), 'type' => $this->request->input('type'),
            'priority' => $this->request->input('priority'), 'date_from' => $this->request->input('date_from'),
            'date_to' => $this->request->input('date_to'),
        ];
        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);
        $userId = $this->currentUser->id();
        return $this->success($this->messageService->getUserMessages($userId, $filters, $page, $pageSize));
    }

    #[GetMapping('read/{messageId}')]
    public function read(int $messageId): Result
    {
        $userId = $this->currentUser->id();
        try {
            $message = $this->messageService->getUserMessage($userId, $messageId);
            if (! $message) { return $this->error('消息不存在', 404); }
            return $this->success($message);
        } catch (\Throwable $e) { return $this->error('获取消息失败：' . $e->getMessage()); }
    }

    #[PutMapping('markRead/{messageId}')]
    public function markAsRead(int $messageId): Result
    {
        $userId = $this->currentUser->id();
        try {
            $result = $this->messageService->markAsRead($userId, $messageId);
            if (! $result) { return $this->error('消息不存在或已读', 404); }
            return $this->success(null, '消息已标记为已读');
        } catch (\Throwable $e) { return $this->error('操作失败：' . $e->getMessage()); }
    }

    #[PutMapping('batchMarkRead')]
    public function batchMarkAsRead(): Result
    {
        $messageIds = $this->request->input('message_ids', []);
        $userId = $this->currentUser->id();
        if (empty($messageIds) || ! \is_array($messageIds)) { return $this->error('请选择要标记的消息'); }
        try {
            $result = $this->messageService->batchMarkAsRead($userId, $messageIds);
            return $this->success(['marked' => $result, 'total' => \count($messageIds)], '批量标记完成');
        } catch (\Throwable $e) { return $this->error('批量操作失败：' . $e->getMessage()); }
    }

    #[PutMapping('markAllRead')]
    public function markAllAsRead(): Result
    {
        $userId = $this->currentUser->id();
        try { return $this->success(['marked' => $this->messageService->markAllAsRead($userId)], '所有消息已标记为已读'); } catch (\Throwable $e) { return $this->error('操作失败：' . $e->getMessage()); }
    }

    #[DeleteMapping('delete/{messageId}')]
    public function delete(int $messageId): Result
    {
        $userId = $this->currentUser->id();
        try {
            $result = $this->messageService->deleteUserMessage($userId, $messageId);
            if (! $result) { return $this->error('消息不存在', 404); }
            return $this->success(null, '消息删除成功');
        } catch (\Throwable $e) { return $this->error('删除失败：' . $e->getMessage()); }
    }

    #[DeleteMapping('batchDelete')]
    public function batchDelete(): Result
    {
        $messageIds = $this->request->input('message_ids', []);
        $userId = $this->currentUser->id();
        if (empty($messageIds) || ! \is_array($messageIds)) { return $this->error('请选择要删除的消息'); }
        try {
            $result = $this->messageService->batchDeleteUserMessages($userId, $messageIds);
            return $this->success(['deleted' => $result, 'total' => \count($messageIds)], '批量删除完成');
        } catch (\Throwable $e) { return $this->error('批量删除失败：' . $e->getMessage()); }
    }

    #[GetMapping('unreadCount')]
    public function getUnreadCount(): Result
    {
        $userId = $this->currentUser->id();
        try { return $this->success(['count' => $this->messageService->getUnreadCount($userId)]); } catch (\Throwable $e) { return $this->error('获取未读数量失败：' . $e->getMessage()); }
    }

    #[GetMapping('typeStats')]
    public function getTypeStats(): Result
    {
        $userId = $this->currentUser->id();
        try { return $this->success($this->messageService->getUserMessageTypeStats($userId)); } catch (\Throwable $e) { return $this->error('获取统计失败：' . $e->getMessage()); }
    }

    #[GetMapping('search')]
    public function search(): Result
    {
        $keyword = $this->request->input('keyword', '');
        $userId = $this->currentUser->id();
        if (empty($keyword)) { return $this->error('搜索关键词不能为空'); }
        $filters = ['is_read' => $this->request->input('is_read'), 'type' => $this->request->input('type'), 'priority' => $this->request->input('priority')];
        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);
        try { return $this->success($this->messageService->searchUserMessages($userId, $keyword, $filters, $page, $pageSize)); } catch (\Throwable $e) { return $this->error('搜索失败：' . $e->getMessage()); }
    }
}
