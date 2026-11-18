<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Trade;

use App\Application\Api\Coupon\Member\AppApiMemberCouponCommandService;
use App\Domain\Trade\Coupon\Api\Command\DomainApiCouponUserCommandService;
use App\Domain\Trade\Coupon\Repository\CouponRepository;
use App\Domain\Trade\Coupon\Repository\CouponUserRepository;
use App\Domain\Trade\Coupon\Service\DomainCouponService;
use App\Infrastructure\Model\Coupon\Coupon;
use App\Infrastructure\Model\Coupon\CouponUser;
use HyperfTests\Support\DatabaseTestCase;

final class CouponIssuanceTest extends DatabaseTestCase
{
    private CouponRepository $coupons;
    private AppApiMemberCouponCommandService $receive;

    protected function setUp(): void
    {
        parent::setUp();
        require_once BASE_PATH . '/migrations/2024_01_01_000015_create_mall_coupons_table.php';
        require_once BASE_PATH . '/migrations/2024_01_01_000016_create_mall_coupon_users_table.php';
        (new \CreateMallCouponsTable())->up();
        (new \CreateMallCouponUsersTable())->up();
        $this->coupons = new CouponRepository(new Coupon());
        $users = new CouponUserRepository(new CouponUser());
        $this->receive = new AppApiMemberCouponCommandService(new DomainApiCouponUserCommandService(new DomainCouponService($this->coupons, $users), $this->coupons, $users));
        require_once BASE_PATH . '/migrations/2024_01_01_000006_create_mall_members_table.php';
        (new \CreateMallMembersTable())->up();
        $this->connection->table('members')->insert([['id' => 7, 'openid' => 'coupon-seven'], ['id' => 8, 'openid' => 'coupon-eight']]);
    }

    public function testIssuedCountsUseReceiptsAndKeepCouponAndMemberIsolation(): void
    {
        $first = $this->seedCoupon();
        $second = $this->seedCoupon();
        $this->receive->receive(7, $first);
        $this->receive->receive(8, $first);
        $this->receive->receive(7, $second);
        $this->connection->table('coupon_users')->where('coupon_id', $first)->where('member_id', 7)->update(['status' => 'used']);
        $this->connection->table('coupon_users')->where('coupon_id', $first)->where('member_id', 8)->update(['status' => 'expired']);

        self::assertSame(2, $this->coupons->countIssued($first));
        self::assertSame(1, $this->coupons->countIssued($second));
        self::assertSame(0, $this->coupons->countIssued(999));
        self::assertSame(1, $this->coupons->countIssuedByMember($first, 7));
        self::assertSame(1, $this->coupons->countIssuedByMember($second, 7));
        self::assertSame(0, $this->coupons->countIssuedByMember($second, 8));
    }

    public function testReceivingHonorsPerMemberLimitAndExhaustedStock(): void
    {
        $coupon = $this->seedCoupon(2);
        $this->receive->receive(7, $coupon);
        try {
            $this->receive->receive(7, $coupon);
            self::fail('A member received the same coupon beyond its limit.');
        } catch (\DomainException $error) {
            self::assertSame('已达到领取上限', $error->getMessage());
        }
        $this->receive->receive(8, $coupon);
        try {
            $this->receive->receive(9, $coupon);
            self::fail('An exhausted coupon was issued.');
        } catch (\DomainException $error) {
            self::assertSame('优惠券库存不足', $error->getMessage());
        }
        self::assertSame(2, $this->connection->table('coupon_users')->where('coupon_id', $coupon)->count());
    }

    public function testApiReceiveAndAdminIssueCannotOversellTheLastCoupon(): void
    {
        $coupon = $this->seedCoupon(1);
        $results = $this->race($coupon, 7, 8);
        self::assertSame(1, array_sum(array_column($results, 'issued')));
        self::assertSame(1, $this->coupons->countIssued($coupon));
    }

    public function testApiReceiveAndAdminIssueShareThePerMemberLimit(): void
    {
        $coupon = $this->seedCoupon(5);
        $results = $this->race($coupon, 7, 7);
        self::assertSame(1, array_sum(array_column($results, 'issued')));
        self::assertSame(1, $this->coupons->countIssuedByMember($coupon, 7));
    }

    private function race(int $couponId, int $apiMemberId, int $adminMemberId): array
    {
        $barrier = tempnam(sys_get_temp_dir(), 'coupon-race-');
        unlink($barrier);
        $workers = [];
        try {
            foreach (['receive' => $apiMemberId, 'issue' => $adminMemberId] as $mode => $memberId) {
                $process = proc_open([PHP_BINARY, BASE_PATH . '/tests/Integration/Trade/coupon-issuance-worker.php',
                    $this->connection->getDatabaseName(), $mode, (string) $couponId, (string) $memberId, $barrier],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::assertIsResource($process);
                $workers[] = [$process, $pipes];
                self::assertSame("READY\n", fgets($pipes[1]));
            }
            touch($barrier);
            $results = [];
            foreach ($workers as [$process, $pipes]) {
                $result = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }
                self::assertSame(0, proc_close($process), $error . $result);
                $results[] = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            }
            $workers = [];
            return $results;
        } finally {
            foreach ($workers as [$process, $pipes]) {
                if (! is_resource($process)) {
                    continue;
                }
                proc_terminate($process);
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
                proc_close($process);
            }
            if (is_file($barrier)) {
                unlink($barrier);
            }
        }
    }

    private function seedCoupon(int $quantity = 5): int
    {
        return $this->connection->table('coupons')->insertGetId([
            'name' => 'receipt-count', 'type' => 'fixed', 'value' => 100,
            'total_quantity' => $quantity, 'per_user_limit' => 1,
            'start_time' => date('Y-m-d H:i:s', time() - 3600),
            'end_time' => date('Y-m-d H:i:s', time() + 86400),
            'status' => 'active',
        ]);
    }
}
