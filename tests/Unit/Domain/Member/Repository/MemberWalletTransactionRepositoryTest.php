<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Repository;

use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Infrastructure\Model\Member\MemberWalletTransaction;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class MemberWalletTransactionRepositoryTest extends TestCase
{
    public function testFindExpirablePointGrantTransactionsBuildsExpectedQuery(): void
    {
        $cutoff = Carbon::parse('2024-06-06 10:00:00');
        $expected = new Collection([new class extends MemberWalletTransaction {
            public function __construct() {}
        }]);

        $query = $this->makeQueryBuilder();
        $whereCalls = [];
        $orderByCalls = [];

        $query
            ->expects(self::exactly(4))
            ->method('where')
            ->willReturnCallback(static function (...$args) use (&$whereCalls, $query): Builder {
                $whereCalls[] = $args;
                return $query;
            });
        $query
            ->expects(self::once())
            ->method('whereNotIn')
            ->with('source', ['points_expire', 'purchase_refund'])
            ->willReturnSelf();
        $query
            ->expects(self::exactly(3))
            ->method('orderBy')
            ->willReturnCallback(static function (...$args) use (&$orderByCalls, $query): Builder {
                $orderByCalls[] = $args;
                return $query;
            });
        $query
            ->expects(self::once())
            ->method('get')
            ->willReturn($expected);

        $repository = $this->makeRepository($query);

        self::assertSame($expected, $repository->findExpirablePointGrantTransactions($cutoff));
        self::assertSame(
            [
                ['wallet_type', 'points', null, 'and'],
                ['type', 'adjust_in', null, 'and'],
                ['amount', '>', 0, 'and'],
                ['created_at', '<=', $cutoff, 'and'],
            ],
            $whereCalls,
        );
        self::assertSame([['member_id'], ['created_at'], ['id']], $orderByCalls);
    }

    public function testFindExpiredPointSourceTransactionIdsReturnsProcessedSourceIds(): void
    {
        $query = $this->makeQueryBuilder();
        $whereCalls = [];

        $query
            ->expects(self::exactly(3))
            ->method('where')
            ->willReturnCallback(static function (...$args) use (&$whereCalls, $query): Builder {
                $whereCalls[] = $args;
                return $query;
            });
        $query
            ->expects(self::once())
            ->method('whereIn')
            ->with('related_id', [11, 12])
            ->willReturnSelf();
        $query
            ->expects(self::once())
            ->method('pluck')
            ->with('related_id')
            ->willReturn(new Collection(['11', 12]));

        $repository = $this->makeRepository($query);

        self::assertSame([11, 12], $repository->findExpiredPointSourceTransactionIds([11, 12]));
        self::assertSame(
            [
                ['wallet_type', 'points', null, 'and'],
                ['source', 'points_expire', null, 'and'],
                ['related_type', 'wallet_transaction', null, 'and'],
            ],
            $whereCalls,
        );
    }

    public function testFindExpiredPointSourceTransactionIdsSkipsQueryWhenIdsEmpty(): void
    {
        $query = $this->makeQueryBuilder();
        $query->expects(self::never())->method('where');

        $repository = $this->makeRepository($query);

        self::assertSame([], $repository->findExpiredPointSourceTransactionIds([]));
    }

    private function makeRepository(Builder $query): MemberWalletTransactionRepository
    {
        $model = $this->createMock(MemberWalletTransaction::class);
        $model
            ->method('newQuery')
            ->willReturn($query);

        return new MemberWalletTransactionRepository($model);
    }

    private function makeQueryBuilder(): Builder
    {
        return $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'get', 'pluck'])
            ->addMethods(['selectRaw', 'whereNotIn', 'whereIn', 'groupBy', 'orderBy'])
            ->getMock();
    }
}
