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

namespace HyperfTests\Feature\Api;

use App\Infrastructure\Model\Member\Member;
use Plugin\Wechat\Interfaces\MiniAppInterface;

/**
 * @internal
 * @coversNothing
 */
final class MemberMiniLoginTest extends ApiControllerCase
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

    public function testCreateAndLoginViaMiniProgram(): void
    {
        $miniApp = \Mockery::mock(MiniAppInterface::class);
        $miniApp->shouldReceive('performSilentLogin')
            ->once()
            ->with('code123', 'encrypted', 'iv123')
            ->andReturn([
                'openid' => 'openid-foo',
                'unionid' => null,
                'nickname' => '小程序用户',
                'avatar' => 'https://example.com/avatar.png',
                'gender' => 'male',
            ]);

        $this->mock(MiniAppInterface::class, $miniApp);

        $response = $this->post('/api/v1/login/miniApp', [
            'code' => 'code123',
            'encrypted_data' => 'encrypted',
            'iv' => 'iv123',
        ]);

        self::assertSame(200, $response['code'], json_encode($response, \JSON_UNESCAPED_UNICODE));
        $data = $response['data'];
        self::assertArrayHasKey('token', $data);
        self::assertArrayHasKey('refresh_token', $data);
        self::assertSame('小程序用户', $data['member']['nickname']);
        self::assertSame('mini_program', $data['member']['source']);

        self::assertDatabaseHas('members', [
            'openid' => 'openid-foo',
            'nickname' => '小程序用户',
            'source' => 'mini_program',
        ]);
    }
}
