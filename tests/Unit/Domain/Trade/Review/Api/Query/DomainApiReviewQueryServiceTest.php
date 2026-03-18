<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Review\Api\Query;

use App\Domain\Trade\Review\Api\Query\DomainApiReviewQueryService;
use App\Domain\Trade\Review\Repository\ReviewRepository;
use Hyperf\Collection\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainApiReviewQueryServiceTest extends TestCase
{
    public function testListByProductReturnsRawRepositoryPayload(): void
    {
        $repository = $this->createMock(ReviewRepository::class);
        $repository->expects(self::once())
            ->method('countApprovedProductReviews')
            ->with(12, ['rating_level' => 'good'])
            ->willReturn(5);
        $repository->expects(self::once())
            ->method('getApprovedProductReviews')
            ->with(12, ['rating_level' => 'good'], 2, 10)
            ->willReturn(new Collection(['foo']));

        $service = new DomainApiReviewQueryService($repository);
        $result = $service->listByProduct(12, ['rating_level' => 'good'], 2, 10);

        self::assertSame(5, $result['total']);
        self::assertInstanceOf(Collection::class, $result['list']);
        self::assertSame(2, $result['page']);
        self::assertSame(10, $result['page_size']);
    }
}
