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
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;
use Plugin\WecomScrm\Service\MessageApiService;

#[Controller(prefix: '/admin/wecom-scrm/messages')]
#[Middleware(AccessTokenMiddleware::class)]
final class MessageApiController extends AbstractController
{
    public function __construct(private readonly RequestInterface $request, private readonly MessageApiService $service) {}

    #[PostMapping(path: 'text')]
    #[Permission(code: 'wecom-scrm:message:send')]
    public function text(): Result
    {
        return $this->success(
            $this->service->text(
                $this->users(),
                (string) $this->request->input('content')
            )
        );
    }

    #[PostMapping(path: 'markdown')]
    #[Permission(code: 'wecom-scrm:message:send')]
    public function markdown(): Result
    {
        return $this->success(
            $this->service->markdown(
                $this->users(),
                (string) $this->request->input('content')
            )
        );
    }

    private function users(): array
    {
        $users = $this->request->input('touser', []);
        return \is_array($users) ? $users : array_filter(explode('|', (string) $users));
    }
}
