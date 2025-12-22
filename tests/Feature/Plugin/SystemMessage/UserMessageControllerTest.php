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

namespace HyperfTests\Feature\Plugin\SystemMessage;

use App\Http\Common\ResultCode;
use Hyperf\Collection\Arr;
use HyperfTests\Feature\Admin\ControllerCase;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Model\UserMessage;

/**
 * @internal
 * @coversNothing
 */
final class UserMessageControllerTest extends ControllerCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 清理测试数据
        Message::truncate();
        UserMessage::truncate();
    }

    protected function tearDown(): void
    {
        // 清理测试数据
        Message::truncate();
        UserMessage::truncate();
        
        parent::tearDown();
    }

    /**
     * 测试获取用户消息列表
     */
    public function testIndex(): void
    {
        // 创建测试消息和用户消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Test Message',
            'content' => 'Test content',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message->id,
            'is_read' => false
        ]);

        $uri = '/system-message/user/index';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试有权限访问（用户消息不需要特殊权限，只需要登录）
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试带参数的列表查询
        $result = $this->get($uri, [
            'token' => $this->token,
            'is_read' => 0,
            'type' => 'system',
            'priority' => 'normal',
            'page' => 1,
            'page_size' => 10
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试获取消息详情
     */
    public function testRead(): void
    {
        // 创建测试消息和用户消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Test Message',
            'content' => 'Test content',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $userMessage = UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message->id,
            'is_read' => false
        ]);

        $uri = "/system-message/user/read/{$message->id}";
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试有权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试不存在的消息
        $result = $this->get('/system-message/user/read/99999', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), 404);
        
        // 测试其他用户的消息（创建另一个用户的消息）
        $otherMessage = Message::create([
            'type' => 'system',
            'title' => 'Other User Message',
            'content' => 'Other content',
            'sender_id' => 1,
            'recipient_type' => 'user',
            'recipient_ids' => json_encode([999]), // 不包含当前用户
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $result = $this->get("/system-message/user/read/{$otherMessage->id}", ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), 404);
    }

    /**
     * 测试标记消息为已读
     */
    public function testMarkAsRead(): void
    {
        // 创建测试消息和用户消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Test Message',
            'content' => 'Test content',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $userMessage = UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message->id,
            'is_read' => false
        ]);

        $uri = "/system-message/user/markRead/{$message->id}";
        
        // 测试未授权访问
        $result = $this->put($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试标记为已读
        $result = $this->put($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 验证消息已被标记为已读
        $userMessage->refresh();
        self::assertTrue($userMessage->is_read);
        
        // 测试重复标记已读消息
        $result = $this->put($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), 404); // 已读消息返回404
        
        // 测试不存在的消息
        $result = $this->put('/system-message/user/markRead/99999', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), 404);
    }

    /**
     * 测试批量标记消息为已读
     */
    public function testBatchMarkAsRead(): void
    {
        // 创建多个测试消息和用户消息
        $message1 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 1',
            'content' => 'Test content 1',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $message2 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 2',
            'content' => 'Test content 2',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message1->id,
            'is_read' => false
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message2->id,
            'is_read' => false
        ]);

        $uri = '/system-message/user/batchMarkRead';
        
        // 测试未授权访问
        $result = $this->put($uri, ['message_ids' => [$message1->id, $message2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试批量标记为已读
        $result = $this->put($uri, ['token' => $this->token, 'message_ids' => [$message1->id, $message2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.marked'), 2);
        
        // 测试空ID数组
        $result = $this->put($uri, ['token' => $this->token, 'message_ids' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试非数组参数
        $result = $this->put($uri, ['token' => $this->token, 'message_ids' => 'invalid']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试不存在的消息ID
        $result = $this->put($uri, ['token' => $this->token, 'message_ids' => [99999]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertSame(Arr::get($result, 'data.marked'), 0);
    }

    /**
     * 测试标记所有消息为已读
     */
    public function testMarkAllAsRead(): void
    {
        // 创建多个测试消息和用户消息
        $message1 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 1',
            'content' => 'Test content 1',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $message2 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 2',
            'content' => 'Test content 2',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message1->id,
            'is_read' => false
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message2->id,
            'is_read' => false
        ]);

        $uri = '/system-message/user/markAllRead';
        
        // 测试未授权访问
        $result = $this->put($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试标记所有消息为已读
        $result = $this->put($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.marked'), 2);
        
        // 验证所有消息都已被标记为已读
        $unreadCount = UserMessage::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count();
        self::assertSame($unreadCount, 0);
    }

    /**
     * 测试删除用户消息
     */
    public function testDelete(): void
    {
        // 创建测试消息和用户消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Test Message',
            'content' => 'Test content',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $userMessage = UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message->id,
            'is_read' => false
        ]);

        $uri = "/system-message/user/delete/{$message->id}";
        
        // 测试未授权访问
        $result = $this->delete($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试删除消息
        $result = $this->delete($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 验证消息已被删除
        $exists = UserMessage::where('user_id', $this->user->id)
            ->where('message_id', $message->id)
            ->exists();
        self::assertFalse($exists);
        
        // 测试删除不存在的消息
        $result = $this->delete('/system-message/user/delete/99999', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), 404);
    }

    /**
     * 测试批量删除用户消息
     */
    public function testBatchDelete(): void
    {
        // 创建多个测试消息和用户消息
        $message1 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 1',
            'content' => 'Test content 1',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $message2 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 2',
            'content' => 'Test content 2',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message1->id,
            'is_read' => false
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message2->id,
            'is_read' => false
        ]);

        $uri = '/system-message/user/batchDelete';
        
        // 测试未授权访问
        $result = $this->delete($uri, ['message_ids' => [$message1->id, $message2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试批量删除
        $result = $this->delete($uri, ['token' => $this->token, 'message_ids' => [$message1->id, $message2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.deleted'), 2);
        
        // 验证消息已被删除
        $count = UserMessage::where('user_id', $this->user->id)->count();
        self::assertSame($count, 0);
        
        // 测试空ID数组
        $result = $this->delete($uri, ['token' => $this->token, 'message_ids' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试非数组参数
        $result = $this->delete($uri, ['token' => $this->token, 'message_ids' => 'invalid']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试获取未读消息数量
     */
    public function testGetUnreadCount(): void
    {
        // 创建测试消息和用户消息
        $message1 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 1',
            'content' => 'Test content 1',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $message2 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 2',
            'content' => 'Test content 2',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message1->id,
            'is_read' => false
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message2->id,
            'is_read' => true // 已读
        ]);

        $uri = '/system-message/user/unreadCount';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试获取未读数量
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.count'), 1); // 只有一条未读消息
    }

    /**
     * 测试获取消息类型统计
     */
    public function testGetTypeStats(): void
    {
        // 创建不同类型的测试消息和用户消息
        $systemMessage = Message::create([
            'type' => 'system',
            'title' => 'System Message',
            'content' => 'System content',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $userMessage = Message::create([
            'type' => 'user',
            'title' => 'User Message',
            'content' => 'User content',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $systemMessage->id,
            'is_read' => false
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $userMessage->id,
            'is_read' => true
        ]);

        $uri = '/system-message/user/typeStats';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试获取类型统计
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
    }

    /**
     * 测试搜索用户消息
     */
    public function testSearch(): void
    {
        // 创建测试消息和用户消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Searchable Message',
            'content' => 'This is searchable content',
            'sender_id' => 1,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        UserMessage::create([
            'user_id' => $this->user->id,
            'message_id' => $message->id,
            'is_read' => false
        ]);

        $uri = '/system-message/user/search';
        
        // 测试未授权访问
        $result = $this->get($uri, ['keyword' => 'searchable']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试搜索
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => 'searchable']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试空关键词
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => '']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试带过滤条件的搜索
        $result = $this->get($uri, [
            'token' => $this->token,
            'keyword' => 'searchable',
            'is_read' => 0,
            'type' => 'system',
            'priority' => 'normal'
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试搜索不存在的内容
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => 'nonexistent']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        // 应该返回空结果但不报错
    }
}