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

namespace Plugin\SystemMessage\Interface\Admin\Controller;

use App\Infrastructure\Error\SystemMessageError;

use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PutMapping;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\MessageService;
use Plugin\SystemMessage\Interface\Common\Controller\SystemMessageAbstractController;

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
            if (! $message) {
                return $this->error(SystemMessageError::MESSAGE_RECORD_NOT_FOUND, 404);
            }
            return $this->success($message);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    #[PutMapping('markRead/{messageId}')]
    public function markAsRead(int $messageId): Result
    {
        $userId = $this->currentUser->id();
        try {
            $result = $this->messageService->markAsRead($userId, $messageId);
            if (! $result) {
                return $this->error(SystemMessageError::MESSAGE_NOT_FOUND_OR_READ, 404);
            }
            return $this->success(null, '消息已标记为已读');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    #[PutMapping('batchMarkRead')]
    public function batchMarkAsRead(): Result
    {
        $messageIds = $this->request->input('message_ids', []);
        $userId = $this->currentUser->id();
        if (empty($messageIds) || ! \is_array($messageIds)) {
            return $this->error(SystemMessageError::MESSAGE_MARK_SELECTION_REQUIRED);
        }
        try {
            $result = $this->messageService->batchMarkAsRead($userId, $messageIds);
            return $this->success(['marked' => $result, 'total' => \count($messageIds)], '批量标记完成');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    #[PutMapping('markAllRead')]
    public function markAllAsRead(): Result
    {
        $userId = $this->currentUser->id();
        try {
            return $this->success(['marked' => $this->messageService->markAllAsRead($userId)], '所有消息已标记为已读');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    #[DeleteMapping('delete/{messageId}')]
    public function delete(int $messageId): Result
    {
        $userId = $this->currentUser->id();
        try {
            $result = $this->messageService->deleteUserMessage(messageId: $messageId, userId: $userId);
            if (! $result) {
                return $this->error(SystemMessageError::MESSAGE_RECORD_NOT_FOUND, 404);
            }
            return $this->success(null, '消息删除成功');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    #[DeleteMapping('batchDelete')]
    public function batchDelete(): Result
    {
        $messageIds = $this->request->input('message_ids', []);
        $userId = $this->currentUser->id();
        if (empty($messageIds) || ! \is_array($messageIds)) {
            return $this->error(SystemMessageError::MESSAGE_DELETE_SELECTION_REQUIRED);
        }
        try {
            $result = $this->messageService->batchDeleteUserMessages(messageIds: $messageIds, userId: $userId);
            return $this->success(['deleted' => $result, 'total' => \count($messageIds)], '批量删除完成');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    #[GetMapping('unreadCount')]
    public function getUnreadCount(): Result
    {
        $userId = $this->currentUser->id();
        try {
            return $this->success(['count' => $this->messageService->getUnreadCount($userId)]);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    #[GetMapping('typeStats')]
    public function getTypeStats(): Result
    {
        $userId = $this->currentUser->id();
        try {
            return $this->success($this->messageService->getUserMessageTypeStats($userId));
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    #[GetMapping('search')]
    public function search(): Result
    {
        $keyword = $this->request->input('keyword', '');
        $userId = $this->currentUser->id();
        if (empty($keyword)) {
            return $this->error(SystemMessageError::SEARCH_KEYWORD_REQUIRED);
        }
        $filters = ['is_read' => $this->request->input('is_read'), 'type' => $this->request->input('type'), 'priority' => $this->request->input('priority')];
        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);
        try {
            return $this->success($this->messageService->searchUserMessages($userId, $keyword, $filters, $page, $pageSize));
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
