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

namespace HyperfTests\Unit\Domain\Marketing\GroupBuy;

use App\Domain\Marketing\GroupBuy\Entity\GroupBuyEntity;
use PHPUnit\Framework\TestCase;

/**
 * Feature: marketing-activity-status, Property 9: GroupBuyEntity.start() 状态守卫
 * Feature: marketing-activity-status, Property 10: GroupBuyEntity.end() 状态守卫.
 *
 * **Validates: Requirements 8.1, 8.2, 8.3, 8.4**
 *
 * @internal
 * @coversNothing
 */
final class GroupBuyEntityTest extends TestCase
{
    private const ITERATIONS = 100;

    /**
     * All possible statuses for GroupBuyEntity.
     */
    private const ALL_STATUSES = ['pending', 'active', 'ended', 'cancelled', 'sold_out'];

    // ========================================================================
    // Property 9: GroupBuyEntity.start() 状态守卫
    // ========================================================================

    /**
     * Property 9a: start() succeeds when status is 'pending', transitioning to 'active'.
     *
     * For any GroupBuyEntity with status 'pending', calling start() should
     * transition the status to 'active' and return the entity instance.
     *
     * **Validates: Requirements 8.1**
     *
     * @dataProvider provideStartSucceedsWhenStatusIsPendingCases
     */
    public function testStartSucceedsWhenStatusIsPending(GroupBuyEntity $entity): void
    {
        $result = $entity->start();

        self::assertSame('active', $entity->getStatus(), 'start() should transition status from pending to active');
        self::assertSame($entity, $result, 'start() should return the entity instance for fluent chaining');
    }

    // ========================================================================
    // Data Providers — generating at least 100 data sets each
    // ========================================================================

    /**
     * Generates 100+ GroupBuyEntity instances with status 'pending'.
     * Each entity has randomized non-status fields to ensure the property
     * holds regardless of other entity state.
     *
     * @return \Generator<string, array{GroupBuyEntity}>
     */
    public static function provideStartSucceedsWhenStatusIsPendingCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            yield "pending entity #{$i}" => [self::createEntityWithStatus('pending')];
        }
    }

    /**
     * Property 9b: start() throws DomainException for any non-pending status.
     *
     * For any GroupBuyEntity with status other than 'pending' (active, ended, cancelled, sold_out),
     * calling start() should throw a DomainException without modifying the status.
     *
     * **Validates: Requirements 8.2**
     *
     * @dataProvider provideStartThrowsDomainExceptionForNonPendingStatusCases
     */
    public function testStartThrowsDomainExceptionForNonPendingStatus(GroupBuyEntity $entity, string $originalStatus): void
    {
        try {
            $entity->start();
            self::fail(\sprintf(
                'Expected DomainException when calling start() on entity with status "%s"',
                $originalStatus,
            ));
        } catch (\DomainException $e) {
            self::assertSame(
                $originalStatus,
                $entity->getStatus(),
                \sprintf('Status should remain "%s" after failed start() call', $originalStatus),
            );
        }
    }

    /**
     * Generates 100+ GroupBuyEntity instances with non-pending statuses.
     * Distributes evenly across active, ended, cancelled, sold_out (25 each).
     *
     * @return \Generator<string, array{GroupBuyEntity, string}>
     */
    public static function provideStartThrowsDomainExceptionForNonPendingStatusCases(): iterable
    {
        $nonPendingStatuses = ['active', 'ended', 'cancelled', 'sold_out'];
        $perStatus = (int) ceil(self::ITERATIONS / \count($nonPendingStatuses));

        foreach ($nonPendingStatuses as $status) {
            for ($i = 0; $i < $perStatus; ++$i) {
                yield "{$status} entity #{$i}" => [self::createEntityWithStatus($status), $status];
            }
        }
    }

    // ========================================================================
    // Property 10: GroupBuyEntity.end() 状态守卫
    // ========================================================================

    /**
     * Property 10a: end() succeeds when status is not 'ended', transitioning to 'ended'.
     *
     * For any GroupBuyEntity with status other than 'ended' (pending, active, cancelled, sold_out),
     * calling end() should transition the status to 'ended' and return the entity instance.
     *
     * **Validates: Requirements 8.3**
     *
     * @dataProvider provideEndSucceedsWhenStatusIsNotEndedCases
     */
    public function testEndSucceedsWhenStatusIsNotEnded(GroupBuyEntity $entity, string $originalStatus): void
    {
        $result = $entity->end();

        self::assertSame(
            'ended',
            $entity->getStatus(),
            \sprintf('end() should transition status from "%s" to "ended"', $originalStatus),
        );
        self::assertSame($entity, $result, 'end() should return the entity instance for fluent chaining');
    }

    /**
     * Generates 100+ GroupBuyEntity instances with non-ended statuses.
     * Distributes evenly across pending, active, cancelled, sold_out (25 each).
     *
     * @return \Generator<string, array{GroupBuyEntity, string}>
     */
    public static function provideEndSucceedsWhenStatusIsNotEndedCases(): iterable
    {
        $nonEndedStatuses = ['pending', 'active', 'cancelled', 'sold_out'];
        $perStatus = (int) ceil(self::ITERATIONS / \count($nonEndedStatuses));

        foreach ($nonEndedStatuses as $status) {
            for ($i = 0; $i < $perStatus; ++$i) {
                yield "{$status} entity #{$i}" => [self::createEntityWithStatus($status), $status];
            }
        }
    }

    /**
     * Property 10b: end() throws DomainException when status is already 'ended'.
     *
     * For any GroupBuyEntity with status 'ended', calling end() should throw
     * a DomainException without modifying the status.
     *
     * **Validates: Requirements 8.4**
     *
     * @dataProvider provideEndThrowsDomainExceptionWhenAlreadyEndedCases
     */
    public function testEndThrowsDomainExceptionWhenAlreadyEnded(GroupBuyEntity $entity): void
    {
        try {
            $entity->end();
            self::fail('Expected DomainException when calling end() on entity with status "ended"');
        } catch (\DomainException $e) {
            self::assertSame(
                'ended',
                $entity->getStatus(),
                'Status should remain "ended" after failed end() call',
            );
        }
    }

    /**
     * Generates 100+ GroupBuyEntity instances with status 'ended'.
     *
     * @return \Generator<string, array{GroupBuyEntity}>
     */
    public static function provideEndThrowsDomainExceptionWhenAlreadyEndedCases(): iterable
    {
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            yield "ended entity #{$i}" => [self::createEntityWithStatus('ended')];
        }
    }

    // ========================================================================
    // Helper: create a GroupBuyEntity with a given status and random fields
    // ========================================================================

    /**
     * Creates a GroupBuyEntity with the specified status and randomized non-status fields.
     * This ensures the property tests cover diverse entity configurations.
     */
    private static function createEntityWithStatus(string $status): GroupBuyEntity
    {
        $entity = new GroupBuyEntity();
        $entity->setId(random_int(1, 100000));
        $entity->setTitle('Test Activity ' . bin2hex(random_bytes(4)));
        $entity->setProductId(random_int(1, 10000));
        $entity->setSkuId(random_int(1, 10000));
        $entity->setOriginalPrice(random_int(100, 100000));
        $entity->setGroupPrice(random_int(50, 99999));
        $entity->setMinPeople(random_int(2, 5));
        $entity->setMaxPeople(random_int(6, 50));
        $entity->setStartTime(date('Y-m-d H:i:s', time() + random_int(-86400, 86400)));
        $entity->setEndTime(date('Y-m-d H:i:s', time() + random_int(86401, 172800)));
        $entity->setGroupTimeLimit(random_int(1, 72));
        $entity->setTotalQuantity(random_int(1, 10000));
        $entity->setSoldQuantity(random_int(0, 100));
        $entity->setSortOrder(random_int(0, 999));
        $entity->setIsEnabled((bool) random_int(0, 1));
        $entity->setStatus($status);

        return $entity;
    }
}
