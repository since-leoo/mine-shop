<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Review\Api\Command;

use App\Domain\Trade\Review\Api\Command\DomainApiReviewCommandService;
use App\Domain\Trade\Review\Contract\ReviewInput;
use App\Domain\Trade\Review\Entity\ReviewEntity;
use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Infrastructure\Model\Review\Review;
use DG\BypassFinals;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 * @runTestsInSeparateProcesses
 */
final class DomainApiReviewCommandServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCreateFillsMemberAndProductFieldsFromOrderItem(): void
    {
        $orderModel = new class {
            public int $id = 101;
            public int $member_id = 88;
            public string $status = 'completed';
        };

        $orderItemModel = new class {
            public int $id = 202;
            public int $order_id = 101;
            public int $product_id = 303;
            public int $sku_id = 404;
        };

        Mockery::mock('alias:App\Infrastructure\Model\Order\Order')
            ->shouldReceive('find')
            ->once()
            ->with(101)
            ->andReturn($orderModel);

        Mockery::mock('alias:App\Infrastructure\Model\Order\OrderItem')
            ->shouldReceive('find')
            ->once()
            ->with(202)
            ->andReturn($orderItemModel);

        $createdEntity = null;
        $reviewModel = Mockery::mock(Review::class);

        $repository = Mockery::mock(ReviewRepository::class);
        $repository->shouldReceive('existsByOrderItemId')
            ->once()
            ->with(202)
            ->andReturn(false);
        $repository->shouldReceive('createFromEntity')
            ->once()
            ->with(Mockery::on(function (ReviewEntity $entity) use (&$createdEntity): bool {
                $createdEntity = $entity;
                return true;
            }))
            ->andReturn($reviewModel);

        $service = new DomainApiReviewCommandService($repository);
        $review = $service->create(88, new class implements ReviewInput {
            public function getId(): int { return 0; }
            public function getOrderId(): ?int { return 101; }
            public function getOrderItemId(): ?int { return 202; }
            public function getProductId(): ?int { return null; }
            public function getSkuId(): ?int { return null; }
            public function getMemberId(): ?int { return null; }
            public function getRating(): ?int { return 5; }
            public function getContent(): ?string { return 'good'; }
            public function getImages(): ?array { return null; }
            public function getIsAnonymous(): ?bool { return false; }
        });

        self::assertInstanceOf(Review::class, $review);
        self::assertInstanceOf(ReviewEntity::class, $createdEntity);
        self::assertSame(88, $createdEntity->getMemberId());
        self::assertSame(303, $createdEntity->getProductId());
        self::assertSame(404, $createdEntity->getSkuId());
    }
}
