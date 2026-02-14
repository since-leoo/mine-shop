<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Catalog\Brand\Entity;

use App\Domain\Catalog\Brand\Entity\BrandEntity;
use PHPUnit\Framework\TestCase;

class BrandEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $entity = new BrandEntity();
        $entity->setId(1)->setName('Nike')->setLogo('nike.png')->setSort(10);
        $this->assertSame(1, $entity->getId());
        $this->assertSame('Nike', $entity->getName());
        $this->assertSame('nike.png', $entity->getLogo());
        $this->assertSame(10, $entity->getSort());
    }

    public function testEmptyNameThrows(): void
    {
        $entity = new BrandEntity();
        $this->expectException(\DomainException::class);
        $entity->setName('');
    }

    public function testActivateDeactivate(): void
    {
        $entity = new BrandEntity();
        $entity->setName('Test');
        $this->assertTrue($entity->isActive());
        $entity->deactivate();
        $this->assertFalse($entity->isActive());
        $entity->activate();
        $this->assertTrue($entity->isActive());
    }

    public function testInvalidStatusThrows(): void
    {
        $entity = new BrandEntity();
        $entity->setName('Test');
        $this->expectException(\DomainException::class);
        $entity->changeStatus('invalid');
    }

    public function testSortNonNegative(): void
    {
        $entity = new BrandEntity();
        $entity->setName('Test')->setSort(-5);
        $this->assertSame(0, $entity->getSort());
    }

    public function testNeedsSort(): void
    {
        $entity = new BrandEntity();
        $this->assertTrue($entity->needsSort());
        $entity->setName('Test')->setSort(5);
        $this->assertFalse($entity->needsSort());
    }

    public function testToArray(): void
    {
        $entity = new BrandEntity();
        $entity->setName('Adidas')->setSort(5)->setStatus('active');
        $arr = $entity->toArray();
        $this->assertSame('Adidas', $arr['name']);
        $this->assertSame(5, $arr['sort']);
        $this->assertSame('active', $arr['status']);
    }
}
