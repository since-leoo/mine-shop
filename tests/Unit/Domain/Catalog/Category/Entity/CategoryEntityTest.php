<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Catalog\Category\Entity;

use App\Domain\Catalog\Category\Entity\CategoryEntity;
use PHPUnit\Framework\TestCase;

class CategoryEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $entity = new CategoryEntity();
        $entity->setId(1)->setName('电子产品')->setParentId(0)->setLevel(1)->setSort(10);
        $this->assertSame(1, $entity->getId());
        $this->assertSame('电子产品', $entity->getName());
        $this->assertSame(0, $entity->getParentId());
        $this->assertSame(1, $entity->getLevel());
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
        $this->assertTrue($entity->isRoot());

        $entity->setParentId(1);
        $this->assertFalse($entity->isRoot());
    }

    public function testActivateDeactivate(): void
    {
        $entity = new CategoryEntity();
        $entity->setName('Test');
        $this->assertTrue($entity->isActive());
        $entity->deactivate();
        $this->assertFalse($entity->isActive());
        $entity->activate();
        $this->assertTrue($entity->isActive());
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
        $this->assertSame(0, $entity->getSort());
    }

    public function testToArray(): void
    {
        $entity = new CategoryEntity();
        $entity->setName('服装')->setParentId(0)->setLevel(1)->setSort(5);
        $arr = $entity->toArray();
        $this->assertSame('服装', $arr['name']);
        $this->assertSame(0, $arr['parent_id']);
        $this->assertSame(1, $arr['level']);
    }
}
