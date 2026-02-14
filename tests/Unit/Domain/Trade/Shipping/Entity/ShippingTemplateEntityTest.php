<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Shipping\Entity;

use App\Domain\Trade\Shipping\Entity\ShippingTemplateEntity;
use PHPUnit\Framework\TestCase;

class ShippingTemplateEntityTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $entity = new ShippingTemplateEntity();
        $this->assertSame(0, $entity->getId());
        $this->assertNull($entity->getName());
        $this->assertNull($entity->getChargeType());
        $this->assertFalse($entity->getIsDefault());
        $this->assertSame('active', $entity->getStatus());
    }

    public function testSettersChaining(): void
    {
        $entity = new ShippingTemplateEntity();
        $entity->setId(1)
            ->setName('标准运费模板')
            ->setChargeType('weight')
            ->setIsDefault(true)
            ->setStatus('active');

        $this->assertSame(1, $entity->getId());
        $this->assertSame('标准运费模板', $entity->getName());
        $this->assertSame('weight', $entity->getChargeType());
        $this->assertTrue($entity->getIsDefault());
    }

    public function testRules(): void
    {
        $entity = new ShippingTemplateEntity();
        $rules = [
            ['region_ids' => ['浙江省'], 'first_unit' => 1, 'first_price' => 800, 'additional_unit' => 1, 'additional_price' => 200],
        ];
        $entity->setRules($rules);
        $this->assertSame($rules, $entity->getRules());
    }

    public function testFreeRules(): void
    {
        $entity = new ShippingTemplateEntity();
        $freeRules = [
            ['region_ids' => ['上海市'], 'free_amount' => 9900],
        ];
        $entity->setFreeRules($freeRules);
        $this->assertSame($freeRules, $entity->getFreeRules());
    }

    public function testToArray(): void
    {
        $entity = new ShippingTemplateEntity();
        $entity->setName('模板A')->setChargeType('quantity')->setStatus('active');
        $arr = $entity->toArray();
        $this->assertSame('模板A', $arr['name']);
        $this->assertSame('quantity', $arr['charge_type']);
        $this->assertSame('active', $arr['status']);
    }

    public function testToArrayFiltersNull(): void
    {
        $entity = new ShippingTemplateEntity();
        $entity->setName('模板B');
        $arr = $entity->toArray();
        $this->assertArrayHasKey('name', $arr);
        // charge_type is null, should be filtered
        $this->assertArrayNotHasKey('charge_type', $arr);
    }
}
