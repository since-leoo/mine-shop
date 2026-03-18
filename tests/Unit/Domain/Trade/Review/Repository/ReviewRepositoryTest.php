<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Review\Repository;

use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Infrastructure\Model\Review\Review;
use Hyperf\Collection\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ReviewRepositoryTest extends TestCase
{
    public function testHandleItemsIncludesProductNameAndMemberNickname(): void
    {
        $member = new class {
            public string $nickname = 'Alice';
        };

        $orderItem = new class {
            public string $product_name = 'Pet toy';
        };

        $review = new class([
            'id' => 1,
            'content' => 'nice',
            'rating' => 5,
            'is_anonymous' => false,
            'status' => 'approved',
            'member' => $member,
            'orderItem' => $orderItem,
        ]) extends Review {
            public function __construct(private array $payload = []) {}

            public function __get($key)
            {
                return $this->payload[$key] ?? null;
            }

            public function relationLoaded($key)
            {
                return in_array($key, ['member', 'orderItem'], true);
            }

            public function getRelation($key)
            {
                return $this->payload[$key] ?? null;
            }

            public function toArray(): array
            {
                return [
                    'id' => $this->payload['id'],
                    'content' => $this->payload['content'],
                    'rating' => $this->payload['rating'],
                    'is_anonymous' => $this->payload['is_anonymous'],
                    'status' => $this->payload['status'],
                ];
            }
        };

        $model = (new \ReflectionClass(Review::class))->newInstanceWithoutConstructor();
        $repository = new ReviewRepository($model);
        $items = $repository->handleItems(new Collection([$review]));
        $data = $items->first();

        self::assertSame('Pet toy', $data['product_name'] ?? null);
        self::assertSame('Alice', $data['member_nickname'] ?? null);
    }
}
