<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Order\ValueObject;

use App\Domain\Trade\Order\ValueObject\OrderAddressValue;
use PHPUnit\Framework\TestCase;

class OrderAddressValueTest extends TestCase
{
    public function testFromArray(): void
    {
        $address = OrderAddressValue::fromArray([
            'name' => '张三',
            'phone' => '13800138000',
            'province' => '浙江省',
            'city' => '杭州市',
            'district' => '西湖区',
            'detail' => '文三路100号',
            'full_address' => '浙江省杭州市西湖区文三路100号',
        ]);
        $this->assertSame('张三', $address->getReceiverName());
        $this->assertSame('13800138000', $address->getReceiverPhone());
        $this->assertSame('浙江省', $address->getProvince());
        $this->assertSame('杭州市', $address->getCity());
        $this->assertSame('西湖区', $address->getDistrict());
    }

    public function testGetFullAddressFallback(): void
    {
        $address = new OrderAddressValue();
        $address->setProvince('浙江省');
        $address->setCity('杭州市');
        $address->setDistrict('西湖区');
        $address->setDetail('文三路100号');
        $this->assertSame('浙江省杭州市西湖区文三路100号', $address->getFullAddress());
    }

    public function testToArray(): void
    {
        $address = OrderAddressValue::fromArray([
            'name' => '李四',
            'phone' => '13900139000',
            'province' => '上海市',
            'city' => '上海市',
            'district' => '浦东新区',
            'detail' => '张江路1号',
        ]);
        $arr = $address->toArray();
        $this->assertSame('李四', $arr['name']);
        $this->assertSame('13900139000', $arr['phone']);
        $this->assertSame('上海市', $arr['province']);
    }
}
