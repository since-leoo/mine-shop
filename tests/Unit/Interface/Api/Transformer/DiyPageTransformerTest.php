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

namespace HyperfTests\Unit\Interface\Api\Transformer;

use App\Interface\Api\Transformer\DiyPageTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DiyPageTransformerTest extends TestCase
{
    public function testEmptyPayloadReturnsStableShape(): void
    {
        $transformer = new DiyPageTransformer();

        self::assertSame([
            'page' => null,
            'components' => [],
            'publishedAt' => null,
        ], $transformer->transform(null));
    }

    public function testDisabledComponentsAreNotReturned(): void
    {
        $transformer = new DiyPageTransformer();

        $result = $transformer->transform([
            'version' => 1,
            'page' => [
                'key' => 'home',
                'title' => '首页',
            ],
            'components' => [
                [
                    'id' => 'banner-1',
                    'type' => 'banner',
                    'enabled' => true,
                    'data' => ['items' => []],
                ],
                [
                    'id' => 'gap-1',
                    'type' => 'gap',
                    'enabled' => false,
                    'data' => [],
                ],
            ],
        ]);

        self::assertSame('home', $result['page']['key']);
        self::assertCount(1, $result['components']);
        self::assertSame('banner-1', $result['components'][0]['id']);
    }

    public function testPublishedAtUsesCamelCaseField(): void
    {
        $transformer = new DiyPageTransformer();

        $result = $transformer->transform([
            'version' => 1,
            'page' => [
                'key' => 'home',
                'title' => '首页',
            ],
            'components' => [],
            'published_at' => '2026-06-06 12:00:00',
        ]);

        self::assertSame('2026-06-06 12:00:00', $result['publishedAt']);
    }
}
