<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Interface\Api\Transformer;

use App\Interface\Api\Transformer\ReviewTransformer;
use App\Infrastructure\Model\Review\Review;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ReviewTransformerTest extends TestCase
{
    public function testDesensitizeNicknameKeepsHeadAndTail(): void
    {
        self::assertSame('?***?', ReviewTransformer::desensitizeNickname('???'));
        self::assertSame('?***', ReviewTransformer::desensitizeNickname('?'));
        self::assertSame('????', ReviewTransformer::desensitizeNickname(''));
    }

    public function testTransformIncludesSkuAndAnonymousRules(): void
    {
        $member = new class {
            public string $nickname = '??';
            public string $avatar = 'https://example.com/avatar.png';
        };

        $orderItem = new class {
            public string $sku_name = '??? 500g';
        };

        $review = new class([
            'id' => 12,
            'rating' => 5,
            'content' => '?????',
            'images' => ['https://example.com/review-1.png'],
            'is_anonymous' => true,
            'admin_reply' => '????',
            'reply_time' => new class {
                public function toDateTimeString(): string { return '2026-03-18 12:30:00'; }
            },
            'created_at' => new class {
                public function toDateTimeString(): string { return '2026-03-18 10:00:00'; }
            },
            'member' => $member,
            'orderItem' => $orderItem,
        ]) extends Review {
            public function __construct(private array $payload = []) {}
            public function relationLoaded($key) { return in_array($key, ['member', 'orderItem'], true); }
            public function getRelation($key) { return $this->payload[$key] ?? null; }
            public function getRelationValue($key) { return $this->payload[$key] ?? null; }
            public function __get($key) { return $this->payload[$key] ?? null; }
        };

        $transformer = new ReviewTransformer();
        $result = $transformer->transform($review);

        self::assertSame('?***?', $result['nickname']);
        self::assertSame('', $result['avatar']);
        self::assertSame('??? 500g', $result['sku_name']);
        self::assertSame('????', $result['admin_reply']);
        self::assertSame('2026-03-18 10:00:00', $result['created_at']);
    }
}
