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

namespace HyperfTests\Feature\Admin\Member;

use App\Infrastructure\Model\Member\Member;
use App\Interface\Common\ResultCode;
use Hyperf\Collection\Arr;
use HyperfTests\Feature\Admin\ControllerCase;

/**
 * @internal
 * @coversNothing
 */
final class MemberControllerTest extends ControllerCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Member::truncate();
    }

    protected function tearDown(): void
    {
        Member::truncate();
        parent::tearDown();
    }

    public function testCreateMember(): void
    {
        $this->forAddPermission('member:member:create');

        $payload = [
            'nickname' => '测试会员',
            'phone' => '13800138000',
            'gender' => 'male',
            'level' => 'silver',
            'status' => 'active',
            'source' => 'admin',
            'remark' => '后端创建',
        ];

        $result = $this->post('/admin/member/member', $payload, $this->authHeader());

        self::assertSame(ResultCode::SUCCESS->value, Arr::get($result, 'code'));
        self::assertSame('会员已创建', Arr::get($result, 'message'));
        self::assertSame([], Arr::get($result, 'data'));
    }
}
