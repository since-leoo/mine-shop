<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Application\Api\Member\AppApiMemberWalletQueryService;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 14: 流水记录时间倒序
 *
 * Validates: Requirements 10.2
 *
 * For any transaction query API result list, each record's `created_at`
 * must be greater than or equal to the next record's `created_at`.
 */
class AppApiMemberWalletQueryServiceTimeOrderTest extends TestCase
{
    use TestTrait;

    private const WALLET_TYPES = ['balance', 'points'];

    /**
     * Generate a pool of transaction records with random timestamps.
     *
     * @param int $count Number of records to generate
     * @param int $memberId Target member ID
     * @param string $walletType Target wallet type
     * @param int $seed Random seed offset for timestamp variation
     * @return array<int, array{id: int, member_id: int, wallet_type: string, type: string, amount: int, balance_before: int, balance_after: int, source: string, created_at: string}>
     */
    private function generateTransactionRecords(int $count, int $memberId, string $walletType, int $seed): array
    {
        $records = [];
        // Use a base timestamp and vary it randomly to create diverse timestamps
        $baseTimestamp = strtotime('2024-01-01 00:00:00');

        for ($i = 0; $i < $count; ++$i) {
            // Generate random offset in seconds (up to ~1 year) using seed for variation
            $offsetSeconds = (($seed + $i * 7 + ($i * $i * 3)) % 31536000);
            $timestamp = $baseTimestamp + abs($offsetSeconds);

            $records[] = [
                'id' => $i + 1,
                'member_id' => $memberId,
                'wallet_type' => $walletType,
                'type' => 'income',
                'amount' => random_int(1, 10000),
                'balance_before' => random_int(0, 50000),
                'balance_after' => random_int(0, 50000),
                'source' => 'test',
                'created_at' => date('Y-m-d H:i:s', $timestamp),
            ];
        }

        return $records;
    }

    /**
     * Build the service with a mocked repository that simulates real sorting
     * behavior: records are returned sorted by created_at descending.
     *
     * @param array $allRecords Pool of all transaction records
     */
    private function buildService(array $allRecords): AppApiMemberWalletQueryService
    {
        $repository = $this->createMock(MemberWalletTransactionRepository::class);

        $repository->method('page')
            ->willReturnCallback(function (array $params, ?int $page, ?int $pageSize) use ($allRecords): array {
                $filtered = $allRecords;

                // Simulate handleSearch filtering logic
                if (! empty($params['member_id'])) {
                    $filtered = array_filter($filtered, static fn (array $r) => $r['member_id'] === (int) $params['member_id']);
                }
                if (! empty($params['wallet_type'])) {
                    $filtered = array_filter($filtered, static fn (array $r) => $r['wallet_type'] === $params['wallet_type']);
                }

                $filtered = array_values($filtered);

                // Sort by created_at descending (simulating real DB behavior)
                usort($filtered, static function (array $a, array $b): int {
                    return strcmp($b['created_at'], $a['created_at']);
                });

                return [
                    'list' => $filtered,
                    'total' => count($filtered),
                ];
            });

        return new AppApiMemberWalletQueryService($repository);
    }

    /**
     * Property 14 (core): For any transaction query result list, each record's
     * created_at must be >= the next record's created_at (descending order).
     */
    public function testTransactionRecordsAreInDescendingTimeOrder(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 1000),                // member ID
            Generators::elements('balance', 'points'),  // wallet type
            Generators::choose(2, 50),                  // number of records
            Generators::choose(0, 100000),              // seed for timestamp variation
        )->then(function (int $memberId, string $walletType, int $recordCount, int $seed) {
            $records = $this->generateTransactionRecords($recordCount, $memberId, $walletType, $seed);
            $service = $this->buildService($records);

            $result = $service->transactions($memberId, $walletType, 1, 100);

            $list = $result['list'];

            // Verify descending time order: each created_at >= next created_at
            for ($i = 0; $i < count($list) - 1; ++$i) {
                $current = $list[$i]['created_at'];
                $next = $list[$i + 1]['created_at'];

                $this->assertGreaterThanOrEqual(
                    $next,
                    $current,
                    sprintf(
                        'Record at index %d (created_at=%s) should be >= record at index %d (created_at=%s)',
                        $i,
                        $current,
                        $i + 1,
                        $next,
                    ),
                );
            }
        });
    }

    /**
     * Property 14 (mixed members): Even when the pool contains records from
     * multiple members, the filtered result for a specific member must still
     * maintain descending time order.
     */
    public function testTimeOrderMaintainedWithMixedMemberRecords(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 1000),
            Generators::elements('balance', 'points'),
            Generators::choose(5, 30),
            Generators::choose(0, 100000),
        )->then(function (int $targetMemberId, string $walletType, int $poolSize, int $seed) {
            $records = [];
            $baseTimestamp = strtotime('2024-01-01 00:00:00');

            // Generate records for multiple members with random timestamps
            $memberIds = [$targetMemberId, $targetMemberId + 1, $targetMemberId + 2];
            for ($i = 0; $i < $poolSize; ++$i) {
                $offsetSeconds = abs(($seed + $i * 13 + ($i * $i * 5)) % 31536000);
                $timestamp = $baseTimestamp + $offsetSeconds;

                $records[] = [
                    'id' => $i + 1,
                    'member_id' => $memberIds[array_rand($memberIds)],
                    'wallet_type' => self::WALLET_TYPES[array_rand(self::WALLET_TYPES)],
                    'type' => 'income',
                    'amount' => random_int(1, 10000),
                    'balance_before' => random_int(0, 50000),
                    'balance_after' => random_int(0, 50000),
                    'source' => 'test',
                    'created_at' => date('Y-m-d H:i:s', $timestamp),
                ];
            }

            $service = $this->buildService($records);
            $result = $service->transactions($targetMemberId, $walletType, 1, 100);

            $list = $result['list'];

            for ($i = 0; $i < count($list) - 1; ++$i) {
                $current = $list[$i]['created_at'];
                $next = $list[$i + 1]['created_at'];

                $this->assertGreaterThanOrEqual(
                    $next,
                    $current,
                    sprintf(
                        'Mixed pool: record at index %d (created_at=%s) should be >= record at index %d (created_at=%s)',
                        $i,
                        $current,
                        $i + 1,
                        $next,
                    ),
                );
            }
        });
    }

    /**
     * Property 14 (single record): A result with a single record trivially
     * satisfies the descending order property.
     */
    public function testSingleRecordTriviallyOrdered(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 1000),
            Generators::elements('balance', 'points'),
        )->then(function (int $memberId, string $walletType) {
            $records = $this->generateTransactionRecords(1, $memberId, $walletType, 42);
            $service = $this->buildService($records);

            $result = $service->transactions($memberId, $walletType, 1, 100);

            $this->assertCount(1, $result['list']);
            // Single record trivially satisfies ordering - no pair to compare
        });
    }
}
