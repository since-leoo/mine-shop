<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Distribution;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\OrderSetting;
use Hyperf\Database\Schema\Blueprint;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\Distribution\Application\Admin\AppDistributionCommandService;
use Plugin\Distribution\Application\Api\AppApiDistributionCommandService;
use Plugin\Distribution\Application\Api\AppApiDistributionQueryService;
use Plugin\Distribution\Application\AppDistributionTradeService;
use Plugin\Distribution\Domain\Contract\ShareLinkGatewayInterface;
use Plugin\Distribution\Domain\Repository\DistributionQueryRepository;
use Plugin\Distribution\Domain\Repository\DistributionRepository;
use Plugin\Distribution\Domain\Service\DomainCommissionService;
use Plugin\Distribution\Domain\Service\DomainDistributionQueryService;
use Plugin\Distribution\Domain\Service\DomainDistributionService;
use Plugin\Distribution\Domain\Service\DomainShareService;
use Plugin\Distribution\Domain\Service\DomainWithdrawalService;
use Plugin\Distribution\Interface\Dto\DistributionSettingsDto;
use Plugin\Distribution\Interface\Dto\ShareDto;
use Plugin\Distribution\Interface\Dto\WithdrawalDto;
use Plugin\Distribution\Interface\Dto\WithdrawalReviewDto;

/** Real persistence and transaction tests; fixtures live only in DatabaseTestCase's disposable database. */
final class DistributionWorkflowTest extends DatabaseTestCase
{
    private const MIGRATION = 'plugins/distribution/Database/Migrations/2026_10_16_000001_create_distribution_tables.php';

    private DistributionRepository $repository;
    private AppApiDistributionCommandService $commands;
    private AppApiDistributionQueryService $queries;
    private AppDistributionCommandService $admin;
    private AppDistributionTradeService $trade;
    private array $linkCalls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTradeFixtures();
        $this->migrate(self::MIGRATION);
        $this->repository = new DistributionRepository();
        $distribution = new DomainDistributionService($this->repository);
        $withdrawals = new DomainWithdrawalService($this->repository);
        $links = $this->createMock(ShareLinkGatewayInterface::class);
        $links->method('generate')->willReturnCallback(function (string $channel, string $page, string $query): array {
            $this->linkCalls[] = compact('channel', 'page', 'query');
            return ['url' => 'https://wxaurl.cn/test-link'];
        });
        $this->commands = new AppApiDistributionCommandService($distribution, $withdrawals, new DomainShareService($this->repository, $links));
        $this->queries = new AppApiDistributionQueryService($distribution, new DomainDistributionQueryService(new DistributionQueryRepository()));
        $this->admin = new AppDistributionCommandService($distribution, $withdrawals);
        $mall = $this->createMock(DomainMallSettingService::class);
        $mall->method('order')->willReturn(new OrderSetting(30, 7, 0, false, 'system', ''));
        $this->trade = new AppDistributionTradeService(new DomainCommissionService($this->repository, $mall));
        $this->settings();
    }

    public function testCompletionEventStoresItsFirstTimestampOnlyInPluginTable(): void
    {
        [$parent, $buyer] = $this->family();
        [$order] = $this->commissionOrder($buyer, 10000);
        $this->trade->paid($order);
        $this->connection->table('orders')->where('id', $order)->update(['status' => 'completed']);
        self::assertFalse($this->connection->getSchemaBuilder()->hasColumn('orders', 'completed_at'));
        $this->trade->recordCompletion($order, '2026-01-01 12:00:00');
        $this->trade->recordCompletion($order, '2026-01-02 12:00:00');
        self::assertSame('2026-01-01 12:00:00', $this->connection->table('distribution_order_snapshots')->where('order_id', $order)->value('completed_at'));
        self::assertSame(1000, $this->balance($parent, 'available_amount'));
        self::assertSame(1, $this->connection->table('distribution_ledger')->where('type', 'settlement')->count());
    }

    public function testMigrationPreservesLegacyRelationshipsAndDocumentsEveryColumn(): void
    {
        $migration = require BASE_PATH . '/' . self::MIGRATION;
        $migration->down();
        $root = $this->member();
        $child = $this->member($root);
        $grandchild = $this->member($child);
        for ($i = 0; $i < 3; ++$i) { $this->member($root); }
        $migration->up();
        self::assertSame(4, $this->repository->directCount($root));
        self::assertSame(2, (int) $this->connection->table('distribution_paths')->where('ancestor_id', $root)->where('descendant_id', $grandchild)->value('depth'));
        self::assertSame(5, $this->connection->table('distribution_paths')->where('ancestor_id', $root)->count());
        $columns = $this->connection->select("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME LIKE 'distribution_%'", [$this->connection->getDatabaseName()]);
        self::assertGreaterThan(60, count($columns));
        foreach ($columns as $column) {
            self::assertNotSame('', $column->COLUMN_COMMENT, $column->TABLE_NAME . '.' . $column->COLUMN_NAME);
        }
        self::assertSame(0, $this->connection->table('distribution_commissions')->count(), 'Installing must not backfill old sales commissions.');
    }

    public function testBindingsAreLimitedImmutableAndPreservePlatformAndDisabledOutcomes(): void
    {
        $root = $this->member();
        $code = $this->code($root);
        $children = [];
        for ($i = 0; $i < 3; ++$i) {
            $children[] = $id = $this->member();
            self::assertSame('bound', $this->commands->bindNewMember($id, $code, null)['reason']);
        }
        $overflow = $this->member();
        self::assertSame('full', $this->commands->bindNewMember($overflow, $code, null)['reason']);
        self::assertNull($this->repository->member($overflow)['referrer_id']);
        self::assertFalse($this->queries->status($root)['can_invite']);
        $grandchild = $this->member();
        self::assertSame('bound', $this->commands->bindNewMember($grandchild, $this->code($children[0]), null)['reason']);
        self::assertSame(2, (int) $this->connection->table('distribution_paths')->where('ancestor_id', $root)->where('descendant_id', $grandchild)->value('depth'));
        $newParent = $this->member();
        self::assertSame($root, (int) $this->commands->bindNewMember($children[0], $this->code($newParent), null)['referrer_id']);
        self::assertSame('full', $this->commands->bindNewMember($overflow, $this->code($newParent), null)['reason']);
        $platform = $this->member();
        self::assertSame('platform', $this->commands->bindNewMember($platform, null, null)['reason']);
        self::assertNull($this->commands->bindNewMember($platform, $this->code($newParent), null)['referrer_id']);
        $self = $this->member();
        self::assertSame('invalid', $this->commands->bindNewMember($self, $this->code($self), null)['reason']);
        $legacy = $this->member($newParent);
        self::assertSame('existing', $this->commands->bindNewMember($legacy, $code, null)['reason']);
        $this->settings(['enabled' => false]);
        self::assertSame('disabled', $this->commands->bindNewMember($this->member(), $this->code($newParent), null)['reason']);
        self::assertSame(3, $this->repository->directCount($root));
    }

    public function testFourConcurrentRegistrationsCannotExceedThreeSlots(): void
    {
        $parent = $this->member();
        $directory = sys_get_temp_dir() . '/mine-shop-distribution-race-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $workers = [];
        $this->connection->beginTransaction();
        $this->repository->member($parent, true);
        // The parent lock is held before workers read their repeatable-read snapshots.
        $this->connection->rollBack();
        $members = array_map(fn () => $this->member(), range(1, 4));
        $this->connection->beginTransaction();
        $this->repository->member($parent, true);
        try {
            foreach ($members as $id) {
                $process = proc_open([PHP_BINARY, __DIR__ . '/binding-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName(), (string) $id, $this->code($parent), $directory . '/' . $id], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
                self::assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 10;
            do {
                $ready = count(glob($directory . '/*') ?: []);
                if ($ready === 4) { break; }
                usleep(10000);
            } while (microtime(true) < $deadline);
            self::assertSame(4, $ready, 'All workers must read their initial count before releasing the parent lock.');
            $this->connection->commit();
            $reasons = [];
            foreach ($workers as [$process, $pipes]) {
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]); fclose($pipes[2]);
                self::assertSame(0, proc_close($process), $error . $output);
                $reasons[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['reason'];
            }
            $workers = [];
            sort($reasons);
            self::assertSame(['bound', 'bound', 'bound', 'full'], $reasons);
            self::assertSame(3, $this->repository->directCount($parent));
            self::assertSame(4, $this->connection->table('distribution_bindings')->count());
        } finally {
            if ($this->connection->transactionLevel() > 0) { $this->connection->rollBack(); }
            foreach ($workers as [$process, $pipes]) {
                if (is_resource($process)) { proc_terminate($process); }
                foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
                if (is_resource($process)) { proc_close($process); }
            }
            foreach (glob($directory . '/*') ?: [] as $file) { unlink($file); }
            rmdir($directory);
        }
    }

    public function testShareIdentityComesFromCurrentMemberAndKeepsProductActivityRouting(): void
    {
        $a = $this->member(); $b = $this->member();
        $home = $this->commands->share($a, new ShareDto(['target' => 'home', 'channel' => 'card']));
        self::assertStringStartsWith('/pages/home/index?', $home['path']);
        $product = $this->commands->share($b, new ShareDto(['target' => 'product', 'channel' => 'short_link', 'spu_id' => 101, 'sku_id' => 201, 'activity_id' => 42, 'order_type' => 'group_buy', 'session_id' => 43, 'group_no' => 'TEAM&A', 'group_buy_id' => 44]));
        parse_str(parse_url($product['path'], PHP_URL_QUERY), $query);
        self::assertSame('101', $query['spuId']);
        self::assertSame('group_buy', $query['orderType']);
        self::assertSame('TEAM&A', $query['groupNo']);
        self::assertSame('43', $query['sessionId']);
        self::assertSame('42', $query['activityId']);
        self::assertSame('44', $query['groupBuyId']);
        self::assertSame($product['share_token'], $query['share_token']);
        self::assertSame($b, (int) $this->repository->share($product['share_token'])['member_id']);
        self::assertSame($a, $this->queries->resolve(null, $home['share_token'])['referrer_id']);
        self::assertSame('pages/goods/details/index', $this->linkCalls[0]['page']);
        self::assertSame(parse_url($product['path'], PHP_URL_QUERY), $this->linkCalls[0]['query']);
        self::assertSame('https://shop.test/#' . $product['path'], $product['h5_url']);
        $this->settings(['enabled' => false]);
        $ordinary = $this->commands->share($a, new ShareDto(['target' => 'home', 'channel' => 'short_link']));
        self::assertSame('', $ordinary['share_token']);
        self::assertSame('/pages/home/index', $ordinary['path']);
        self::assertCount(1, $this->linkCalls);
    }

    public function testTeamStatisticsExcludeSelfDeduplicateAndRejectUnrelatedMembers(): void
    {
        [$root, $child] = $this->family();
        $grandchild = $this->member();
        $this->commands->bindNewMember($grandchild, $this->code($child), null);
        $outsider = $this->member();
        [$order, $item] = $this->order($child, 11000, 1000);
        $this->order($grandchild, 6000);
        $this->order($root, 50000);
        $this->order($outsider, 90000);
        $this->refund($order, $item, 2000);
        $stats = $this->queries->overview($root, []);
        self::assertSame(1, $stats['direct_count']);
        self::assertSame(2, $stats['team_count']);
        self::assertSame(2, $stats['paid_order_count']);
        self::assertSame(15000, $stats['net_amount']);
        $orders = $this->queries->orders($root, []);
        self::assertSame(2, $orders['total']);
        foreach ($orders['list'] as $entry) {
            self::assertNotSame($root, (int) $entry['member_id']);
            self::assertNotSame($outsider, (int) $entry['member_id']);
            self::assertArrayNotHasKey('address', $entry);
            self::assertArrayNotHasKey('phone', $entry);
        }
        self::assertSame(1, $this->queries->orders($root, ['member_id' => $child, 'scope' => 'self'])['total']);
        self::assertSame(1, $this->queries->team($root, ['scope' => 'direct'])['total']);
        foreach ([['member_id' => $outsider], ['member_id' => 0], ['member_id' => -1]] as $filter) {
            $this->expectDomainFailure(fn () => $this->queries->team($root, $filter));
            $this->expectDomainFailure(fn () => $this->queries->orders($root, $filter));
        }
        $platformStats = (new DistributionQueryRepository())->overview(null);
        self::assertSame(17000, $platformStats['paid_amount'], 'Platform sales must not sum overlapping ancestor totals.');
    }

    public function testSnapshotPaymentRefundSettlementWithdrawalAndDebtRemainConsistentAfterDisabling(): void
    {
        [$parent, $buyer] = $this->family();
        [$order, $item, $snapshot] = $this->commissionOrder($buyer, 11000, 1000);
        self::assertSame(1000, $snapshot['lines'][0]['amount']);
        $this->settings(['enabled' => false, 'commission_rate_bps' => 9000]);
        $this->trade->paid($order); $this->trade->paid($order);
        self::assertSame(1000, $this->balance($parent, 'pending_amount'));
        self::assertSame(1, $this->connection->table('distribution_commissions')->count());
        $this->refund($order, $item, 2000);
        $this->trade->refunded($order); $this->trade->refunded($order);
        self::assertSame(800, $this->balance($parent, 'pending_amount'));
        $this->complete($order);
        $this->trade->settleDue(); $this->trade->completed($order);
        self::assertSame(0, $this->balance($parent, 'pending_amount'));
        self::assertSame(800, $this->balance($parent, 'available_amount'));
        $input = new WithdrawalDto(['amount' => 800, 'account_name' => '测试收款人', 'account_no' => 'account-001', 'request_key' => 'withdraw-once']);
        $withdrawal = $this->commands->withdraw($parent, $input);
        self::assertSame($withdrawal['id'], $this->commands->withdraw($parent, $input)['id']);
        self::assertSame(800, $this->balance($parent, 'frozen_amount'));
        $this->review((int) $withdrawal['id'], 'approve');
        $this->review((int) $withdrawal['id'], 'pay', 'PAYMENT-001');
        $this->review((int) $withdrawal['id'], 'pay', 'PAYMENT-001');
        self::assertSame(800, $this->balance($parent, 'withdrawn_amount'));
        self::assertSame(0, $this->balance($parent, 'frozen_amount'));
        $this->refund($order, $item, 9000);
        $this->trade->refunded($order); $this->trade->refunded($order);
        self::assertSame(800, $this->balance($parent, 'debt_amount'));
        self::assertSame([], $this->trade->snapshot($buyer, $this->payload(10000)));
        $this->settings();
        [$next] = $this->commissionOrder($buyer, 10000);
        $this->trade->paid($next); $this->complete($next); $this->trade->completed($next);
        self::assertSame(0, $this->balance($parent, 'debt_amount'));
        self::assertSame(200, $this->balance($parent, 'available_amount'));
        self::assertSame(8, $this->connection->table('distribution_ledger')->count());
    }

    public function testRefundDuringWithdrawalBlocksPayoutAndRejectionOffsetsDebt(): void
    {
        [$parent, $buyer] = $this->family();
        [$order, $item] = $this->commissionOrder($buyer, 10000);
        $this->trade->paid($order); $this->complete($order); $this->trade->completed($order);
        $withdrawal = $this->commands->withdraw($parent, new WithdrawalDto(['amount' => 1000, 'account_name' => '测试', 'account_no' => 'bank-001', 'request_key' => 'pending-withdraw']));
        $id = (int) $withdrawal['id'];
        $this->review($id, 'approve');
        $this->refund($order, $item, 5000); $this->trade->refunded($order);
        $this->expectDomainFailure(fn () => $this->review($id, 'pay', 'PAYMENT-BLOCKED'));
        self::assertSame('approved', $this->repository->withdrawal($id)['status']);
        self::assertSame(500, $this->balance($parent, 'debt_amount'));
        $this->review($id, 'reject', '', '退款取消付款');
        self::assertSame(500, $this->balance($parent, 'available_amount'));
        self::assertSame(0, $this->balance($parent, 'debt_amount'));
        self::assertSame(0, $this->balance($parent, 'frozen_amount'));
    }

    public function testOpenAfterSaleAndSettlementWindowPreventPrematureSettlement(): void
    {
        $this->settings(['settlement_days' => 7]);
        [$parent, $buyer] = $this->family();
        [$order, $item] = $this->commissionOrder($buyer, 10000);
        $this->trade->paid($order); $this->complete($order);
        $this->trade->completed($order);
        self::assertSame(1000, $this->balance($parent, 'pending_amount'));
        $this->complete($order, 8);
        $afterSale = $this->connection->table('order_trade_after_sale')->insertGetId(['order_id' => $order, 'order_item_id' => $item, 'status' => 'pending']);
        $this->trade->settleDue();
        self::assertSame(1000, $this->balance($parent, 'pending_amount'));
        $this->connection->table('order_trade_after_sale')->where('id', $afterSale)->update(['status' => 'closed']);
        $this->trade->settleDue();
        self::assertSame(1000, $this->balance($parent, 'available_amount'));
    }

    public function testFirstOrderQualificationIsNotReleasedAfterFullRefund(): void
    {
        $this->settings(['commission_mode' => 'first_order']);
        [$parent, $buyer] = $this->family();
        [$first, $item] = $this->commissionOrder($buyer, 10000);
        $this->trade->paid($first);
        $this->refund($first, $item, 10000); $this->trade->refunded($first);
        [$second] = $this->commissionOrder($buyer, 10000);
        $this->trade->paid($second);
        self::assertSame(1, $this->connection->table('distribution_commissions')->count());
        self::assertSame($first, (int) $this->connection->table('distribution_first_orders')->where('buyer_id', $buyer)->value('order_id'));
        self::assertSame(0, $this->balance($parent, 'pending_amount'));
    }

    public function testOutOfOrderRefundNotificationIsAppliedWhenPaymentEventuallyArrives(): void
    {
        [$parent, $buyer] = $this->family();
        [$order, $item] = $this->commissionOrder($buyer, 10000);
        $this->refund($order, $item, 10000);
        $this->trade->refunded($order);
        $this->trade->paid($order); $this->trade->paid($order);
        self::assertSame(0, $this->balance($parent, 'pending_amount'));
        self::assertSame('cancelled', $this->connection->table('distribution_commissions')->where('order_id', $order)->value('status'));
        self::assertSame(2, $this->connection->table('distribution_ledger')->count());
    }

    public function testFirstOrderUsesPaymentTimeThenIdEvenWhenLaterNotificationArrivesFirst(): void
    {
        $this->settings(['commission_mode' => 'first_order']);
        foreach ([false, true] as $samePaymentTime) {
            [$parent, $buyer] = $this->family();
            [$first] = $this->commissionOrder($buyer, 10000);
            [$second] = $this->commissionOrder($buyer, 20000);
            $this->connection->table('orders')->where('id', $first)->update(['pay_time' => date('Y-m-d H:i:s', time() - 60)]);
            $this->connection->table('orders')->where('id', $second)->update(['pay_time' => date('Y-m-d H:i:s', time() - ($samePaymentTime ? 60 : 30))]);
            $this->trade->paid($second);
            self::assertSame(0, $this->balance($parent, 'pending_amount'));
            $this->trade->paid($first); $this->trade->paid($second);
            self::assertSame(1000, $this->balance($parent, 'pending_amount'));
            self::assertSame($first, (int) $this->connection->table('distribution_first_orders')->where('buyer_id', $buyer)->value('order_id'));
            self::assertSame(1, $this->connection->table('distribution_commissions')->where('member_id', $parent)->count());
        }
    }

    public function testOneHundredOrdersWithOpenAfterSalesCannotStarveNextEligibleSettlement(): void
    {
        [$parent, $buyer] = $this->family();
        for ($i = 0; $i < 101; ++$i) {
            [$order, $item] = $this->commissionOrder($buyer, 1000);
            $this->trade->paid($order); $this->complete($order);
            if ($i < 100) {
                $this->connection->table('order_trade_after_sale')->insert(['order_id' => $order, 'order_item_id' => $item, 'status' => 'pending']);
            }
        }
        self::assertSame([], $this->trade->settleDue());
        self::assertSame('settled', $this->connection->table('distribution_commissions')->where('order_id', $order)->value('status'));
        self::assertSame(100, $this->balance($parent, 'available_amount'));
        self::assertSame(10000, $this->balance($parent, 'pending_amount'));
    }

    public function testConcurrentWithdrawalsAreIdempotentAndAllowOnlyOneOpenApplication(): void
    {
        foreach ([['same-key', 'same-key'], ['different-key-1', 'different-key-2']] as $keys) {
            [$parent, $buyer] = $this->family();
            [$order] = $this->commissionOrder($buyer, 10000);
            $this->trade->paid($order); $this->complete($order); $this->trade->completed($order);
            $results = $this->concurrentWithdrawals($parent, $keys);
            $accepted = array_values(array_filter($results, static fn (array $result) => $result['accepted']));
            self::assertCount($keys[0] === $keys[1] ? 2 : 1, $accepted);
            self::assertSame(1, count(array_unique(array_column($accepted, 'id'))));
            self::assertSame(1, $this->connection->table('distribution_withdrawals')->where('member_id', $parent)->count());
            self::assertSame(500, $this->balance($parent, 'available_amount'));
            self::assertSame(500, $this->balance($parent, 'frozen_amount'));
            self::assertSame(1, $this->connection->table('distribution_ledger')->where('member_id', $parent)->where('type', 'withdrawal_freeze')->count());
        }
    }

    private function concurrentWithdrawals(int $member, array $keys): array
    {
        $directory = sys_get_temp_dir() . '/mine-shop-withdrawal-race-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $workers = [];
        $this->connection->beginTransaction();
        $this->repository->member($member, true);
        try {
            foreach ($keys as $index => $key) {
                $process = proc_open([PHP_BINARY, __DIR__ . '/withdrawal-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName(), (string) $member, $key, $directory . '/' . $index], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
                self::assertIsResource($process);
                fclose($pipes[0]); $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 10;
            do {
                $ready = count(glob($directory . '/*') ?: []);
                if ($ready === count($keys)) { break; }
                usleep(10000);
            } while (microtime(true) < $deadline);
            self::assertSame(count($keys), $ready);
            $this->connection->commit();
            $results = [];
            foreach ($workers as [$process, $pipes]) {
                $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]); fclose($pipes[2]);
                self::assertSame(0, proc_close($process), $error . $output);
                $results[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            }
            $workers = [];
            return $results;
        } finally {
            if ($this->connection->transactionLevel() > 0) { $this->connection->rollBack(); }
            foreach ($workers as [$process, $pipes]) {
                if (is_resource($process)) { proc_terminate($process); }
                foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
                if (is_resource($process)) { proc_close($process); }
            }
            foreach (glob($directory . '/*') ?: [] as $file) { unlink($file); }
            rmdir($directory);
        }
    }

    private function settings(array $overrides = []): void
    {
        $this->admin->settings(new DistributionSettingsDto(array_replace(['enabled' => true, 'commission_rate_bps' => 1000, 'settlement_days' => 0, 'h5_base_url' => 'https://shop.test'], $overrides)), 1);
    }

    private function member(?int $parent = null): int
    {
        return (int) $this->connection->table('members')->insertGetId(['referrer_id' => $parent, 'invite_code' => strtoupper(bin2hex(random_bytes(4))), 'nickname' => '测试粉丝', 'avatar' => '', 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')]);
    }

    private function code(int $id): string { return (string) $this->repository->member($id)['invite_code']; }
    private function family(): array
    {
        $parent = $this->member(); $child = $this->member();
        $this->commands->bindNewMember($child, $this->code($parent), null);
        return [$parent, $child];
    }
    private function payload(int $paid, int $shipping = 0): array
    {
        return ['snapshot' => ['pay_amount' => $paid, 'shipping_fee' => $shipping, 'order_type' => 'normal'], 'items' => [['product_id' => 101, 'sku_id' => 201, 'quantity' => 1, 'total_price' => $paid - $shipping]]];
    }
    private function order(int $buyer, int $paid, int $shipping = 0): array
    {
        $id = (int) $this->connection->table('orders')->insertGetId(['order_no' => 'ORDER-' . bin2hex(random_bytes(8)), 'member_id' => $buyer, 'status' => 'paid', 'pay_amount' => $paid, 'shipping_fee' => $shipping, 'pay_time' => date('Y-m-d H:i:s'), 'address' => '不应向推广员展示的地址']);
        $item = (int) $this->connection->table('order_items')->insertGetId(['order_id' => $id, 'product_id' => 101, 'sku_id' => 201, 'quantity' => 1, 'total_price' => $paid - $shipping, 'product_name' => '测试商品']);
        return [$id, $item];
    }
    private function commissionOrder(int $buyer, int $paid, int $shipping = 0): array
    {
        $snapshot = $this->trade->snapshot($buyer, $this->payload($paid, $shipping));
        [$order, $item] = $this->order($buyer, $paid, $shipping);
        $this->trade->created($this->repository->order($order)['order_no'], $snapshot);
        return [$order, $item, $snapshot];
    }
    private function refund(int $order, int $item, int $amount): void
    {
        $sale = $this->connection->table('order_trade_after_sale')->insertGetId(['order_id' => $order, 'order_item_id' => $item, 'status' => 'completed']);
        $this->connection->table('payment_refunds')->insert(['order_id' => $order, 'refund_amount' => $amount, 'status' => 'success', 'extra_data' => json_encode(['after_sale_id' => $sale], JSON_THROW_ON_ERROR)]);
    }
    private function complete(int $order, int $daysAgo = 0): void
    {
        $this->connection->table('orders')->where('id', $order)->update(['status' => 'completed']);
        $this->connection->table('distribution_order_snapshots')->where('order_id', $order)->update(['completed_at' => date('Y-m-d H:i:s', time() - $daysAgo * 86400)]);
    }
    private function balance(int $member, string $field): int { return (int) $this->repository->account($member)[$field]; }
    private function review(int $id, string $action, string $payment = '', string $remark = ''): array
    {
        return $this->admin->review($id, new WithdrawalReviewDto(['action' => $action, 'payment_no' => $payment, 'remark' => $remark]), 1);
    }
    private function expectDomainFailure(callable $action): void
    {
        try { $action(); self::fail('Expected a domain boundary rejection.'); }
        catch (\DomainException $exception) { self::assertNotSame('', $exception->getMessage()); }
    }
    private function createTradeFixtures(): void
    {
        $schema = $this->connection->getSchemaBuilder();
        $schema->create('members', static function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('referrer_id')->nullable()->index(); $table->string('invite_code')->unique();
            $table->string('nickname'); $table->string('avatar'); $table->string('status'); $table->timestamp('created_at');
        });
        $schema->create('orders', static function (Blueprint $table): void {
            $table->id(); $table->string('order_no')->unique(); $table->unsignedBigInteger('member_id')->index(); $table->string('status');
            $table->unsignedBigInteger('pay_amount'); $table->unsignedBigInteger('shipping_fee'); $table->timestamp('pay_time')->nullable();
            $table->string('address');
        });
        $schema->create('order_items', static function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('order_id'); $table->unsignedBigInteger('product_id'); $table->unsignedBigInteger('sku_id');
            $table->unsignedInteger('quantity'); $table->unsignedBigInteger('total_price'); $table->string('product_name');
        });
        $schema->create('payment_refunds', static function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('order_id'); $table->string('status'); $table->unsignedBigInteger('refund_amount'); $table->json('extra_data');
        });
        $schema->create('order_trade_after_sale', static function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('order_id'); $table->unsignedBigInteger('order_item_id'); $table->string('status');
        });
    }
}
