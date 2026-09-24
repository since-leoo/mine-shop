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
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;
use Plugin\WecomScrm\Service\CustomerService;

#[Controller(prefix: '/admin/wecom-scrm/customers')]
#[Middleware(AccessTokenMiddleware::class)]
final class CustomerApiController extends AbstractController
{
    public function __construct(private readonly RequestInterface $request, private readonly CustomerService $service) {}

    #[GetMapping(path: '')]
    #[Permission(code: 'wecom-scrm:customer:list')]
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

    #[GetMapping(path: 'detail')]
    #[Permission(code: 'wecom-scrm:customer:read')]
    public function detail(): Result
    {
        return $this->success(
            $this->service->page(
                ['external_userid' => $this->request->input('external_userid')],
                1,
                1
            )
        );
    }
}
