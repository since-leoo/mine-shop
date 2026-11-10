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

namespace HyperfTests\Unit\Distribution;

use PHPUnit\Framework\TestCase;
use Plugin\Diy\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;

/**
 * @internal
 * @coversNothing
 */
final class DiyDistributionSchemaTest extends TestCase
{
    public function testPublishedComponentKeepsPresentationChoicesWithoutSnapshottingMemberData(): void
    {
        $props = ['title' => '我的分享', 'showInvite' => false, 'showTeam' => true, 'showOrders' => true, 'showCommission' => false];
        $schema = DiyPageSchemaVo::fromArray($this->schema('distribution-center', $props), 'user')->publishedPayload();
        self::assertSame($props, $schema['components'][0]['props']);
        self::assertSame([], $schema['components'][0]['data']);
    }

    public function testUntrustedSchemaCannotOverrideDistributionRuntimeEligibility(): void
    {
        $this->expectException(\DomainException::class);
        DiyPageSchemaVo::fromArray($this->schema('user-profile-header', ['showInviteEntry' => true]), 'user');
    }

    public function testPrivateCommissionStatisticsCannotBePublishedToAllVisitors(): void
    {
        $this->expectException(\DomainException::class);
        DiyPageSchemaVo::fromArray($this->schema('distribution-center', ['title' => '分享'], ['available_amount' => 12345]), 'user');
    }

    public function testTextFalseCannotAccidentallyEnableAnInvitationEntry(): void
    {
        $this->expectException(\DomainException::class);
        DiyPageSchemaVo::fromArray($this->schema('distribution-center', ['showInvite' => 'false']), 'user');
    }

    private function schema(string $type, array $props, array $data = []): array
    {
        return ['version' => 1, 'page' => ['key' => 'user'], 'components' => [[
            'id' => 'distribution-entry', 'type' => $type, 'name' => '分销中心',
            'enabled' => true, 'props' => $props, 'data' => $data, 'style' => [],
        ]]];
    }
}
