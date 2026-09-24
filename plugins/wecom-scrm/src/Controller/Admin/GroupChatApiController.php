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

namespace Plugin\WecomScrm\Controller\Admin;

use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;
use Plugin\WecomScrm\Service\GroupChatService;

#[Controller(prefix: '/admin/wecom-scrm/group-chats')]
#[Middleware(AccessTokenMiddleware::class)]
final class GroupChatApiController extends AbstractController
{
    public function __construct(private readonly RequestInterface $request, private readonly GroupChatService $service) {}

    #[GetMapping(path: '')]
    #[Permission(code: 'wecom-scrm:group-chat:list')]
    public function index(): Result
    {
        return $this->success($this->service->page($this->request->all(), $this->getCurrentPage(), $this->getPageSize()));
    }

    #[GetMapping(path: 'detail')]
    #[Permission(code: 'wecom-scrm:group-chat:read')]
    public function detail(): Result
    {
        return $this->success($this->service->findById((string) $this->request->input('chat_id')));
    }

    #[PostMapping(path: 'send')]
    #[Permission(code: 'wecom-scrm:group-chat:send')]
    public function send(): Result
    {
        return $this->success($this->service->send((string) $this->request->input('chat_id'), (string) $this->request->input('content')));
    }
}
