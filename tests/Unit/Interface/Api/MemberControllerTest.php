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

namespace HyperfTests\Unit\Interface\Api;

use App\Application\Member\Contract\MemberQueryInterface;
use App\Interface\Api\Controller\V1\MemberController;
use App\Interface\Api\Support\CurrentMember;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \App\Interface\Api\Controller\V1\MemberController
 */
final class MemberControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testProfileReturnsMemberData(): void
    {
        $memberData = [
            'id' => 10,
            'nickname' => '接口会员',
            'source' => 'mini_program',
        ];

        $currentMember = \Mockery::mock(CurrentMember::class);
        $currentMember->shouldReceive('id')->once()->andReturn(10);

        $queryService = \Mockery::mock(MemberQueryInterface::class);
        $queryService->shouldReceive('detail')
            ->once()
            ->with(10)
            ->andReturn($memberData);

        $controller = new MemberController($queryService, $currentMember);

        $result = $controller->profile()->toArray();

        self::assertSame(200, $result['code']);
        self::assertSame('获取成功', $result['message']);
        self::assertSame($memberData, $result['data']['member']);
    }
}
