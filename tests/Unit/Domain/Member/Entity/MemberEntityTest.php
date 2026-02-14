<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Member\Entity;

use App\Domain\Member\Entity\MemberEntity;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class MemberEntityTest extends TestCase
{
    private function makeEntity(): MemberEntity
    {
        $entity = new MemberEntity();
        $entity->setId(1);
        $entity->setNickname('测试用户');
        $entity->setPhone('13800138000');
        $entity->setGender('male');
        $entity->setStatus('active');
        $entity->setLevel('bronze');
        $entity->setSource('wechat');
        return $entity;
    }

    public function testBasicProperties(): void
    {
        $entity = $this->makeEntity();
        $this->assertSame(1, $entity->getId());
        $this->assertSame('测试用户', $entity->getNickname());
        $this->assertSame('13800138000', $entity->getPhone());
        $this->assertSame('male', $entity->getGender());
        $this->assertSame('active', $entity->getStatus());
    }

    public function testSetGenderFromInt(): void
    {
        $entity = new MemberEntity();
        $entity->setGender(1);
        $this->assertSame('male', $entity->getGender());
        $entity->setGender(2);
        $this->assertSame('female', $entity->getGender());
        $entity->setGender(0);
        $this->assertSame('unknown', $entity->getGender());
    }

    public function testUpdateStatus(): void
    {
        $entity = $this->makeEntity();
        $entity->updateStatus('banned');
        $this->assertSame('banned', $entity->getStatus());
    }

    public function testUpdateStatusInvalidThrows(): void
    {
        $entity = $this->makeEntity();
        $this->expectException(\Throwable::class);
        $entity->updateStatus('invalid_status');
    }

    public function testSyncTags(): void
    {
        $entity = $this->makeEntity();
        $entity->syncTags([1, 2, 3, 2]);
        $this->assertSame([1, 2, 3], $entity->getTagIds());
    }

    public function testBindPhone(): void
    {
        $entity = $this->makeEntity();
        $entity->bindPhone('13900139000');
        $this->assertSame('13900139000', $entity->getPhone());
    }

    public function testBindPhoneEmptyThrows(): void
    {
        $entity = $this->makeEntity();
        $this->expectException(\DomainException::class);
        $entity->bindPhone('');
    }

    public function testDirtyFieldsTracking(): void
    {
        $entity = new MemberEntity();
        $entity->setId(1);
        $entity->setNickname('Test');
        $arr = $entity->toArray();
        $this->assertArrayHasKey('nickname', $arr);
        $this->assertSame('Test', $arr['nickname']);
    }

    public function testClearDirty(): void
    {
        $entity = new MemberEntity();
        $entity->setNickname('Test');
        $entity->clearDirty();
        $arr = $entity->toArray();
        $this->assertEmpty($arr);
    }

    public function testLocationFields(): void
    {
        $entity = $this->makeEntity();
        $entity->setProvince('浙江省');
        $entity->setCity('杭州市');
        $entity->setDistrict('西湖区');
        $entity->setStreet('文三路');
        $entity->setCountry('中国');
        $this->assertSame('浙江省', $entity->getProvince());
        $this->assertSame('杭州市', $entity->getCity());
        $this->assertSame('西湖区', $entity->getDistrict());
    }

    public function testBirthday(): void
    {
        $entity = $this->makeEntity();
        $entity->setBirthday(Carbon::parse('1990-01-01'));
        $this->assertSame('1990-01-01', $entity->getBirthday()->toDateString());
    }

    public function testLastLogin(): void
    {
        $entity = $this->makeEntity();
        $now = Carbon::now();
        $entity->setLastLoginAt($now);
        $entity->setLastLoginIp('127.0.0.1');
        $this->assertTrue($entity->getLastLoginAt()->eq($now));
        $this->assertSame('127.0.0.1', $entity->getLastLoginIp());
    }
}
