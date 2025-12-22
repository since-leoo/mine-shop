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
use Hyperf\Stringable\Str;
use HyperfTests\Feature\Admin\ControllerCase;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Model\MessageTemplate;

/**
 * @internal
 * @coversNothing
 */
final class MessageControllerTest extends ControllerCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 清理测试数据
        Message::truncate();
        MessageTemplate::truncate();
    }

    protected function tearDown(): void
    {
        // 清理测试数据
        Message::truncate();
        MessageTemplate::truncate();
        
        parent::tearDown();
    }

    /**
     * 测试获取消息列表
     */
    public function testIndex(): void
    {
        $uri = '/admin/system-message/index';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:index'));
        
        // 测试有权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试带参数的列表查询
        $result = $this->get($uri, [
            'token' => $this->token,
            'type' => 'system',
            'status' => 'sent',
            'page' => 1,
            'page_size' => 10
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 删除权限后测试
        $this->deletePermissions('system-message:index');
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
    }

    /**
     * 测试获取消息详情
     */
    public function testRead(): void
    {
        // 创建测试消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Test Message',
            'content' => 'Test content',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft'
        ]);

        $uri = "/admin/system-message/read/{$message->id}";
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:read'));
        
        // 测试有权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.id'), $message->id);
        
        // 测试不存在的消息
        $result = $this->get('/admin/system-message/read/99999', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), 404);
    }

    /**
     * 测试创建消息
     */
    public function testSave(): void
    {
        $uri = '/admin/system-message/save';
        $data = [
            'type' => 'system',
            'title' => 'New Test Message',
            'content' => 'New test content',
            'recipient_type' => 'all',
            'priority' => 'normal'
        ];
        
        // 测试未授权访问
        $result = $this->post($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:save'));
        
        // 测试有权限创建
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.title'), $data['title']);
        self::assertSame(Arr::get($result, 'data.sender_id'), $this->user->id);
        
        // 测试无效数据
        $invalidData = ['token' => $this->token, 'title' => '']; // 缺少必需字段
        $result = $this->post($uri, $invalidData);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试更新消息
     */
    public function testUpdate(): void
    {
        // 创建测试消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Original Title',
            'content' => 'Original content',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft'
        ]);

        $uri = "/admin/system-message/update/{$message->id}";
        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'priority' => 'high'
        ];
        
        // 测试未授权访问
        $result = $this->put($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->put($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:update'));
        
        // 测试有权限更新
        $result = $this->put($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.title'), $data['title']);
        
        // 测试更新不存在的消息
        $result = $this->put('/admin/system-message/update/99999', array_merge($data, ['token' => $this->token]));
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试删除消息
     */
    public function testDelete(): void
    {
        // 创建测试消息
        $message1 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 1',
            'content' => 'Test content 1',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft'
        ]);

        $message2 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 2',
            'content' => 'Test content 2',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft'
        ]);

        $uri = '/admin/system-message/delete';
        
        // 测试未授权访问
        $result = $this->delete($uri, ['ids' => [$message1->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => [$message1->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:delete'));
        
        // 测试单个删除
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => [$message1->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.deleted'), 1);
        
        // 测试批量删除
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => [$message2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空ID数组
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试不存在的ID
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => [99999]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertSame(Arr::get($result, 'data.failed'), 1);
    }

    /**
     * 测试发送消息
     */
    public function testSend(): void
    {
        // 创建测试消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Test Message',
            'content' => 'Test content',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft'
        ]);

        $uri = '/admin/system-message/send';
        
        // 测试未授权访问
        $result = $this->post($uri, ['id' => $message->id]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, ['token' => $this->token, 'id' => $message->id]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:send'));
        
        // 测试发送消息
        $result = $this->post($uri, ['token' => $this->token, 'id' => $message->id]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空ID
        $result = $this->post($uri, ['token' => $this->token]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试不存在的消息
        $result = $this->post($uri, ['token' => $this->token, 'id' => 99999]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试调度消息
     */
    public function testSchedule(): void
    {
        // 创建测试消息
        $message = Message::create([
            'type' => 'system',
            'title' => 'Test Message',
            'content' => 'Test content',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft'
        ]);

        $uri = '/admin/system-message/schedule';
        $scheduledAt = now()->addHour()->toDateTimeString();
        
        // 测试未授权访问
        $result = $this->post($uri, ['id' => $message->id, 'scheduled_at' => $scheduledAt]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, ['token' => $this->token, 'id' => $message->id, 'scheduled_at' => $scheduledAt]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:schedule'));
        
        // 测试调度消息
        $result = $this->post($uri, ['token' => $this->token, 'id' => $message->id, 'scheduled_at' => $scheduledAt]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试缺少参数
        $result = $this->post($uri, ['token' => $this->token, 'id' => $message->id]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        $result = $this->post($uri, ['token' => $this->token, 'scheduled_at' => $scheduledAt]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试批量发送消息
     */
    public function testBatchSend(): void
    {
        // 创建测试消息
        $message1 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 1',
            'content' => 'Test content 1',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft'
        ]);

        $message2 = Message::create([
            'type' => 'system',
            'title' => 'Test Message 2',
            'content' => 'Test content 2',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft'
        ]);

        $uri = '/admin/system-message/batchSend';
        
        // 测试未授权访问
        $result = $this->post($uri, ['ids' => [$message1->id, $message2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, ['token' => $this->token, 'ids' => [$message1->id, $message2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:batchSend'));
        
        // 测试批量发送
        $result = $this->post($uri, ['token' => $this->token, 'ids' => [$message1->id, $message2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试空ID数组
        $result = $this->post($uri, ['token' => $this->token, 'ids' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试非数组参数
        $result = $this->post($uri, ['token' => $this->token, 'ids' => 'invalid']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试搜索消息
     */
    public function testSearch(): void
    {
        // 创建测试消息
        Message::create([
            'type' => 'system',
            'title' => 'Searchable Message',
            'content' => 'This is searchable content',
            'sender_id' => $this->user->id,
            'recipient_type' => 'all',
            'priority' => 'normal',
            'status' => 'sent'
        ]);

        $uri = '/admin/system-message/search';
        
        // 测试未授权访问
        $result = $this->get($uri, ['keyword' => 'searchable']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问（使用index权限）
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => 'searchable']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:index'));
        
        // 测试搜索
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => 'searchable']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空关键词
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => '']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试带过滤条件的搜索
        $result = $this->get($uri, [
            'token' => $this->token,
            'keyword' => 'searchable',
            'type' => 'system',
            'status' => 'sent'
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试获取消息统计
     */
    public function testStatistics(): void
    {
        $uri = '/admin/system-message/statistics';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:statistics'));
        
        // 测试获取统计
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
    }

    /**
     * 测试获取热门消息
     */
    public function testPopular(): void
    {
        $uri = '/admin/system-message/popular';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:index'));
        
        // 测试获取热门消息
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试带限制参数
        $result = $this->get($uri, ['token' => $this->token, 'limit' => 5]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试获取最近消息
     */
    public function testRecent(): void
    {
        $uri = '/admin/system-message/recent';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message:index'));
        
        // 测试获取最近消息
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试带参数
        $result = $this->get($uri, ['token' => $this->token, 'days' => 30, 'limit' => 10]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }
}