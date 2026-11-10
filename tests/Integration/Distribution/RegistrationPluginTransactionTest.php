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

namespace HyperfTests\Integration\Distribution;

use HyperfTests\Support\DatabaseTestCase;
use Plugin\Distribution\Domain\ValueObject\DistributionPolicyVo;
use SinceLeoo\Plugin\PluginManager;

/**
 * Separate child process proves production plugin scanning and actual framework AOP execution.
 * @internal
 * @coversNothing
 */
final class RegistrationPluginTransactionTest extends DatabaseTestCase
{
    private int $parentId;

    protected function setUp(): void
    {
        parent::setUp();
        require_once BASE_PATH . '/migrations/2024_01_01_000006_create_mall_members_table.php';
        (new \CreateMallMembersTable())->up();
        $this->migrate('migrations/2026_03_20_000003_add_password_to_members_table.php');
        require_once BASE_PATH . '/migrations/2026_02_21_000001_add_member_referral_fields.php';
        (new \AddMemberReferralFields())->up();
        $this->migrate('plugins/distribution/Database/Migrations/2026_10_16_000001_create_distribution_tables.php');
        $settings = array_replace(DistributionPolicyVo::defaults(), ['enabled' => true]);
        $this->connection->table('distribution_rule_versions')->insert(['settings' => json_encode($settings), 'operator_id' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        $this->parentId = $this->connection->table('members')->insertGetId(['invite_code' => 'PARENT01', 'openid' => 'victim-openid', 'status' => 'active', 'source' => 'mini_program']);
        $this->connection->table('distribution_shares')->insert(['token' => str_repeat('a', 24), 'member_id' => $this->parentId, 'channel' => 'link', 'target' => '{}', 'created_at' => date('Y-m-d H:i:s'), 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
    }

    public function testInstalledPluginInterceptsRealH5AndMiniRegistrationsAndPreservesThreeSlotLimit(): void
    {
        $result = $this->worker('enabled');
        self::assertTrue($result['plugin_autoloadable']);
        self::assertSame('bound', $result['h5']['referral_binding']['reason']);
        self::assertSame(['bound', 'bound', 'full'], array_column(array_column($result['mini'], 'referral_binding'), 'reason'));
        self::assertSame('existing_member', $result['existing']['referral_binding']['reason']);
        self::assertSame($result['mini'][0]['member']['id'], $result['existing']['member']['id']);
        self::assertNotSame($this->parentId, $result['mini'][0]['member']['id'], 'Manual openid must not select another member.');
        self::assertSame(5, $result['member_count']);
        self::assertCount(4, $result['bindings']);
        self::assertSame([null, str_repeat('a', 24), str_repeat('a', 24), str_repeat('a', 24)], array_column($result['bindings'], 'share_token'));
        self::assertCount(4, $result['events']);
        self::assertSame([$this->parentId, $this->parentId, $this->parentId, null], array_column($result['events'], 'referrer_id'));
        self::assertSame(3, $this->connection->table('members')->where('referrer_id', $this->parentId)->count());
    }

    public function testBindingFailureRollsBackHostMemberInsertAndPublishesNoPublicEvent(): void
    {
        $result = $this->worker('failure');
        self::assertSame('injected plugin binding failure', $result['error']);
        self::assertSame(1, $result['member_count']);
        self::assertSame([], $result['bindings']);
        self::assertSame([], $result['events']);
    }

    public function testDisabledPluginLeavesHostRegistrationIndependentOfDistribution(): void
    {
        $result = $this->worker('disabled');
        self::assertFalse($result['plugin_autoloadable']);
        self::assertArrayNotHasKey('referral_binding', $result['h5']);
        self::assertSame(2, $result['member_count']);
        self::assertSame([], $result['bindings']);
        self::assertCount(1, $result['events']);
        self::assertNull($result['events'][0]['referrer_id']);
    }

    public function testExistingPhoneMemberSettingPasswordDoesNotBecomeANewRegistration(): void
    {
        $id = $this->connection->table('members')->insertGetId(['phone' => '13800138000', 'referrer_id' => 77, 'status' => 'active', 'source' => 'mini_program']);
        $result = $this->worker('existing-h5');
        self::assertSame($id, $result['h5']['member']['id']);
        self::assertSame('existing_member', $result['h5']['referral_binding']['reason']);
        self::assertSame(2, $result['member_count']);
        self::assertSame([], $result['events']);
        self::assertSame([], $result['bindings']);
        self::assertSame(77, (int) $this->connection->table('members')->where('id', $id)->value('referrer_id'));
    }

    public function testRealPluginManagerForcedUninstallIsBlockedBeforeVendorAndKeepsIsolatedInstallLock(): void
    {
        $result = $this->worker('uninstall-guard');
        self::assertTrue($result['isolated_physical_plugin']);
        self::assertSame(PluginManager::class, $result['manager_class']);
        self::assertTrue($result['proxied_method'], 'The production manager must use the generated AOP proxy.');
        self::assertSame(\DomainException::class, $result['exception_class'] ?? null);
        self::assertStringContainsString('曾启用', $result['message'] ?? '');
        self::assertArrayNotHasKey('returned', $result, 'The vendor uninstall body must not swallow the guard exception.');
        self::assertTrue($result['lock_exists']);
        self::assertTrue($result['lock_unchanged']);
    }

    private function worker(string $mode): array
    {
        $command = [\PHP_BINARY, __DIR__ . '/registration-aop-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName(), $mode];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        if (! \is_resource($process)) {
            self::fail('Unable to launch AOP worker.');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr . $stdout);
        return json_decode(trim($stdout), true, 512, \JSON_THROW_ON_ERROR);
    }
}
