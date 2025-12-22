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

namespace Plugin\Since\SystemMessage\Controller\User;

use App\Http\Common\Middleware\AccessTokenMiddleware;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Plugin\Since\SystemMessage\Controller\AbstractController;
use Plugin\Since\SystemMessage\Service\MessageService;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: "system-message/user")]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
class UserMessageController extends AbstractController
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * 获取用户消息列表
     */
    #[GetMapping("index")]
    public function index(): ResponseInterface
    {
        $filters = [
            'is_read' => $this->request->input('is_read'),
            'type' => $this->request->input('type'),
            'priority' => $this->request->input('priority'),
            'date_from' => $this->request->input('date_from'),
            'date_to' => $this->request->input('date_to'),
        ];

        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);
        $userId = user()->getId();

        $result = $this->messageService->getUserMessages($userId, $filters, $page, $pageSize);

        return $this->success($result);
    }

    /**
     * 获取消息详情
     */
    #[GetMapping("read/{messageId}")]
    public function read(int $messageId): ResponseInterface
    {
        $userId = user()->getId();
        
        try {
            $message = $this->messageService->getUserMessage($userId, $messageId);

            if (!$message) {
                return $this->error('消息不存在', 404);
            }

            return $this->success($message);
        } catch (\Throwable $e) {
            return $this->error('获取消息失败：' . $e->getMessage());
        }
    }

    /**
     * 标记消息为已读
     */
    #[PutMapping("markRead/{messageId}")]
    public function markAsRead(int $messageId): ResponseInterface
    {
        $userId = user()->getId();

        try {
            $result = $this->messageService->markAsRead($userId, $messageId);

            if (!$result) {
                return $this->error('消息不存在或已读', 404);
            }

            return $this->success(null, '消息已标记为已读');
        } catch (\Throwable $e) {
            return $this->error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 批量标记消息为已读
     */
    #[PutMapping("batchMarkRead")]
    public function batchMarkAsRead(): ResponseInterface
    {
        $messageIds = $this->request->input('message_ids', []);
        $userId = user()->getId();

        if (empty($messageIds) || !is_array($messageIds)) {
            return $this->error('请选择要标记的消息');
        }

        try {
            $result = $this->messageService->batchMarkAsRead($userId, $messageIds);

            return $this->success([
                'marked' => $result,
                'total' => count($messageIds),
            ], '批量标记完成');
        } catch (\Throwable $e) {
            return $this->error('批量操作失败：' . $e->getMessage());
        }
    }

    /**
     * 标记所有消息为已读
     */
    #[PutMapping("markAllRead")]
    public function markAllAsRead(): ResponseInterface
    {
        $userId = user()->getId();

        try {
            $result = $this->messageService->markAllAsRead($userId);

            return $this->success([
                'marked' => $result,
            ], '所有消息已标记为已读');
        } catch (\Throwable $e) {
            return $this->error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 删除用户消息
     */
    #[DeleteMapping("delete/{messageId}")]
    public function delete(int $messageId): ResponseInterface
    {
        $userId = user()->getId();

        try {
            $result = $this->messageService->deleteUserMessage($userId, $messageId);

            if (!$result) {
                return $this->error('消息不存在', 404);
            }

            return $this->success(null, '消息删除成功');
        } catch (\Throwable $e) {
            return $this->error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 批量删除用户消息
     */
    #[DeleteMapping("batchDelete")]
    public function batchDelete(): ResponseInterface
    {
        $messageIds = $this->request->input('message_ids', []);
        $userId = user()->getId();

        if (empty($messageIds) || !is_array($messageIds)) {
            return $this->error('请选择要删除的消息');
        }

        try {
            $result = $this->messageService->batchDeleteUserMessages($userId, $messageIds);

            return $this->success([
                'deleted' => $result,
                'total' => count($messageIds),
            ], '批量删除完成');
        } catch (\Throwable $e) {
            return $this->error('批量删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取未读消息数量
     */
    #[GetMapping("unreadCount")]
    public function getUnreadCount(): ResponseInterface
    {
        $userId = user()->getId();

        try {
            $count = $this->messageService->getUnreadCount($userId);

            return $this->success(['count' => $count]);
        } catch (\Throwable $e) {
            return $this->error('获取未读数量失败：' . $e->getMessage());
        }
    }

    /**
     * 获取消息类型统计
     */
    #[GetMapping("typeStats")]
    public function getTypeStats(): ResponseInterface
    {
        $userId = user()->getId();

        try {
            $stats = $this->messageService->getUserMessageTypeStats($userId);

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->error('获取统计失败：' . $e->getMessage());
        }
    }

    /**
     * 搜索用户消息
     */
    #[GetMapping("search")]
    public function search(): ResponseInterface
    {
        $keyword = $this->request->input('keyword', '');
        $userId = user()->getId();
        
        if (empty($keyword)) {
            return $this->error('搜索关键词不能为空');
        }

        $filters = [
            'is_read' => $this->request->input('is_read'),
            'type' => $this->request->input('type'),
            'priority' => $this->request->input('priority'),
        ];

        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);

        try {
            $result = $this->messageService->searchUserMessages($userId, $keyword, $filters, $page, $pageSize);

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->error('搜索失败：' . $e->getMessage());
        }
    }
}