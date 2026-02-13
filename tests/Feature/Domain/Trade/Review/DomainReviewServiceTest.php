<?php

declare(strict_types=1);

namespace HyperfTests\Feature\Domain\Trade\Review;

use App\Domain\Trade\Review\Entity\ReviewEntity;
use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Domain\Trade\Review\Service\DomainReviewService;
use App\Infrastructure\Model\Review\Review;
use DG\BypassFinals;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainReviewServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testGetEntityThrowsWhenNotFound(): void
    {
        $repo = Mockery::mock(ReviewRepository::class);
        $repo->shouldReceive('findById')->with(999)->andReturnNull();

        $service = new DomainReviewService($repo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('评价不存在');
        $service->getEntity(999);
    }

    public function testGetEntityReturnsEntity(): void
    {
        $model = Mockery::mock(Review::class)->makePartial();
        $model->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $model->shouldReceive('getAttribute')->with('order_id')->andReturn(10);
        $model->shouldReceive('getAttribute')->with('order_item_id')->andReturn(20);
        $model->shouldReceive('getAttribute')->with('product_id')->andReturn(30);
        $model->shouldReceive('getAttribute')->with('sku_id')->andReturn(40);
        $model->shouldReceive('getAttribute')->with('member_id')->andReturn(50);
        $model->shouldReceive('getAttribute')->with('rating')->andReturn(5);
        $model->shouldReceive('getAttribute')->with('content')->andReturn('很好');
        $model->shouldReceive('getAttribute')->with('images')->andReturn(null);
        $model->shouldReceive('getAttribute')->with('is_anonymous')->andReturn(false);
        $model->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $model->shouldReceive('getAttribute')->with('admin_reply')->andReturn(null);
        $model->shouldReceive('getAttribute')->with('reply_time')->andReturn(null);

        $repo = Mockery::mock(ReviewRepository::class);
        $repo->shouldReceive('findById')->with(1)->andReturn($model);

        $service = new DomainReviewService($repo);
        $entity = $service->getEntity(1);

        self::assertInstanceOf(ReviewEntity::class, $entity);
        self::assertSame(1, $entity->getId());
        self::assertSame(5, $entity->getRating());
        self::assertSame('很好', $entity->getContent());
        self::assertSame('pending', $entity->getStatus());
    }

    public function testApproveChangesStatusAndPersists(): void
    {
        $entity = $this->createPendingEntity();

        $repo = Mockery::mock(ReviewRepository::class);
        $repo->shouldReceive('updateFromEntity')->with($entity)->once()->andReturnTrue();

        $service = new DomainReviewService($repo);
        $result = $service->approve($entity);

        self::assertTrue($result);
        self::assertSame('approved', $entity->getStatus());
    }

    public function testRejectChangesStatusAndPersists(): void
    {
        $entity = $this->createPendingEntity();

        $repo = Mockery::mock(ReviewRepository::class);
        $repo->shouldReceive('updateFromEntity')->with($entity)->once()->andReturnTrue();

        $service = new DomainReviewService($repo);
        $result = $service->reject($entity);

        self::assertTrue($result);
        self::assertSame('rejected', $entity->getStatus());
    }

    public function testReplySetFieldsAndPersists(): void
    {
        $entity = $this->createPendingEntity();

        $repo = Mockery::mock(ReviewRepository::class);
        $repo->shouldReceive('updateFromEntity')->with($entity)->once()->andReturnTrue();

        $service = new DomainReviewService($repo);
        $result = $service->reply($entity, '感谢评价');

        self::assertTrue($result);
        self::assertSame('感谢评价', $entity->getAdminReply());
        self::assertNotNull($entity->getReplyTime());
    }

    public function testStatsDelegatesToRepository(): void
    {
        $expected = [
            'today_reviews' => 5,
            'pending_reviews' => 3,
            'total_reviews' => 100,
            'average_rating' => 4.2,
        ];

        $repo = Mockery::mock(ReviewRepository::class);
        $repo->shouldReceive('getStatistics')->once()->andReturn($expected);

        $service = new DomainReviewService($repo);
        self::assertSame($expected, $service->stats());
    }

    public function testApproveNonPendingThrows(): void
    {
        $entity = $this->createPendingEntity();
        $entity->approve();

        $repo = Mockery::mock(ReviewRepository::class);
        $service = new DomainReviewService($repo);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('只能审核待审核状态的评价');
        $service->approve($entity);
    }

    public function testRejectNonPendingThrows(): void
    {
        $entity = $this->createPendingEntity();
        $entity->reject();

        $repo = Mockery::mock(ReviewRepository::class);
        $service = new DomainReviewService($repo);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('只能审核待审核状态的评价');
        $service->reject($entity);
    }

    private function createPendingEntity(): ReviewEntity
    {
        $entity = new ReviewEntity();
        $entity->setId(1)->setOrderId(10)->setOrderItemId(20)->setProductId(30);
        $entity->setSkuId(40)->setMemberId(50)->setRating(4)->setContent('好评');
        $entity->setStatus('pending');
        return $entity;
    }
}
