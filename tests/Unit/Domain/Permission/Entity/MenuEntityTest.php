<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Permission\Entity;

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\MenuEntity;
use PHPUnit\Framework\TestCase;

class MenuEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $entity = new MenuEntity();
        $entity->setId(1)->setName('系统管理')->setPath('/system')->setSort(10);
        $this->assertSame(1, $entity->getId());
        $this->assertSame('系统管理', $entity->getName());
        $this->assertSame('/system', $entity->getPath());
        $this->assertSame(10, $entity->getSort());
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
        $this->assertSame(Status::DISABLE, $entity->getStatus());
    }

    public function testSortNonNegative(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setSort(-5);
        $this->assertSame(0, $entity->getSort());
    }

    public function testMetaTypeNormalization(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setMeta(['type' => 'm']);
        $this->assertSame('M', $entity->metaType());
    }

    public function testMetaTypeInvalidDefaultsToM(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setMeta(['type' => 'X']);
        $this->assertSame('M', $entity->metaType());
    }

    public function testAllowsButtonPermissions(): void
    {
        $entity = new MenuEntity();
        $entity->setName('Test')->setMeta(['type' => 'M']);
        $this->assertTrue($entity->allowsButtonPermissions());

        $entity->setMeta(['type' => 'C']);
        $this->assertFalse($entity->allowsButtonPermissions());
    }

    public function testToArrayDirtyTracking(): void
    {
        $entity = new MenuEntity();
        $entity->setName('菜单A');
        $arr = $entity->toArray();
        $this->assertArrayHasKey('name', $arr);
        $this->assertSame('菜单A', $arr['name']);
    }
}
