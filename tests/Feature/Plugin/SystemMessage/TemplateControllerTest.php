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
use Plugin\Since\SystemMessage\Model\MessageTemplate;

/**
 * @internal
 * @coversNothing
 */
final class TemplateControllerTest extends ControllerCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 清理测试数据
        MessageTemplate::truncate();
    }

    protected function tearDown(): void
    {
        // 清理测试数据
        MessageTemplate::truncate();
        
        parent::tearDown();
    }

    /**
     * 测试获取模板列表
     */
    public function testIndex(): void
    {
        $uri = '/admin/system-message/template/index';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:index'));
        
        // 测试有权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试带参数的列表查询
        $result = $this->get($uri, [
            'token' => $this->token,
            'type' => 'system',
            'category' => 'notification',
            'is_active' => 1,
            'page' => 1,
            'page_size' => 10
        ]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 删除权限后测试
        $this->deletePermissions('system-message-template:index');
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
    }

    /**
     * 测试获取模板详情
     */
    public function testRead(): void
    {
        // 创建测试模板
        $template = MessageTemplate::create([
            'name' => 'Test Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}, your account is {{status}}',
            'variables' => json_encode(['name', 'status']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = "/admin/system-message/template/read/{$template->id}";
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:read'));
        
        // 测试有权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.id'), $template->id);
        
        // 测试不存在的模板
        $result = $this->get('/admin/system-message/template/read/99999', ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), 404);
    }

    /**
     * 测试创建模板
     */
    public function testSave(): void
    {
        $uri = '/admin/system-message/template/save';
        $data = [
            'name' => 'New Test Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}',
            'variables' => ['name'],
            'is_active' => true
        ];
        
        // 测试未授权访问
        $result = $this->post($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:save'));
        
        // 测试有权限创建
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.name'), $data['name']);
        self::assertSame(Arr::get($result, 'data.created_by'), $this->user->id);
        
        // 测试无效数据
        $invalidData = ['token' => $this->token, 'name' => '']; // 缺少必需字段
        $result = $this->post($uri, $invalidData);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试更新模板
     */
    public function testUpdate(): void
    {
        // 创建测试模板
        $template = MessageTemplate::create([
            'name' => 'Original Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Original Title',
            'content_template' => 'Original Content',
            'variables' => json_encode(['name']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = "/admin/system-message/template/update/{$template->id}";
        $data = [
            'name' => 'Updated Template',
            'title_template' => 'Updated Title {{name}}',
            'content_template' => 'Updated Content {{name}}',
            'variables' => ['name', 'email']
        ];
        
        // 测试未授权访问
        $result = $this->put($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->put($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:update'));
        
        // 测试有权限更新
        $result = $this->put($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.name'), $data['name']);
        
        // 测试更新不存在的模板
        $result = $this->put('/admin/system-message/template/update/99999', array_merge($data, ['token' => $this->token]));
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试删除模板
     */
    public function testDelete(): void
    {
        // 创建测试模板
        $template1 = MessageTemplate::create([
            'name' => 'Test Template 1',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Title 1',
            'content_template' => 'Content 1',
            'variables' => json_encode(['name']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $template2 = MessageTemplate::create([
            'name' => 'Test Template 2',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Title 2',
            'content_template' => 'Content 2',
            'variables' => json_encode(['name']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = '/admin/system-message/template/delete';
        
        // 测试未授权访问
        $result = $this->delete($uri, ['ids' => [$template1->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => [$template1->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:delete'));
        
        // 测试单个删除
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => [$template1->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.deleted'), 1);
        
        // 测试批量删除
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => [$template2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空ID数组
        $result = $this->delete($uri, ['token' => $this->token, 'ids' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试预览模板
     */
    public function testPreview(): void
    {
        // 创建测试模板
        $template = MessageTemplate::create([
            'name' => 'Test Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}, your status is {{status}}',
            'variables' => json_encode(['name', 'status']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = '/admin/system-message/template/preview';
        $data = [
            'id' => $template->id,
            'variables' => [
                'name' => 'John Doe',
                'status' => 'active'
            ]
        ];
        
        // 测试未授权访问
        $result = $this->post($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:read'));
        
        // 测试预览模板
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试空ID
        $result = $this->post($uri, ['token' => $this->token, 'variables' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试不存在的模板
        $result = $this->post($uri, ['token' => $this->token, 'id' => 99999, 'variables' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试渲染模板
     */
    public function testRender(): void
    {
        // 创建测试模板
        $template = MessageTemplate::create([
            'name' => 'Test Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}',
            'variables' => json_encode(['name']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = '/admin/system-message/template/render';
        $data = [
            'id' => $template->id,
            'variables' => ['name' => 'John Doe']
        ];
        
        // 测试未授权访问
        $result = $this->post($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:read'));
        
        // 测试渲染模板
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空ID
        $result = $this->post($uri, ['token' => $this->token]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试验证模板变量
     */
    public function testValidateVariables(): void
    {
        // 创建测试模板
        $template = MessageTemplate::create([
            'name' => 'Test Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}, email: {{email}}',
            'variables' => json_encode(['name', 'email']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = '/admin/system-message/template/validateVariables';
        
        // 测试未授权访问
        $result = $this->post($uri, ['id' => $template->id, 'variables' => ['name' => 'John']]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, ['token' => $this->token, 'id' => $template->id, 'variables' => ['name' => 'John']]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:read'));
        
        // 测试验证变量
        $result = $this->post($uri, ['token' => $this->token, 'id' => $template->id, 'variables' => ['name' => 'John', 'email' => 'john@example.com']]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空ID
        $result = $this->post($uri, ['token' => $this->token, 'variables' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试获取模板变量
     */
    public function testGetVariables(): void
    {
        // 创建测试模板
        $template = MessageTemplate::create([
            'name' => 'Test Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}, email: {{email}}',
            'variables' => json_encode(['name', 'email']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = "/admin/system-message/template/getVariables/{$template->id}";
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:read'));
        
        // 测试获取变量
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试不存在的模板
        $result = $this->get('/admin/system-message/template/getVariables/99999', ['token' => $this->token]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试复制模板
     */
    public function testCopy(): void
    {
        // 创建测试模板
        $template = MessageTemplate::create([
            'name' => 'Original Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}',
            'variables' => json_encode(['name']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = '/admin/system-message/template/copy';
        $data = [
            'id' => $template->id,
            'name' => 'Copied Template'
        ];
        
        // 测试未授权访问
        $result = $this->post($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:save'));
        
        // 测试复制模板
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        self::assertSame(Arr::get($result, 'data.name'), $data['name']);
        
        // 测试空ID
        $result = $this->post($uri, ['token' => $this->token, 'name' => 'New Name']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试更新模板状态
     */
    public function testChangeStatus(): void
    {
        // 创建测试模板
        $template = MessageTemplate::create([
            'name' => 'Test Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}',
            'variables' => json_encode(['name']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = '/admin/system-message/template/changeStatus';
        
        // 测试未授权访问
        $result = $this->put($uri, ['id' => $template->id]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->put($uri, ['token' => $this->token, 'id' => $template->id]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:update'));
        
        // 测试更新状态
        $result = $this->put($uri, ['token' => $this->token, 'id' => $template->id]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空ID
        $result = $this->put($uri, ['token' => $this->token]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试搜索模板
     */
    public function testSearch(): void
    {
        // 创建测试模板
        MessageTemplate::create([
            'name' => 'Searchable Template',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Searchable Title',
            'content_template' => 'This is searchable content',
            'variables' => json_encode(['name']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = '/admin/system-message/template/search';
        
        // 测试未授权访问
        $result = $this->get($uri, ['keyword' => 'searchable']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => 'searchable']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:index'));
        
        // 测试搜索
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => 'searchable']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空关键词
        $result = $this->get($uri, ['token' => $this->token, 'keyword' => '']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试获取模板分类
     */
    public function testGetCategories(): void
    {
        $uri = '/admin/system-message/template/categories';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:index'));
        
        // 测试获取分类
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
    }

    /**
     * 测试获取活跃模板
     */
    public function testGetActiveTemplates(): void
    {
        $uri = '/admin/system-message/template/active';
        
        // 测试未授权访问
        $result = $this->get($uri);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:index'));
        
        // 测试获取活跃模板
        $result = $this->get($uri, ['token' => $this->token]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试带类型参数
        $result = $this->get($uri, ['token' => $this->token, 'type' => 'system']);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试导入模板
     */
    public function testImport(): void
    {
        $uri = '/admin/system-message/template/import';
        $data = [
            'templates' => [
                [
                    'name' => 'Imported Template 1',
                    'type' => 'system',
                    'category' => 'notification',
                    'title_template' => 'Hello {{name}}',
                    'content_template' => 'Welcome {{name}}',
                    'variables' => ['name'],
                    'is_active' => true
                ],
                [
                    'name' => 'Imported Template 2',
                    'type' => 'user',
                    'category' => 'alert',
                    'title_template' => 'Alert {{type}}',
                    'content_template' => 'Alert: {{message}}',
                    'variables' => ['type', 'message'],
                    'is_active' => true
                ]
            ]
        ];
        
        // 测试未授权访问
        $result = $this->post($uri, $data);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:import'));
        
        // 测试导入模板
        $result = $this->post($uri, array_merge($data, ['token' => $this->token]));
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试空数据
        $result = $this->post($uri, ['token' => $this->token, 'templates' => []]);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        
        // 测试非数组数据
        $result = $this->post($uri, ['token' => $this->token, 'templates' => 'invalid']);
        self::assertNotSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }

    /**
     * 测试导出模板
     */
    public function testExport(): void
    {
        // 创建测试模板
        $template1 = MessageTemplate::create([
            'name' => 'Export Template 1',
            'type' => 'system',
            'category' => 'notification',
            'title_template' => 'Hello {{name}}',
            'content_template' => 'Welcome {{name}}',
            'variables' => json_encode(['name']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $template2 = MessageTemplate::create([
            'name' => 'Export Template 2',
            'type' => 'user',
            'category' => 'alert',
            'title_template' => 'Alert {{type}}',
            'content_template' => 'Alert: {{message}}',
            'variables' => json_encode(['type', 'message']),
            'is_active' => true,
            'created_by' => $this->user->id
        ]);

        $uri = '/admin/system-message/template/export';
        
        // 测试未授权访问
        $result = $this->post($uri, ['ids' => [$template1->id, $template2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::UNAUTHORIZED->value);
        
        // 测试无权限访问
        $result = $this->post($uri, ['token' => $this->token, 'ids' => [$template1->id, $template2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::FORBIDDEN->value);
        
        // 添加权限
        self::assertTrue($this->addPermissions('system-message-template:export'));
        
        // 测试导出模板
        $result = $this->post($uri, ['token' => $this->token, 'ids' => [$template1->id, $template2->id]]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
        self::assertArrayHasKey('data', $result);
        
        // 测试导出所有模板（空ID数组）
        $result = $this->post($uri, ['token' => $this->token, 'ids' => []]);
        self::assertSame(Arr::get($result, 'code'), ResultCode::SUCCESS->value);
    }
}