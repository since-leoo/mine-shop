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

namespace HyperfTests\Feature\Domain\Trade\Review;

use App\Domain\Trade\Review\Contract\ReviewInput;
use App\Domain\Trade\Review\Entity\ReviewEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ReviewEntityTest extends TestCase
{
    public function testCreateWithValidInput(): void
    {
        $entity = new ReviewEntity();
        $dto = $this->createMockInput(3, '很好的商品');

        $result = $entity->create($dto);

        self::assertSame($entity, $result);
        self::assertSame(3, $entity->getRating());
        self::assertSame('很好的商品', $entity->getContent());
        self::assertSame('pending', $entity->getStatus());
        self::assertSame(100, $entity->getOrderId());
        self::assertSame(200, $entity->getOrderItemId());
        self::assertSame(300, $entity->getProductId());
        self::assertSame(400, $entity->getSkuId());
        self::assertSame(500, $entity->getMemberId());
        self::assertFalse($entity->getIsAnonymous());
    }

    public function testCreateWithRatingBoundary1(): void
    {
        $entity = new ReviewEntity();
        $entity->create($this->createMockInput(1, '一般'));
        self::assertSame(1, $entity->getRating());
    }

    public function testCreateWithRatingBoundary5(): void
    {
        $entity = new ReviewEntity();
        $entity->create($this->createMockInput(5, '非常好'));
        self::assertSame(5, $entity->getRating());
    }

    public function testCreateWithRatingTooLow(): void
    {
        $entity = new ReviewEntity();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('评分必须在1到5之间');
        $entity->create($this->createMockInput(0, '内容'));
    }

    public function testCreateWithRatingTooHigh(): void
    {
        $entity = new ReviewEntity();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('评分必须在1到5之间');
        $entity->create($this->createMockInput(6, '内容'));
    }

    public function testCreateWithNullRating(): void
    {
        $entity = new ReviewEntity();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('评分必须在1到5之间');
        $entity->create($this->createMockInput(null, '内容'));
    }

    public function testCreateWithEmptyContent(): void
    {
        $entity = new ReviewEntity();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('评价内容不能为空');
        $entity->create($this->createMockInput(3, ''));
    }

    public function testCreateWithWhitespaceOnlyContent(): void
    {
        $entity = new ReviewEntity();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('评价内容不能为空');
        $entity->create($this->createMockInput(3, '   '));
    }

    public function testCreateWithNullContent(): void
    {
        $entity = new ReviewEntity();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('评价内容不能为空');
        $entity->create($this->createMockInput(3, null));
    }

    public function testApproveFromPending(): void
    {
        $entity = $this->createPendingEntity();
        $result = $entity->approve();

        self::assertSame($entity, $result);
        self::assertSame('approved', $entity->getStatus());
    }

    public function testRejectFromPending(): void
    {
        $entity = $this->createPendingEntity();
        $result = $entity->reject();

        self::assertSame($entity, $result);
        self::assertSame('rejected', $entity->getStatus());
    }

    public function testApproveFromApprovedThrows(): void
    {
        $entity = $this->createPendingEntity();
        $entity->approve();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('只能审核待审核状态的评价');
        $entity->approve();
    }

    public function testRejectFromRejectedThrows(): void
    {
        $entity = $this->createPendingEntity();
        $entity->reject();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('只能审核待审核状态的评价');
        $entity->reject();
    }

    public function testApproveFromRejectedThrows(): void
    {
        $entity = $this->createPendingEntity();
        $entity->reject();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('只能审核待审核状态的评价');
        $entity->approve();
    }

    public function testReply(): void
    {
        $entity = $this->createPendingEntity();
        $result = $entity->reply('感谢您的评价');

        self::assertSame($entity, $result);
        self::assertSame('感谢您的评价', $entity->getAdminReply());
        self::assertNotNull($entity->getReplyTime());
    }

    public function testToArray(): void
    {
        $entity = $this->createPendingEntity();
        $array = $entity->toArray();

        self::assertSame(100, $array['order_id']);
        self::assertSame(200, $array['order_item_id']);
        self::assertSame(300, $array['product_id']);
        self::assertSame(400, $array['sku_id']);
        self::assertSame(500, $array['member_id']);
        self::assertSame(4, $array['rating']);
        self::assertSame('好评', $array['content']);
        self::assertSame('pending', $array['status']);
        self::assertArrayNotHasKey('admin_reply', $array);
        self::assertArrayNotHasKey('reply_time', $array);
    }

    public function testToArrayWithImagesNull(): void
    {
        $entity = new ReviewEntity();
        $entity->create($this->createMockInput(3, '内容', null));
        $array = $entity->toArray();

        self::assertArrayNotHasKey('images', $array);
    }

    public function testCreateWithImages(): void
    {
        $images = ['https://example.com/1.jpg', 'https://example.com/2.jpg'];
        $entity = new ReviewEntity();
        $entity->create($this->createMockInput(5, '带图评价', $images));

        self::assertSame($images, $entity->getImages());
        $array = $entity->toArray();
        self::assertSame($images, $array['images']);
    }

    public function testCreateWithAnonymous(): void
    {
        $entity = new ReviewEntity();
        $entity->create($this->createMockInput(4, '匿名评价', null, true));

        self::assertTrue($entity->getIsAnonymous());
    }

    private function createPendingEntity(): ReviewEntity
    {
        $entity = new ReviewEntity();
        $entity->create($this->createMockInput(4, '好评'));
        return $entity;
    }

    private function createMockInput(
        ?int $rating,
        ?string $content,
        ?array $images = null,
        ?bool $isAnonymous = false
    ): ReviewInput {
        return new class($rating, $content, $images, $isAnonymous) implements ReviewInput {
            public function __construct(
                private readonly ?int $rating,
                private readonly ?string $content,
                private readonly ?array $images,
                private readonly ?bool $isAnonymous,
            ) {}

            public function getId(): int { return 0; }
            public function getOrderId(): ?int { return 100; }
            public function getOrderItemId(): ?int { return 200; }
            public function getProductId(): ?int { return 300; }
            public function getSkuId(): ?int { return 400; }
            public function getMemberId(): ?int { return 500; }
            public function getRating(): ?int { return $this->rating; }
            public function getContent(): ?string { return $this->content; }
            public function getImages(): ?array { return $this->images; }
            public function getIsAnonymous(): ?bool { return $this->isAnonymous; }
        };
    }
}
