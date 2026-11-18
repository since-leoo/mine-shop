<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ProductReview;

use App\Domain\Trade\Review\Contract\ReviewInput;
use App\Infrastructure\Error\ProductReviewError;
use PHPUnit\Framework\TestCase;
use Plugin\ProductReview\Domain\Trade\Review\Entity\ReviewEntity;

final class ErrorCatalogTest extends TestCase
{
    public function testRatingValidationExposesItsStableBusinessCode(): void
    {
        $input = $this->createMock(ReviewInput::class);
        $input->method('getRating')->willReturn(0);
        $this->expectException(\DomainException::class);
        $this->expectExceptionCode(ProductReviewError::RATING_OUT_OF_RANGE->value);
        $this->expectExceptionMessage('评分必须在1到5之间');
        (new ReviewEntity())->create($input);
    }

    public function testRepeatedReviewKeepsTheEntityStateAndReturnsTheReviewCode(): void
    {
        $review = (new ReviewEntity())->approve();
        try {
            $review->reject();
            self::fail('An approved review cannot be rejected again');
        } catch (\DomainException $error) {
            self::assertSame(ProductReviewError::REVIEW_NOT_PENDING->value, $error->getCode());
            self::assertSame('只能审核待审核状态的评价', $error->getMessage());
            self::assertSame('approved', $review->getStatus());
        }
    }
}
