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

namespace HyperfTests\Feature\Admin\Coupon;

use App\Infrastructure\Model\Coupon\Coupon;
use App\Infrastructure\Model\Coupon\CouponUser;
use App\Infrastructure\Model\Member\Member;
use App\Interface\Common\ResultCode;
use Hyperf\Stringable\Str;
use HyperfTests\Feature\Admin\ControllerCase;
use Illuminate\Support\Carbon;

/**
 * @internal
 * @coversNothing
 */
final class CouponControllerTest extends ControllerCase
{
    protected function tearDown(): void
    {
        CouponUser::truncate();
        Coupon::truncate();
        Member::truncate();
        parent::tearDown();
    }

    public function testCouponCrudFlow(): void
    {
        $this->forAddPermission('coupon:list');
        $result = $this->get('/admin/coupon/list', [], $this->authHeader());
        self::assertSame(ResultCode::SUCCESS->value, $result['code']);

        $this->forAddPermission('coupon:create');
        $payload = [
            'name' => '测试优惠券' . Str::random(5),
            'type' => 'fixed',
            'value' => 10,
            'min_amount' => 100,
            'total_quantity' => 100,
            'per_user_limit' => 2,
            'start_time' => Carbon::now()->subDay()->toDateTimeString(),
            'end_time' => Carbon::now()->addDays(10)->toDateTimeString(),
        ];
        $result = $this->post('/admin/coupon', $payload, $this->authHeader());
        self::assertSame(ResultCode::SUCCESS->value, $result['code']);
        $couponId = $result['data']['id'];

        $this->forAddPermission('coupon:update');
        $payload['name'] = '更新后的优惠券';
        $result = $this->put('/admin/coupon/' . $couponId, $payload, $this->authHeader());
        self::assertSame(ResultCode::SUCCESS->value, $result['code']);

        $this->forAddPermission('coupon:update');
        $result = $this->put('/admin/coupon/' . $couponId . '/toggle-status', [], $this->authHeader());
        self::assertSame(ResultCode::SUCCESS->value, $result['code']);

        $this->forAddPermission('coupon:delete');
        $result = $this->delete('/admin/coupon/' . $couponId, [], $this->authHeader());
        self::assertSame(ResultCode::SUCCESS->value, $result['code']);
    }

    public function testCouponIssue(): void
    {
        $coupon = Coupon::create([
            'name' => '发放测试' . Str::random(4),
            'type' => 'fixed',
            'value' => 5,
            'min_amount' => 50,
            'total_quantity' => 10,
            'used_quantity' => 0,
            'per_user_limit' => 1,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->addDays(5),
            'status' => 'active',
        ]);

        $member = Member::create([
            'openid' => Str::uuid()->toString(),
            'nickname' => '测试会员',
            'gender' => 'unknown',
            'level' => 'normal',
            'growth_value' => 0,
            'total_orders' => 0,
            'total_amount' => 0,
            'status' => 'enabled',
            'source' => 'test',
        ]);

        $this->forAddPermission('coupon:issue');
        $result = $this->post('/admin/coupon/' . $coupon->id . '/issue', [
            'member_ids' => [$member->id],
        ], $this->authHeader());
        self::assertSame(ResultCode::SUCCESS->value, $result['code']);
        self::assertSame(1, CouponUser::count());
    }
}
