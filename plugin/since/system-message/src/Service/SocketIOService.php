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

namespace Plugin\Since\SystemMessage\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

/**
 * Socket.IO 服务
 * 
 * 基于Socket.IO实现的实时通信服务，支持房间管理、命名空间、事件广播等功能
 */
class SocketIOService
{
    #[Inject]
    protected Redis $redis;

    /**
     * Socket.IO 服务器实例
     */
    protected $socketServer;

    /**
     * 命名空间
     */
    protected string $namespace = '/system-message';

    /**
     * Redis键前缀
     */
    protected string $redisPrefix = 'socketio:system_message:';

    /**
     * 初始化Socket.IO服务
     */
    public function initialize($socketServer): void
    {
        $this->socketServer = $socketServer;
        $this->setupEventHandlers();
    }

    /**
     * 设置事件处理器
     */
    protected function setupEventHandlers(): void
    {
        if (!$this->socketServer) {
            return;
        }

        // 用户连接事件
        $this->socketServer->on('connection', function ($socket) {
            $this->handleConnection($socket);
        });

        // 用户断开连接事件
        $this->socketServer->on('disconnect', function ($socket) {
            $this->handleDisconnection($socket);
        });
    }

    /**
     * 处理用户连接
     */
    protected function handleConnection($socket): void
    {
        $userId = $this->getUserIdFromSocket($socket);
        if (!$userId) {
            $socket->disconnect();
            return;
        }

        // 加入用户房间
        $socket->join("user_{$userId}");
        
        // 记录用户在线状态
        $this->setUserOnline($userId, $socket->id);

        // 发送连接成功消息
        $socket->emit('connected', [
            'message' => 'Connected to system message service',
            'user_id' => $userId,
            'socket_id' => $socket->id,
        ]);

        system_message_logger()->info('User connected to Socket.IO', [
            'user_id' => $userId,
            'socket_id' => $socket->id,
        ]);
    }

    /**
     * 处理用户断开连接
     */
    protected function handleDisconnection($socket): void
    {
        $userId = $this->getUserIdFromSocket($socket);
        if ($userId) {
            $this->setUserOffline($userId, $socket->id);
            
            system_message_logger()->info('User disconnected from Socket.IO', [
                'user_id' => $userId,
                'socket_id' => $socket->id,
            ]);
        }
    }

    /**
     * 发送消息给指定用户
     */
    public function sendToUser(int $userId, array $data): bool
    {
        try {
            if (!$this->socketServer) {
                system_message_logger()->warning('Socket.IO server not initialized');
                return false;
            }

            // 检查用户是否在线
            if (!$this->isUserOnline($userId)) {
                system_message_logger()->info('User is offline, Socket.IO message not sent', [
                    'user_id' => $userId,
                    'data' => $data,
                ]);
                return false;
            }

            // 发送到用户房间
            $this->socketServer->to("user_{$userId}")->emit('message_notification', $data);

            system_message_logger()->info('Socket.IO message sent to user', [
                'user_id' => $userId,
                'event' => 'message_notification',
            ]);

            return true;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to send Socket.IO message to user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 广播消息给所有在线用户
     */
    public function broadcast(array $data): int
    {
        try {
            if (!$this->socketServer) {
                system_message_logger()->warning('Socket.IO server not initialized');
                return 0;
            }

            $onlineUsers = $this->getOnlineUsers();
            
            // 广播到所有用户
            $this->socketServer->emit('message_broadcast', $data);

            system_message_logger()->info('Socket.IO message broadcasted', [
                'total_users' => count($onlineUsers),
                'event' => 'message_broadcast',
            ]);

            return count($onlineUsers);
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to broadcast Socket.IO message', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * 发送消息给指定用户组
     */
    public function sendToUsers(array $userIds, array $data): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'offline' => 0,
        ];

        foreach ($userIds as $userId) {
            if (!$this->isUserOnline($userId)) {
                $results['offline']++;
                continue;
            }

            if ($this->sendToUser($userId, $data)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * 发送消息给指定角色的用户
     */
    public function sendToRole(string $role, array $data): int
    {
        try {
            if (!$this->socketServer) {
                return 0;
            }

            // 发送到角色房间
            $this->socketServer->to("role_{$role}")->emit('message_notification', $data);

            system_message_logger()->info('Socket.IO message sent to role', [
                'role' => $role,
                'event' => 'message_notification',
            ]);

            // 返回房间中的用户数量（这里简化处理）
            return $this->getRoleUserCount($role);
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to send Socket.IO message to role', [
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * 用户加入角色房间
     */
    public function joinRole(int $userId, string $role): bool
    {
        try {
            $socketIds = $this->getUserSocketIds($userId);
            
            foreach ($socketIds as $socketId) {
                $socket = $this->getSocketById($socketId);
                if ($socket) {
                    $socket->join("role_{$role}");
                }
            }

            system_message_logger()->info('User joined role room', [
                'user_id' => $userId,
                'role' => $role,
            ]);

            return true;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to join role room', [
                'user_id' => $userId,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 用户离开角色房间
     */
    public function leaveRole(int $userId, string $role): bool
    {
        try {
            $socketIds = $this->getUserSocketIds($userId);
            
            foreach ($socketIds as $socketId) {
                $socket = $this->getSocketById($socketId);
                if ($socket) {
                    $socket->leave("role_{$role}");
                }
            }

            system_message_logger()->info('User left role room', [
                'user_id' => $userId,
                'role' => $role,
            ]);

            return true;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to leave role room', [
                'user_id' => $userId,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 发送系统通知
     */
    public function sendSystemNotification(array $data): int
    {
        return $this->broadcast(array_merge($data, [
            'type' => 'system_notification',
            'timestamp' => time(),
        ]));
    }

    /**
     * 发送实时消息状态更新
     */
    public function sendMessageStatusUpdate(int $userId, int $messageId, string $status): bool
    {
        return $this->sendToUser($userId, [
            'type' => 'message_status_update',
            'message_id' => $messageId,
            'status' => $status,
            'timestamp' => time(),
        ]);
    }

    /**
     * 发送未读消息数量更新
     */
    public function sendUnreadCountUpdate(int $userId, int $count): bool
    {
        return $this->sendToUser($userId, [
            'type' => 'unread_count_update',
            'count' => $count,
            'timestamp' => time(),
        ]);
    }

    /**
     * 检查用户是否在线
     */
    public function isUserOnline(int $userId): bool
    {
        $socketIds = $this->redis->sMembers($this->redisPrefix . "user:{$userId}:sockets");
        return !empty($socketIds);
    }

    /**
     * 获取在线用户列表
     */
    public function getOnlineUsers(): array
    {
        $pattern = $this->redisPrefix . 'user:*:sockets';
        $keys = $this->redis->keys($pattern);
        
        $users = [];
        foreach ($keys as $key) {
            if (preg_match('/user:(\d+):sockets/', $key, $matches)) {
                $users[] = (int) $matches[1];
            }
        }
        
        return $users;
    }

    /**
     * 获取在线用户数量
     */
    public function getOnlineUserCount(): int
    {
        return count($this->getOnlineUsers());
    }

    /**
     * 获取用户连接数量
     */
    public function getUserConnectionCount(int $userId): int
    {
        return $this->redis->sCard($this->redisPrefix . "user:{$userId}:sockets");
    }

    /**
     * 设置用户在线状态
     */
    protected function setUserOnline(int $userId, string $socketId): void
    {
        // 添加socket到用户的socket集合
        $this->redis->sAdd($this->redisPrefix . "user:{$userId}:sockets", $socketId);
        
        // 设置socket到用户的映射
        $this->redis->set($this->redisPrefix . "socket:{$socketId}:user", $userId, 3600);
        
        // 设置用户在线状态
        $this->redis->set($this->redisPrefix . "user:{$userId}:online", time(), 3600);
    }

    /**
     * 设置用户离线状态
     */
    protected function setUserOffline(int $userId, string $socketId): void
    {
        // 从用户的socket集合中移除
        $this->redis->sRem($this->redisPrefix . "user:{$userId}:sockets", $socketId);
        
        // 删除socket到用户的映射
        $this->redis->del($this->redisPrefix . "socket:{$socketId}:user");
        
        // 如果用户没有其他连接，设置为离线
        if ($this->redis->sCard($this->redisPrefix . "user:{$userId}:sockets") === 0) {
            $this->redis->del($this->redisPrefix . "user:{$userId}:online");
        }
    }

    /**
     * 从Socket获取用户ID
     */
    protected function getUserIdFromSocket($socket): ?int
    {
        // 这里应该从socket的认证信息中获取用户ID
        // 例如从JWT token或session中解析
        // 暂时返回固定值，实际实现时需要集成认证系统
        
        $token = $socket->handshake->query['token'] ?? null;
        if (!$token) {
            return null;
        }
        
        // 解析token获取用户ID（这里需要实际的JWT解析逻辑）
        return $this->parseUserIdFromToken($token);
    }

    /**
     * 从token解析用户ID
     */
    protected function parseUserIdFromToken(string $token): ?int
    {
        // 这里应该实现JWT token解析逻辑
        // 暂时返回固定值
        return 1;
    }

    /**
     * 获取用户的所有Socket ID
     */
    protected function getUserSocketIds(int $userId): array
    {
        return $this->redis->sMembers($this->redisPrefix . "user:{$userId}:sockets");
    }

    /**
     * 根据Socket ID获取Socket实例
     */
    protected function getSocketById(string $socketId)
    {
        // 这里应该从Socket.IO服务器获取socket实例
        // 具体实现取决于使用的Socket.IO库
        return $this->socketServer->sockets->sockets[$socketId] ?? null;
    }

    /**
     * 获取角色房间中的用户数量
     */
    protected function getRoleUserCount(string $role): int
    {
        // 这里应该从Socket.IO服务器获取房间用户数量
        // 暂时返回估算值
        return 0;
    }

    /**
     * 获取连接统计信息
     */
    public function getConnectionStats(): array
    {
        $onlineUsers = $this->getOnlineUsers();
        $totalConnections = 0;
        $userConnectionCounts = [];

        foreach ($onlineUsers as $userId) {
            $count = $this->getUserConnectionCount($userId);
            $totalConnections += $count;
            $userConnectionCounts[$userId] = $count;
        }

        return [
            'online_users' => count($onlineUsers),
            'total_connections' => $totalConnections,
            'avg_connections_per_user' => count($onlineUsers) > 0 
                ? round($totalConnections / count($onlineUsers), 2) 
                : 0,
            'user_connections' => $userConnectionCounts,
            'server_status' => $this->socketServer ? 'running' : 'stopped',
        ];
    }

    /**
     * 清理过期连接
     */
    public function cleanupExpiredConnections(): int
    {
        $cleaned = 0;
        $pattern = $this->redisPrefix . 'socket:*:user';
        $keys = $this->redis->keys($pattern);
        
        foreach ($keys as $key) {
            $ttl = $this->redis->ttl($key);
            if ($ttl === -1) { // 没有过期时间的key
                $this->redis->expire($key, 3600);
            } elseif ($ttl === -2) { // 已过期的key
                $this->redis->del($key);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}