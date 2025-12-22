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

namespace Plugin\Since\SystemMessage\Controller\Admin;

use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Mine\Access\Attribute\Permission;
use Plugin\Since\SystemMessage\Controller\AbstractController;
use Plugin\Since\SystemMessage\Request\CreateMessageRequest;
use Plugin\Since\SystemMessage\Request\UpdateMessageRequest;
use Plugin\Since\SystemMessage\Service\MessageService;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: "admin/system-message")]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
class MessageController extends AbstractController
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * 获取消息列表
     */
    #[GetMapping("index")]
    #[Permission(code: "system-message:index")]
    public function index(): ResponseInterface
    {
        $filters = [
            'type' => $this->request->input('type'),
            'status' => $this->request->input('status'),
            'sender_id' => $this->request->input('sender_id'),
            'recipient_type' => $this->request->input('recipient_type'),
            'priority' => $this->request->input('priority'),
            'date_from' => $this->request->input('date_from'),
            'date_to' => $this->request->input('date_to'),
        ];

        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);

        $result = $this->messageService->getRepository()->list($filters, $page, $pageSize);

        return $this->success($result);
    }

    /**
     * 获取消息详情
     */
    #[GetMapping("read/{id}")]
    #[Permission(code: "system-message:read")]
    public function read(int $id): ResponseInterface
    {
        $message = $this->messageService->getRepository()->findById($id);

        if (!$message) {
            return $this->error('消息不存在', 404);
        }

        return $this->success($message);
    }

    /**
     * 创建消息
     */
    #[PostMapping("save")]
    #[Permission(code: "system-message:save")]
    public function save(CreateMessageRequest $request): ResponseInterface
    {
        $data = $request->validated();
        
        // 添加发送者信息
        $data['sender_id'] = user()->getId();

        $message = $this->messageService->create($data);

        return $this->success($message, '消息创建成功');
    }

    /**
     * 更新消息
     */
    #[PutMapping("update/{id}")]
    #[Permission(code: "system-message:update")]
    public function update(int $id, UpdateMessageRequest $request): ResponseInterface
    {
        $data = $request->validated();

        $message = $this->messageService->update($id, $data);

        return $this->success($message, '消息更新成功');
    }

    /**
     * 删除消息
     */
    #[DeleteMapping("delete")]
    #[Permission(code: "system-message:delete")]
    public function delete(): ResponseInterface
    {
        $ids = $this->request->input('ids', []);

        if (empty($ids)) {
            return $this->error('请选择要删除的消息');
        }

        $deleted = 0;
        $failed = 0;

        foreach ((array) $ids as $id) {
            try {
                if ($this->messageService->delete($id)) {
                    $deleted++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return $this->success([
            'deleted' => $deleted,
            'failed' => $failed,
        ], '删除操作完成');
    }

    /**
     * 发送消息
     */
    #[PostMapping("send")]
    #[Permission(code: "system-message:send")]
    public function send(): ResponseInterface
    {
        $id = $this->request->input('id');
        
        if (!$id) {
            return $this->error('消息ID不能为空');
        }

        try {
            $result = $this->messageService->send($id);

            return $this->success(['result' => $result], '消息发送成功');
        } catch (\Throwable $e) {
            return $this->error('消息发送失败：' . $e->getMessage());
        }
    }

    /**
     * 调度消息
     */
    #[PostMapping("schedule")]
    #[Permission(code: "system-message:schedule")]
    public function schedule(): ResponseInterface
    {
        $id = $this->request->input('id');
        $scheduledAt = $this->request->input('scheduled_at');
        
        if (!$id || !$scheduledAt) {
            return $this->error('消息ID和调度时间不能为空');
        }

        try {
            $result = $this->messageService->schedule($id, \Carbon\Carbon::parse($scheduledAt));

            return $this->success(['result' => $result], '消息调度成功');
        } catch (\Throwable $e) {
            return $this->error('消息调度失败：' . $e->getMessage());
        }
    }

    /**
     * 批量发送消息
     */
    #[PostMapping("batchSend")]
    #[Permission(code: "system-message:batchSend")]
    public function batchSend(): ResponseInterface
    {
        $ids = $this->request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要发送的消息');
        }

        $sent = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                if ($this->messageService->send($id)) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return $this->success([
            'sent' => $sent,
            'failed' => $failed,
        ], '批量发送完成');
    }

    /**
     * 搜索消息
     */
    #[GetMapping("search")]
    #[Permission(code: "system-message:index")]
    public function search(): ResponseInterface
    {
        $keyword = $this->request->input('keyword', '');
        
        if (empty($keyword)) {
            return $this->error('搜索关键词不能为空');
        }

        $filters = [
            'type' => $this->request->input('type'),
            'status' => $this->request->input('status'),
        ];

        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);

        $result = $this->messageService->getRepository()->search($keyword, $filters, $page, $pageSize);

        return $this->success($result);
    }

    /**
     * 获取消息统计
     */
    #[GetMapping("statistics")]
    #[Permission(code: "system-message:statistics")]
    public function statistics(): ResponseInterface
    {
        $stats = $this->messageService->getStatistics();

        return $this->success($stats);
    }

    /**
     * 获取热门消息
     */
    #[GetMapping("popular")]
    #[Permission(code: "system-message:index")]
    public function popular(): ResponseInterface
    {
        $limit = (int) $this->request->input('limit', 10);
        $messages = $this->messageService->getRepository()->getPopularMessages($limit);

        return $this->success($messages);
    }

    /**
     * 获取最近消息
     */
    #[GetMapping("recent")]
    #[Permission(code: "system-message:index")]
    public function recent(): ResponseInterface
    {
        $days = (int) $this->request->input('days', 7);
        $limit = (int) $this->request->input('limit', 20);
        
        $messages = $this->messageService->getRepository()->getRecentMessages($days, $limit);

        return $this->success($messages);
    }
}