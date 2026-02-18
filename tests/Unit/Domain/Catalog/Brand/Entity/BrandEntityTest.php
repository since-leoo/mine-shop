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

namespace HyperfTests\Unit\Domain\Catalog\Brand\Entity;

use App\Domain\Catalog\Brand\Entity\BrandEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class BrandEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $entity = new BrandEntity();
        $entity->setId(1)->setName('Nike')->setLogo('nike.png')->setSort(10);
        self::assertSame(1, $entity->getId());
        self::assertSame('Nike', $entity->getName());
        self::assertSame('nike.png', $entity->getLogo());
        self::assertSame(10, $entity->getSort());
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
        self::assertTrue($entity->isActive());
        $entity->deactivate();
        self::assertFalse($entity->isActive());
        $entity->activate();
        self::assertTrue($entity->isActive());
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
        self::assertSame(0, $entity->getSort());
    }

    public function testNeedsSort(): void
    {
        $entity = new BrandEntity();
        self::assertTrue($entity->needsSort());
        $entity->setName('Test')->setSort(5);
        self::assertFalse($entity->needsSort());
    }

    public function testToArray(): void
    {
        $entity = new BrandEntity();
        $entity->setName('Adidas')->setSort(5)->setStatus('active');
        $arr = $entity->toArray();
        self::assertSame('Adidas', $arr['name']);
        self::assertSame(5, $arr['sort']);
        self::assertSame('active', $arr['status']);
    }
}
