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

namespace HyperfTests\Unit\Domain\Trade\Shipping\Entity;

use App\Domain\Trade\Shipping\Entity\ShippingTemplateEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ShippingTemplateEntityTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $entity = new ShippingTemplateEntity();
        self::assertSame(0, $entity->getId());
        self::assertNull($entity->getName());
        self::assertNull($entity->getChargeType());
        self::assertFalse($entity->getIsDefault());
        self::assertSame('active', $entity->getStatus());
    }

    public function testSettersChaining(): void
    {
        $entity = new ShippingTemplateEntity();
        $entity->setId(1)
            ->setName('标准运费模板')
            ->setChargeType('weight')
            ->setIsDefault(true)
            ->setStatus('active');

        self::assertSame(1, $entity->getId());
        self::assertSame('标准运费模板', $entity->getName());
        self::assertSame('weight', $entity->getChargeType());
        self::assertTrue($entity->getIsDefault());
    }

    public function testRules(): void
    {
        $entity = new ShippingTemplateEntity();
        $rules = [
            ['region_ids' => ['浙江省'], 'first_unit' => 1, 'first_price' => 800, 'additional_unit' => 1, 'additional_price' => 200],
        ];
        $entity->setRules($rules);
        self::assertSame($rules, $entity->getRules());
    }

    public function testFreeRules(): void
    {
        $entity = new ShippingTemplateEntity();
        $freeRules = [
            ['region_ids' => ['上海市'], 'free_amount' => 9900],
        ];
        $entity->setFreeRules($freeRules);
        self::assertSame($freeRules, $entity->getFreeRules());
    }

    public function testToArray(): void
    {
        $entity = new ShippingTemplateEntity();
        $entity->setName('模板A')->setChargeType('quantity')->setStatus('active');
        $arr = $entity->toArray();
        self::assertSame('模板A', $arr['name']);
        self::assertSame('quantity', $arr['charge_type']);
        self::assertSame('active', $arr['status']);
    }

    public function testToArrayFiltersNull(): void
    {
        $entity = new ShippingTemplateEntity();
        $entity->setName('模板B');
        $arr = $entity->toArray();
        self::assertArrayHasKey('name', $arr);
        // charge_type is null, should be filtered
        self::assertArrayNotHasKey('charge_type', $arr);
    }
}
