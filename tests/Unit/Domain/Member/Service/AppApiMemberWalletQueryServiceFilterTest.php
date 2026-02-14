<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Service;

use App\Application\Api\Member\AppApiMemberWalletQueryService;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Feature: member-vip-level, Property 13: 流水记录过滤正确性
 *
 * Validates: Requirements 10.1
 *
 * For any member ID and wallet type (balance or points), all records returned
 * by the transaction query API must have member_id equal to the requested
 * member ID and wallet_type equal to the requested wallet type.
 */
class AppApiMemberWalletQueryServiceFilterTest extends TestCase
{
    use TestTrait;

    private const WALLET_TYPES = ['balance', 'points'];

    /**
     * Generate a pool of transaction records with various member_ids and wallet_types.
     *
     * @return array<int, array{member_id: int, wallet_type: string, id: int, amount: int, type: string}>
     */
    private function generateTransactionPool(int $count, array $memberIds): array
    {
        $records = [];
        for ($i = 0; $i < $count; ++$i) {
            $records[] = [
                'id' => $i + 1,
                'member_id' => $memberIds[array_rand($memberIds)],
                'wallet_type' => self::WALLET_TYPES[array_rand(self::WALLET_TYPES)],
                'type' => 'income',
                'amount' => random_int(1, 10000),
                'balance_before' => random_int(0, 50000),
                'balance_after' => random_int(0, 50000),
                'source' => 'test',
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        return $records;
    }

    /**
     * Build the service with a mocked repository that simulates real filtering
     * behavior via handleSearch.
     *
     * The mock repository's page() method applies the same where-clause logic
     * as the real handleSearch: filtering by member_id and wallet_type.
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

                return [
                    'list' => $filtered,
                    'total' => count($filtered),
                ];
            });

        return new AppApiMemberWalletQueryService($repository);
    }

    /**
     * Property 13 (core): For any member ID and wallet type, all returned records
     * must have member_id == requested member ID and wallet_type == requested wallet type.
     */
    public function testAllReturnedRecordsMatchRequestedMemberIdAndWalletType(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 1000),       // target member ID
            Generators::elements('balance', 'points'), // target wallet type
            Generators::choose(5, 30),         // pool size
        )->then(function (int $targetMemberId, string $targetWalletType, int $poolSize) {
            // Create a pool with multiple member IDs including the target
            $memberIds = [$targetMemberId];
            for ($i = 0; $i < 3; ++$i) {
                $otherMemberId = $targetMemberId + $i + 1;
                $memberIds[] = $otherMemberId;
            }

            $allRecords = $this->generateTransactionPool($poolSize, $memberIds);
            $service = $this->buildService($allRecords);

            $result = $service->transactions($targetMemberId, $targetWalletType, 1, 100);

            $this->assertArrayHasKey('list', $result);
            $this->assertArrayHasKey('total', $result);

            foreach ($result['list'] as $index => $record) {
                $this->assertSame(
                    $targetMemberId,
                    $record['member_id'],
                    sprintf(
                        'Record at index %d has member_id=%d, expected %d',
                        $index,
                        $record['member_id'],
                        $targetMemberId,
                    ),
                );

                $this->assertSame(
                    $targetWalletType,
                    $record['wallet_type'],
                    sprintf(
                        'Record at index %d has wallet_type=%s, expected %s',
                        $index,
                        $record['wallet_type'],
                        $targetWalletType,
                    ),
                );
            }
        });
    }

    /**
     * Property 13 (completeness): The number of returned records must equal
     * the count of records in the pool that match both member_id and wallet_type.
     */
    public function testReturnedCountMatchesExpectedFilteredCount(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 1000),
            Generators::elements('balance', 'points'),
            Generators::choose(5, 30),
        )->then(function (int $targetMemberId, string $targetWalletType, int $poolSize) {
            $memberIds = [$targetMemberId];
            for ($i = 0; $i < 3; ++$i) {
                $memberIds[] = $targetMemberId + $i + 1;
            }

            $allRecords = $this->generateTransactionPool($poolSize, $memberIds);
            $service = $this->buildService($allRecords);

            $result = $service->transactions($targetMemberId, $targetWalletType, 1, 100);

            // Count expected matches from the pool
            $expectedCount = count(array_filter($allRecords, static fn (array $r) => $r['member_id'] === $targetMemberId && $r['wallet_type'] === $targetWalletType));

            $this->assertSame(
                $expectedCount,
                $result['total'],
                sprintf(
                    'Expected %d records for member_id=%d, wallet_type=%s, got %d',
                    $expectedCount,
                    $targetMemberId,
                    $targetWalletType,
                    $result['total'],
                ),
            );

            $this->assertCount(
                $expectedCount,
                $result['list'],
                'List count must match total count',
            );
        });
    }

    /**
     * Property 13 (no cross-contamination): Records belonging to other members
     * or other wallet types must never appear in the result.
     */
    public function testNoRecordsFromOtherMembersOrWalletTypes(): void
    {
        $this->limitTo(200);

        $this->forAll(
            Generators::choose(1, 1000),
            Generators::elements('balance', 'points'),
            Generators::choose(10, 40),
        )->then(function (int $targetMemberId, string $targetWalletType, int $poolSize) {
            $otherMemberId = $targetMemberId + 999;
            $otherWalletType = $targetWalletType === 'balance' ? 'points' : 'balance';

            // Ensure pool has records for other member/wallet combos
            $memberIds = [$targetMemberId, $otherMemberId];
            $allRecords = $this->generateTransactionPool($poolSize, $memberIds);

            $service = $this->buildService($allRecords);
            $result = $service->transactions($targetMemberId, $targetWalletType, 1, 100);

            foreach ($result['list'] as $record) {
                $this->assertNotSame(
                    $otherMemberId,
                    $record['member_id'],
                    'Record from another member must not appear in results',
                );

                // If member_id matches, wallet_type must also match
                if ($record['member_id'] === $targetMemberId) {
                    $this->assertNotSame(
                        $otherWalletType,
                        $record['wallet_type'],
                        'Record with wrong wallet_type must not appear in results',
                    );
                }
            }
        });
    }

    /**
     * Edge case: When no records match the filter, result should be empty.
     */
    public function testEmptyResultWhenNoRecordsMatch(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 1000),
            Generators::elements('balance', 'points'),
        )->then(function (int $targetMemberId, string $targetWalletType) {
            // Create records only for a different member
            $otherMemberId = $targetMemberId + 1;
            $allRecords = $this->generateTransactionPool(10, [$otherMemberId]);

            $service = $this->buildService($allRecords);
            $result = $service->transactions($targetMemberId, $targetWalletType, 1, 100);

            $this->assertSame(0, $result['total']);
            $this->assertEmpty($result['list']);
        });
    }
}
