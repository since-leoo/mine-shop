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

namespace HyperfTests\Feature\Admin\Seckill;

use App\Interface\Common\ResultCode;
use Hyperf\Stringable\Str;
use HyperfTests\Feature\Admin\ControllerCase;
use App\Infrastructure\Model\Seckill\SeckillActivity;

/**
 * @internal
 * @coversNothing
 */
final class SeckillActivityControllerTest extends ControllerCase
{
    protected function tearDown(): void
    {
        SeckillActivity::truncate();
        parent::tearDown();
    }

    public function testPageList(): void
    {
        $result = $this->get('/admin/seckill/activity/list');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $result = $this->get('/admin/seckill/activity/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);

        $this->forAddPermission('seckill:activity:list');
        $result = $this->get('/admin/seckill/activity/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('list', $result['data']);
        self::assertArrayHasKey('total', $result['data']);

        $this->deletePermissions('seckill:activity:list');
        $result = $this->get('/admin/seckill/activity/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);
    }

    public function testStats(): void
    {
        $this->forAddPermission('seckill:activity:list');
        $result = $this->get('/admin/seckill/activity/stats', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('total', $result['data']);
        self::assertArrayHasKey('enabled', $result['data']);
        self::assertArrayHasKey('disabled', $result['data']);
    }

    public function testCreate(): void
    {
        $result = $this->post('/admin/seckill/activity');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:activity:create');

        // Test validation
        $result = $this->post('/admin/seckill/activity', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::UNPROCESSABLE_ENTITY->value);

        // Test successful creation
        $fill = [
            'title' => '测试秒杀活动' . Str::random(5),
            'description' => '这是一个测试活动',
            'status' => 'pending',
            'is_enabled' => true,
        ];
        $result = $this->post('/admin/seckill/activity', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('id', $result['data']);
        self::assertSame($fill['title'], $result['data']['title']);

        // Verify in database
        $entity = SeckillActivity::find($result['data']['id']);
        self::assertNotNull($entity);
        self::assertSame($fill['title'], $entity->title);
        self::assertSame($fill['status'], $entity->status);
        self::assertTrue($entity->is_enabled);
    }

    public function testShow(): void
    {
        $entity = SeckillActivity::create([
            'title' => '测试活动' . Str::random(5),
            'description' => '测试描述',
            'status' => 'pending',
            'is_enabled' => true,
        ]);

        $result = $this->get('/admin/seckill/activity/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:activity:read');
        $result = $this->get('/admin/seckill/activity/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertSame($entity->title, $result['data']['title']);

        // Test non-existent
        $result = $this->get('/admin/seckill/activity/999999', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FAIL->value);
    }

    public function testUpdate(): void
    {
        $entity = SeckillActivity::create([
            'title' => '测试活动' . Str::random(5),
            'description' => '测试描述',
            'status' => 'pending',
            'is_enabled' => true,
        ]);

        $result = $this->put('/admin/seckill/activity/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:activity:update');

        $fill = [
            'title' => '更新后的活动' . Str::random(5),
            'description' => '更新后的描述',
            'status' => 'active',
            'is_enabled' => false,
        ];
        $result = $this->put('/admin/seckill/activity/' . $entity->id, $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify update
        $entity->refresh();
        self::assertSame($fill['title'], $entity->title);
        self::assertSame($fill['status'], $entity->status);
        self::assertFalse($entity->is_enabled);
    }

    public function testDelete(): void
    {
        $entity = SeckillActivity::create([
            'title' => '测试活动' . Str::random(5),
            'description' => '测试描述',
            'status' => 'pending',
            'is_enabled' => true,
        ]);

        $result = $this->delete('/admin/seckill/activity/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:activity:delete');
        $result = $this->delete('/admin/seckill/activity/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify deleted
        self::assertNull(SeckillActivity::find($entity->id));
    }

    public function testToggleStatus(): void
    {
        $entity = SeckillActivity::create([
            'title' => '测试活动' . Str::random(5),
            'description' => '测试描述',
            'status' => 'pending',
            'is_enabled' => true,
        ]);

        $this->forAddPermission('seckill:activity:update');
        $result = $this->put('/admin/seckill/activity/' . $entity->id . '/toggle-status', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify toggled
        $entity->refresh();
        self::assertFalse($entity->is_enabled);

        // Toggle back
        $result = $this->put('/admin/seckill/activity/' . $entity->id . '/toggle-status', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        $entity->refresh();
        self::assertTrue($entity->is_enabled);
    }
}
