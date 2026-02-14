<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Domain\Member\Repository\MemberGrowthLogRepository;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Service\DomainMemberGrowthService;
use App\Domain\Member\Service\DomainMemberLevelService;
use App\Infrastructure\Model\Member\Member;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 4: 成长值变动日志完整性
 *
 * Validates: Requirements 2.5
 *
 * For any growth value change operation (add or deduct), the system must create a log record
 * in member_growth_logs where after_value == before_value + change_amount, and the member's
 * growth_value is updated to match after_value.
 */
class DomainMemberGrowthServiceLogIntegrityTest extends TestCase
{
    use TestTrait;

    private function makeMemberMock(int $id, int $growthValue): Member
    {
        $member = $this->getMockBuilder(Member::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $attrs = [
            'id' => $id,
            'growth_value' => $growthValue,
            'level_id' => 1,
            'level' => 'VIP1',
        ];

        $member->method('getAttribute')
            ->willReturnCallback(fn (string $key) => $attrs[$key] ?? null);

        return $member;
    }

    /**
     * Build the service with mocks that capture log and update data.
     *
     * @return array{service: DomainMemberGrowthService, getLog: callable, getUpdate: callable}
     */
    private function buildServiceWithCapture(int $memberId, int $initialGrowth): array
    {
        $capturedLog = null;
        $capturedUpdate = null;

        $memberRepository = $this->createMock(MemberRepository::class);
        $growthLogRepository = $this->createMock(MemberGrowthLogRepository::class);
        $levelService = $this->createMock(DomainMemberLevelService::class);

        $memberRepository->method('findById')
            ->willReturn($this->makeMemberMock($memberId, $initialGrowth));

        $memberRepository->method('updateById')
            ->willReturnCallback(function (int $id, array $data) use (&$capturedUpdate): bool {
                $capturedUpdate = $data;
                return true;
            });

        $growthLogRepository->method('create')
            ->willReturnCallback(function (array $data) use (&$capturedLog): mixed {
                $capturedLog = $data;
                return null;
            });

        $service = new DomainMemberGrowthService(
            $memberRepository,
            $growthLogRepository,
            $levelService,
        );

        return [
            'service' => $service,
            'getLog' => static function () use (&$capturedLog) { return $capturedLog; },
            'getUpdate' => static function () use (&$capturedUpdate) { return $capturedUpdate; },
        ];
    }

    /**
     * Property 4 (addGrowthValue): For any positive amount added to any non-negative
     * initial growth value, the log record must satisfy:
     *   after_value == before_value + change_amount
     * and the member update must set growth_value == after_value.
     */
    public function testAddGrowthValueLogIntegrity(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(0, 100000),  // initial growth value
            Generators::choose(1, 50000),   // amount to add (positive)
        )->then(function (int $initialGrowth, int $amount) {
            $memberId = 1;
            $ctx = $this->buildServiceWithCapture($memberId, $initialGrowth);

            $ctx['service']->addGrowthValue($memberId, $amount, 'order_payment', 'test');

            $log = ($ctx['getLog'])();
            $update = ($ctx['getUpdate'])();

            // Invariant 1: after_value == before_value + change_amount
            $this->assertNotNull($log, 'Growth log must be created');
            $this->assertSame(
                $log['before_value'] + $log['change_amount'],
                $log['after_value'],
                sprintf(
                    'Log integrity violated: before=%d + change=%d != after=%d',
                    $log['before_value'],
                    $log['change_amount'],
                    $log['after_value'],
                ),
            );

            // Invariant 2: member's growth_value updated to after_value
            $this->assertNotNull($update, 'Member must be updated');
            $this->assertSame(
                $log['after_value'],
                $update['growth_value'],
                sprintf(
                    'Member growth_value (%d) does not match log after_value (%d)',
                    $update['growth_value'],
                    $log['after_value'],
                ),
            );
        });
    }

    /**
     * Property 4 (deductGrowthValue): For any positive deduction amount and any
     * positive initial growth value, the log record must satisfy:
     *   after_value == before_value + change_amount (change_amount is negative)
     * and the member update must set growth_value == after_value.
     * Growth value floors at 0.
     */
    public function testDeductGrowthValueLogIntegrity(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 100000),  // initial growth value (positive so deduction produces a log)
            Generators::choose(1, 50000),   // amount to deduct (positive)
        )->then(function (int $initialGrowth, int $amount) {
            $memberId = 1;
            $ctx = $this->buildServiceWithCapture($memberId, $initialGrowth);

            $ctx['service']->deductGrowthValue($memberId, $amount, 'order_refund', 'test');

            $log = ($ctx['getLog'])();
            $update = ($ctx['getUpdate'])();

            // Invariant 1: after_value == before_value + change_amount
            $this->assertNotNull($log, 'Growth log must be created for deduction');
            $this->assertSame(
                $log['before_value'] + $log['change_amount'],
                $log['after_value'],
                sprintf(
                    'Log integrity violated: before=%d + change=%d != after=%d',
                    $log['before_value'],
                    $log['change_amount'],
                    $log['after_value'],
                ),
            );

            // Invariant 2: member's growth_value updated to after_value
            $this->assertNotNull($update, 'Member must be updated');
            $this->assertSame(
                $log['after_value'],
                $update['growth_value'],
                sprintf(
                    'Member growth_value (%d) does not match log after_value (%d)',
                    $update['growth_value'],
                    $log['after_value'],
                ),
            );

            // Additional: after_value must be >= 0 (floor at zero)
            $this->assertGreaterThanOrEqual(
                0,
                $log['after_value'],
                'Growth value must never go below zero',
            );
        });
    }
}
