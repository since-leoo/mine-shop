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

namespace Plugin\Since\SystemMessage\Middleware;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MessagePermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected RequestInterface $request,
        protected HttpResponse $response
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 获取当前路由信息
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // 检查是否是系统消息相关的路由
        if (!$this->isSystemMessageRoute($path)) {
            return $handler->handle($request);
        }

        // 检查用户权限
        if (!$this->checkPermission($request, $path, $method)) {
            return $this->response->json([
                'code' => 403,
                'message' => 'Access denied: insufficient permissions',
            ])->withStatus(403);
        }

        return $handler->handle($request);
    }

    /**
     * 检查是否是系统消息相关路由
     */
    protected function isSystemMessageRoute(string $path): bool
    {
        $patterns = [
            '/api/system-message/',
            '/api/message-template/',
            '/api/notification-preference/',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查权限
     */
    protected function checkPermission(ServerRequestInterface $request, string $path, string $method): bool
    {
        // 获取当前用户信息
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return false;
        }

        // 根据路径和方法确定所需权限
        $requiredPermission = $this->getRequiredPermission($path, $method);
        if (!$requiredPermission) {
            return true; // 不需要特殊权限
        }

        // 检查用户是否有所需权限
        return $this->userHasPermission($user, $requiredPermission);
    }

    /**
     * 获取当前用户
     */
    protected function getCurrentUser(ServerRequestInterface $request): ?array
    {
        // 这里应该从JWT token或session中获取用户信息
        // 暂时返回null，实际实现时需要集成MineAdmin的用户系统
        return null;
    }

    /**
     * 根据路径和方法获取所需权限
     */
    protected function getRequiredPermission(string $path, string $method): ?string
    {
        $permissions = [
            // 消息管理权限
            'GET:/api/system-message/admin' => 'system_message:view',
            'POST:/api/system-message/admin' => 'system_message:create',
            'PUT:/api/system-message/admin' => 'system_message:update',
            'DELETE:/api/system-message/admin' => 'system_message:delete',
            
            // 模板管理权限
            'GET:/api/message-template/admin' => 'message_template:view',
            'POST:/api/message-template/admin' => 'message_template:create',
            'PUT:/api/message-template/admin' => 'message_template:update',
            'DELETE:/api/message-template/admin' => 'message_template:delete',
            
            // 用户消息权限（用户只能访问自己的消息）
            'GET:/api/system-message/user' => null, // 不需要特殊权限
            'PUT:/api/system-message/user' => null, // 不需要特殊权限
        ];

        $key = $method . ':' . $path;
        
        // 精确匹配
        if (isset($permissions[$key])) {
            return $permissions[$key];
        }

        // 模糊匹配
        foreach ($permissions as $pattern => $permission) {
            if (str_contains($path, explode(':', $pattern)[1])) {
                return $permission;
            }
        }

        return null;
    }

    /**
     * 检查用户是否有指定权限
     */
    protected function userHasPermission(?array $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        // 这里应该调用MineAdmin的权限系统检查用户权限
        // 暂时返回true，实际实现时需要集成权限系统
        return true;
    }
}