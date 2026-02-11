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
use Carbon\Carbon;
use Hyperf\Stringable\Str;
use HyperfTests\Feature\Admin\ControllerCase;
use App\Infrastructure\Model\Seckill\SeckillActivity;
use App\Infrastructure\Model\Seckill\SeckillSession;

/**
 * @internal
 * @coversNothing
 */
final class SeckillSessionControllerTest extends ControllerCase
{
    private SeckillActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activity = SeckillActivity::create([
            'title' => '测试活动' . Str::random(5),
            'description' => '测试描述',
            'status' => 'pending',
            'is_enabled' => true,
        ]);
    }

    protected function tearDown(): void
    {
        SeckillSession::truncate();
        SeckillActivity::truncate();
        parent::tearDown();
    }

    public function testPageList(): void
    {
        $result = $this->get('/admin/seckill/session/list');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $result = $this->get('/admin/seckill/session/list', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::FORBIDDEN->value);

        $this->forAddPermission('seckill:session:list');
        $result = $this->get('/admin/seckill/session/list', ['activity_id' => $this->activity->id], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('list', $result['data']);
        self::assertArrayHasKey('total', $result['data']);
    }

    public function testByActivity(): void
    {
        $session = SeckillSession::create([
            'activity_id' => $this->activity->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
            'status' => 'pending',
            'is_enabled' => true,
            'max_quantity_per_user' => 1,
        ]);

        $this->forAddPermission('seckill:session:list');
        $result = $this->get('/admin/seckill/session/by-activity/' . $this->activity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertIsArray($result['data']);
        self::assertGreaterThanOrEqual(1, \count($result['data']));
    }

    public function testCreate(): void
    {
        $result = $this->post('/admin/seckill/session');
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:session:create');

        // Test validation
        $result = $this->post('/admin/seckill/session', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::UNPROCESSABLE_ENTITY->value);

        // Test successful creation
        $startTime = Carbon::now()->addHour()->format('Y-m-d H:i:s');
        $endTime = Carbon::now()->addHours(2)->format('Y-m-d H:i:s');
        $fill = [
            'activity_id' => $this->activity->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'pending',
            'is_enabled' => true,
            'max_quantity_per_user' => 2,
        ];
        $result = $this->post('/admin/seckill/session', $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertArrayHasKey('id', $result['data']);
        self::assertSame((int) $this->activity->id, (int) $result['data']['activity_id']);

        // Verify in database
        $entity = SeckillSession::find($result['data']['id']);
        self::assertNotNull($entity);
        self::assertSame((int) $this->activity->id, (int) $entity->activity_id);
    }

    public function testShow(): void
    {
        $entity = SeckillSession::create([
            'activity_id' => $this->activity->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
            'status' => 'pending',
            'is_enabled' => true,
            'max_quantity_per_user' => 1,
        ]);

        $result = $this->get('/admin/seckill/session/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:session:read');
        $result = $this->get('/admin/seckill/session/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);
        self::assertSame((int) $entity->id, (int) $result['data']['id']);
    }

    public function testUpdate(): void
    {
        $entity = SeckillSession::create([
            'activity_id' => $this->activity->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
            'status' => 'pending',
            'is_enabled' => true,
            'max_quantity_per_user' => 1,
        ]);

        $result = $this->put('/admin/seckill/session/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:session:update');

        $newStartTime = Carbon::now()->addHours(3)->format('Y-m-d H:i:s');
        $newEndTime = Carbon::now()->addHours(4)->format('Y-m-d H:i:s');
        $fill = [
            'start_time' => $newStartTime,
            'end_time' => $newEndTime,
            'status' => 'active',
            'is_enabled' => false,
            'max_quantity_per_user' => 5,
        ];
        $result = $this->put('/admin/seckill/session/' . $entity->id, $fill, $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify update
        $entity->refresh();
        self::assertFalse($entity->is_enabled);
        self::assertSame(5, $entity->max_quantity_per_user);
    }

    public function testDelete(): void
    {
        $entity = SeckillSession::create([
            'activity_id' => $this->activity->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
            'status' => 'pending',
            'is_enabled' => true,
            'max_quantity_per_user' => 1,
        ]);

        $result = $this->delete('/admin/seckill/session/' . $entity->id);
        self::assertSame($result['code'], ResultCode::UNAUTHORIZED->value);

        $this->forAddPermission('seckill:session:delete');
        $result = $this->delete('/admin/seckill/session/' . $entity->id, [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify deleted
        self::assertNull(SeckillSession::find($entity->id));
    }

    public function testToggleStatus(): void
    {
        $entity = SeckillSession::create([
            'activity_id' => $this->activity->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
            'status' => 'pending',
            'is_enabled' => true,
            'max_quantity_per_user' => 1,
        ]);

        $this->forAddPermission('seckill:session:update');
        $result = $this->put('/admin/seckill/session/' . $entity->id . '/toggle-status', [], $this->authHeader());
        self::assertSame($result['code'], ResultCode::SUCCESS->value);

        // Verify toggled
        $entity->refresh();
        self::assertFalse($entity->is_enabled);
    }
}
