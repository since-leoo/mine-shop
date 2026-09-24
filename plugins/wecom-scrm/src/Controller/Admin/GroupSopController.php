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
use Plugin\WecomScrm\Service\GroupSopService;

#[Controller(prefix: '/admin/wecom-scrm/group-sops')]
#[Middleware(AccessTokenMiddleware::class)]
final class GroupSopController extends AbstractController
{
    public function __construct(private readonly RequestInterface $request, private readonly GroupSopService $service) {}

    #[GetMapping(path: '')]
    #[Permission(code: 'wecom-scrm:group-sop:list')]
    public function index(): Result
    {
        return $this->success(
            $this->service->page(
                $this->request->all(),
                $this->getCurrentPage(),
                $this->getPageSize()
            )
        );
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'wecom-scrm:group-sop:create')]
    public function store(): Result
    {
        return $this->success(
            $this->service->create(
                $this->request->all()
            )
        );
    }
}
