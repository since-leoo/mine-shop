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

namespace HyperfTests\Unit\Domain\Permission\Entity;

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\MenuEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class MenuEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $entity = new MenuEntity();
        $entity->setId(1)->setName('系统管理')->setPath('/system')->setSort(10);
        self::assertSame(1, $entity->getId());
        self::assertSame('系统管理', $entity->getName());
        self::assertSame('/system', $entity->getPath());
        self::assertSame(10, $entity->getSort());
    }

    public function testEmptyNameThrows(): void
    {
        $entity = new MenuEntity();
        $this->expectException(\DomainException::class);
        $entity->setName('');
    }

    public function testNegativeParentIdThrows(): void
    {
        $entity = new MenuEntity();
        $this->expectException(\DomainException::class);
        $entity->setParentId(-1);
    }

    public function testChangeStatus(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setStatus(Status::DISABLE);
        self::assertSame(Status::DISABLE, $entity->getStatus());
    }

    public function testSortNonNegative(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setSort(-5);
        self::assertSame(0, $entity->getSort());
    }

    public function testMetaTypeNormalization(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setMeta(['type' => 'm']);
        self::assertSame('M', $entity->metaType());
    }

    public function testMetaTypeInvalidDefaultsToM(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setMeta(['type' => 'X']);
        self::assertSame('M', $entity->metaType());
    }

    public function testAllowsButtonPermissions(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setMeta(['type' => 'M']);
        self::assertTrue($entity->allowsButtonPermissions());

        $entity->setMeta(['type' => 'C']);
        self::assertFalse($entity->allowsButtonPermissions());
    }

    public function testToArrayDirtyTracking(): void
    {
        $entity = new MenuEntity();
        $entity->setName('菜单A');
        $arr = $entity->toArray();
        self::assertArrayHasKey('name', $arr);
        self::assertSame('菜单A', $arr['name']);
    }
}
