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

namespace App\Interface\Admin\Controller;

use App\Application\Admin\Plugin\AppPluginCenterQueryService;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/admin/plugin-center')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
final class PluginCenterController extends AbstractController
{
    public function __construct(
        private readonly AppPluginCenterQueryService $queryService
    ) {}

    #[GetMapping(path: 'plugins')]
    public function plugins(): Result
    {
        return $this->success($this->queryService->list());
    }

    #[GetMapping(path: 'plugins/{name:.+}/config')]
    public function config(string $name): Result
    {
        return $this->success($this->queryService->config($name));
    }

    #[GetMapping(path: 'plugins/config')]
    public function configByQuery(RequestInterface $request): Result
    {
        return $this->success($this->queryService->config((string) $request->input('name', '')));
    }

    #[PutMapping(path: 'plugins/{name:.+}/config/{key:.+}')]
    public function updateConfig(string $name, string $key, RequestInterface $request): Result
    {
        $value = $request->input('value');
        return $this->success($this->queryService->updateConfig($name, $key, $value), '配置已更新');
    }

    #[PutMapping(path: 'plugins/config/{key:.+}')]
    public function updateConfigByQuery(string $key, RequestInterface $request): Result
    {
        $name = (string) $request->input('name', '');
        return $this->success($this->queryService->updateConfig($name, $key, $request->input('value')), '配置已更新');
    }
}
