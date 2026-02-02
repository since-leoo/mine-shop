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

namespace HyperfTests\Feature\Domain\Permission;

use App\Domain\Permission\ValueObject\ButtonPermission;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ButtonPermissionTest extends TestCase
{
    public function testFromArrayAndWithId(): void
    {
        $permission = ButtonPermission::fromArray([
            'code' => 'menu:create',
            'title' => '新增',
            'i18n' => 'permission.menu.create',
        ]);

        self::assertSame('menu:create', $permission->code());
        self::assertSame('新增', $permission->title());

        $withId = $permission->withId(12);
        self::assertSame(12, $withId->id());
        self::assertSame('menu:create', $withId->code());
    }

    public function testFromArrayRejectsEmptyCode(): void
    {
        $this->expectException(\DomainException::class);
        ButtonPermission::fromArray(['title' => '无效按钮']);
    }
}
