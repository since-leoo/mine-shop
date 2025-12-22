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
use Plugin\Since\SystemMessage\Model\UserNotificationPreference;

/**
 * @internal
 * @coversNothing
 */
final class PreferenceControllerTest extends ControllerCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 清理测试数据
        UserNotificationPreference::truncate();
    }

    protected function tearDown(): void
    {
        // 清理测试数据
        UserNotificationPreference::truncate();
        
        parent::tearDown();
    }

    /**
     * 测试获取用户通知偏好设置
     */
    public function testIndex(): void
    {
        $uri = '/system-message/preference/index';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试有权限访问（用户偏好设置不需要特殊权限，只需要登录）
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
    }

    /**
     * 测试更新用户通知偏好设置
     */
    public function testUpdate(): void
    {
        $uri = '/system-message/preference/update';
        $data = [
            'channels' => [
                'database' => true,
                'email' => false,
                'sms' => true,
                'websocket' => true
            ],
            'message_types' => [
                'system' => true,
                'user' => false,
                'announcement' => true
            ],
            'do_not_disturb_enabled' => true,
            'do_not_disturb_start' => '22:00',
            'do_not_disturb_end' => '08:00',
            'min_priority' => 2
        ];
        
        // 测试未授权访问
        $result = $this->put($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试更新偏好设置
        $result = $this->put($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 验证数据已保存
        $preference = UserNotificationPreference::where('user_id', $this->user->id)->first();
        self::assertNotNull($preference);
        self::assertTrue($preference->do_not_disturb_enabled);
        self::assertSame($preference->min_priority, 2);
        
        // 测试无效数据
        $invalidData = ['token' => $this->token, 'min_priority' => 10]; // 超出范围
        $result = $this->put($uri, $invalidData);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试重置用户通知偏好设置
     */
    public function testReset(): void
    {
        // 先创建一个自定义偏好设置
        UserNotificationPreference::create([
            'user_id' => $this->user->id,
            'channels' => json_encode(['database' => false, 'email' => false]),
            'message_types' => json_encode(['system' => false]),
            'do_not_disturb_enabled' => true,
            'do_not_disturb_start' => '23:00',
            'do_not_disturb_end' => '07:00',
            'min_priority' => 3
        ]);

        $uri = '/system-message/preference/reset';
        
        // 测试未授权访问
        $result = $this->post($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试重置偏好设置
        $result = $this->post($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 验证设置已重置为默认值
        $preference = UserNotificationPreference::where('user_id', $this->user->id)->first();
        self::assertNotNull($preference);
        // 默认值应该是启用所有渠道和消息类型
        $channels = json_decode($preference->channels, true);
        self::assertTrue($channels['database'] ?? false);
    }

    /**
     * 测试获取默认通知偏好设置
     */
    public function testGetDefaults(): void
    {
        $uri = '/system-message/preference/defaults';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试获取默认设置
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
    }

    /**
     * 测试更新渠道偏好设置
     */
    public function testUpdateChannelPreferences(): void
    {
        $uri = '/system-message/preference/updateChannels';
        $data = [
            'channels' => [
                'database' => true,
                'email' => false,
                'sms' => true,
                'websocket' => false
            ]
        ];
        
        // 测试未授权访问
        $result = $this->put($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试更新渠道偏好
        $result = $this->put($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空渠道数据
        $result = $this->put($uri, ['token' => $this->token, 'channels' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试非数组渠道数据
        $result = $this->put($uri, ['token' => $this->token, 'channels' => 'invalid']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试更新消息类型偏好设置
     */
    public function testUpdateTypePreferences(): void
    {
        $uri = '/system-message/preference/updateTypes';
        $data = [
            'types' => [
                'system' => true,
                'user' => false,
                'announcement' => true,
                'alert' => false
            ]
        ];
        
        // 测试未授权访问
        $result = $this->put($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试更新消息类型偏好
        $result = $this->put($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空类型数据
        $result = $this->put($uri, ['token' => $this->token, 'types' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试非数组类型数据
        $result = $this->put($uri, ['token' => $this->token, 'types' => 'invalid']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试设置免打扰时间
     */
    public function testSetDoNotDisturbTime(): void
    {
        $uri = '/system-message/preference/setDoNotDisturbTime';
        $data = [
            'start_time' => '22:00',
            'end_time' => '08:00',
            'enabled' => true
        ];
        
        // 测试未授权访问
        $result = $this->put($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试设置免打扰时间
        $result = $this->put($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试缺少开始时间
        $result = $this->put($uri, ['token' => $this->token, 'end_time' => '08:00', 'enabled' => true]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试缺少结束时间
        $result = $this->put($uri, ['token' => $this->token, 'start_time' => '22:00', 'enabled' => true]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试禁用免打扰
        $result = $this->put($uri, [
            'token' => $this->token,
            'start_time' => '22:00',
            'end_time' => '08:00',
            'enabled' => false
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试启用/禁用免打扰
     */
    public function testToggleDoNotDisturb(): void
    {
        $uri = '/system-message/preference/toggleDoNotDisturb';
        
        // 测试未授权访问
        $result = $this->put($uri, ['enabled' => true]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试启用免打扰
        $result = $this->put($uri, ['token' => $this->token, 'enabled' => true]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试禁用免打扰
        $result = $this->put($uri, ['token' => $this->token, 'enabled' => false]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试默认值（应该转换为false）
        $result = $this->put($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试设置最小优先级
     */
    public function testSetMinPriority(): void
    {
        $uri = '/system-message/preference/setMinPriority';
        
        // 测试未授权访问
        $result = $this->put($uri, ['priority' => 2]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试设置有效优先级
        $result = $this->put($uri, ['token' => $this->token, 'priority' => 2]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试边界值
        $result = $this->put($uri, ['token' => $this->token, 'priority' => 1]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        $result = $this->put($uri, ['token' => $this->token, 'priority' => 5]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试无效优先级（小于1）
        $result = $this->put($uri, ['token' => $this->token, 'priority' => 0]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试无效优先级（大于5）
        $result = $this->put($uri, ['token' => $this->token, 'priority' => 6]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试负数优先级
        $result = $this->put($uri, ['token' => $this->token, 'priority' => -1]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试检查是否在免打扰时间内
     */
    public function testCheckDoNotDisturb(): void
    {
        // 先设置免打扰时间
        UserNotificationPreference::create([
            'user_id' => $this->user->id,
            'channels' => json_encode(['database' => true]),
            'message_types' => json_encode(['system' => true]),
            'do_not_disturb_enabled' => true,
            'do_not_disturb_start' => '22:00',
            'do_not_disturb_end' => '08:00',
            'min_priority' => 1
        ]);

        $uri = '/system-message/preference/checkDoNotDisturb';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试检查免打扰状态
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('is_active', Arr::get($result, 'data'));
        
        // 结果应该是布尔值
        $isActive = Arr::get($result, 'data.is_active');
        self::assertIsBool($isActive);
    }

    /**
     * 测试完整的偏好设置工作流
     */
    public function testCompletePreferenceWorkflow(): void
    {
        // 1. 获取默认设置
        $result = $this->get('/system-message/preference/defaults', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 2. 获取当前设置（应该是默认值）
        $result = $this->get('/system-message/preference/index', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 3. 更新渠道偏好
        $result = $this->put('/system-message/preference/updateChannels', [
            'token' => $this->token,
            'channels' => ['database' => true, 'email' => false]
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 4. 更新消息类型偏好
        $result = $this->put('/system-message/preference/updateTypes', [
            'token' => $this->token,
            'types' => ['system' => true, 'user' => false]
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 5. 设置免打扰时间
        $result = $this->put('/system-message/preference/setDoNotDisturbTime', [
            'token' => $this->token,
            'start_time' => '23:00',
            'end_time' => '07:00',
            'enabled' => true
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 6. 设置最小优先级
        $result = $this->put('/system-message/preference/setMinPriority', [
            'token' => $this->token,
            'priority' => 3
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 7. 检查免打扰状态
        $result = $this->get('/system-message/preference/checkDoNotDisturb', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 8. 验证设置已保存
        $result = $this->get('/system-message/preference/index', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        $preference = Arr::get($result, 'data');
        self::assertNotNull($preference);
        
        // 9. 重置为默认值
        $result = $this->post('/system-message/preference/reset', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 10. 验证已重置
        $result = $this->get('/system-message/preference/index', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }
}