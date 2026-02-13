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

use App\Domain\Trade\Review\Api\Query\DomainApiReviewQueryService;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainApiReviewQueryServiceTest extends TestCase
{
    public function testDesensitizeNicknameWithMultiByteChars(): void
    {
        // 三字中文名："张三丰" → "张***丰"
        self::assertSame('张***丰', DomainApiReviewQueryService::desensitizeNickname('张三丰'));
    }

    public function testDesensitizeNicknameWithTwoChars(): void
    {
        // 两字中文名："张三" → "张***三"
        self::assertSame('张***三', DomainApiReviewQueryService::desensitizeNickname('张三'));
    }

    public function testDesensitizeNicknameWithSingleChar(): void
    {
        // 单字："张" → "张***"
        self::assertSame('张***', DomainApiReviewQueryService::desensitizeNickname('张'));
    }

    public function testDesensitizeNicknameWithEmptyString(): void
    {
        // 空字符串返回默认匿名用户
        self::assertSame('匿名用户', DomainApiReviewQueryService::desensitizeNickname(''));
    }

    public function testDesensitizeNicknameWithEnglishName(): void
    {
        // 英文名："Alice" → "A***e"
        self::assertSame('A***e', DomainApiReviewQueryService::desensitizeNickname('Alice'));
    }

    public function testDesensitizeNicknameWithLongName(): void
    {
        // 长名字："用户昵称很长" → "用***长"
        self::assertSame('用***长', DomainApiReviewQueryService::desensitizeNickname('用户昵称很长'));
    }

    public function testDesensitizedNicknameNeverEqualsOriginal(): void
    {
        // 脱敏后的昵称不应等于原始昵称（长度 >= 2 的情况）
        $names = ['张三', '张三丰', 'Alice', 'Bob', '用户昵称'];
        foreach ($names as $name) {
            self::assertNotSame($name, DomainApiReviewQueryService::desensitizeNickname($name));
        }
    }
}
