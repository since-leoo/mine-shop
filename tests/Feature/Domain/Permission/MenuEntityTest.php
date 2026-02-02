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

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\MenuEntity;
use App\Domain\Permission\ValueObject\ButtonPermission;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class MenuEntityTest extends TestCase
{
    public function testEnsureCanPersistRequiresName(): void
    {
        $entity = new MenuEntity();
        $this->expectException(\DomainException::class);
        $entity->ensureCanPersist(true);
    }

    public function testAllowsButtonPermissionsOnlyForMenus(): void
    {
        $entity = (new MenuEntity())
            ->setName('测试菜单')
            ->setMeta(['type' => 'M']);
        self::assertTrue($entity->allowsButtonPermissions());

        $entity->setMeta(['type' => 'I']);
        self::assertFalse($entity->allowsButtonPermissions());
    }

    public function testButtonPayloads(): void
    {
        $entity = (new MenuEntity())
            ->setName('测试菜单')
            ->setMeta(['type' => 'M'])
            ->setStatus(Status::Normal)
            ->setButtonPermissions([
                ButtonPermission::fromArray(['id' => 1, 'code' => 'btn:add', 'title' => '新增']),
            ]);

        $payloads = $entity->buttonPayloads();
        self::assertCount(1, $payloads);
        self::assertSame('btn:add', $payloads[0]['code']);
    }
}
