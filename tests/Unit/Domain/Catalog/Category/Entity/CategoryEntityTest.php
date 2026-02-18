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

namespace HyperfTests\Unit\Domain\Catalog\Category\Entity;

use App\Domain\Catalog\Category\Entity\CategoryEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class CategoryEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $entity = new CategoryEntity();
        $entity->setId(1)->setName('电子产品')->setParentId(0)->setLevel(1)->setSort(10);
        self::assertSame(1, $entity->getId());
        self::assertSame('电子产品', $entity->getName());
        self::assertSame(0, $entity->getParentId());
        self::assertSame(1, $entity->getLevel());
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(\DomainException::class);
        (new CategoryEntity())->setName('');
    }

    public function testNegativeParentIdThrows(): void
    {
        $this->expectException(\DomainException::class);
        (new CategoryEntity())->setParentId(-1);
    }

    public function testLevelMustBePositive(): void
    {
        $this->expectException(\DomainException::class);
        (new CategoryEntity())->setLevel(0);
    }

    public function testIsRoot(): void
    {
        $entity = new CategoryEntity();
        $entity->setName('Root')->setParentId(0);
        self::assertTrue($entity->isRoot());

        $entity->setParentId(1);
        self::assertFalse($entity->isRoot());
    }

    public function testActivateDeactivate(): void
    {
        $entity = new CategoryEntity();
        $entity->setName('Test');
        self::assertTrue($entity->isActive());
        $entity->deactivate();
        self::assertFalse($entity->isActive());
        $entity->activate();
        self::assertTrue($entity->isActive());
    }

    public function testInvalidStatusThrows(): void
    {
        $entity = new CategoryEntity();
        $entity->setName('Test');
        $this->expectException(\DomainException::class);
        $entity->changeStatus('invalid');
    }

    public function testSortNonNegative(): void
    {
        $entity = new CategoryEntity();
        $entity->setName('Test')->setSort(-5);
        self::assertSame(0, $entity->getSort());
    }

    public function testToArray(): void
    {
        $entity = new CategoryEntity();
        $entity->setName('服装')->setParentId(0)->setLevel(1)->setSort(5);
        $arr = $entity->toArray();
        self::assertSame('服装', $arr['name']);
        self::assertSame(0, $arr['parent_id']);
        self::assertSame(1, $arr['level']);
    }
}
