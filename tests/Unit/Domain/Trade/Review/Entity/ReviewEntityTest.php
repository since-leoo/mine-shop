<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Review\Entity;

use App\Domain\Trade\Review\Entity\ReviewEntity;
use PHPUnit\Framework\TestCase;

class ReviewEntityTest extends TestCase
{
    private function makeEntity(string $status = 'pending'): ReviewEntity
    {
        $entity = new ReviewEntity();
        $entity->setId(1);
        $entity->setOrderId(100);
        $entity->setOrderItemId(200);
        $entity->setProductId(300);
        $entity->setSkuId(400);
        $entity->setMemberId(500);
        $entity->setRating(5);
        $entity->setContent('很好的商品');
        $entity->setStatus($status);
        return $entity;
    }

    public function testBasicProperties(): void
    {
        $entity = $this->makeEntity();
        $this->assertSame(1, $entity->getId());
        $this->assertSame(100, $entity->getOrderId());
        $this->assertSame(5, $entity->getRating());
        $this->assertSame('很好的商品', $entity->getContent());
        $this->assertSame('pending', $entity->getStatus());
    }

    public function testApprove(): void
    {
        $entity = $this->makeEntity('pending');
        $entity->approve();
        $this->assertSame('approved', $entity->getStatus());
    }

    public function testApproveNotPendingThrows(): void
    {
        $entity = $this->makeEntity('approved');
        $this->expectException(\DomainException::class);
        $entity->approve();
    }

    public function testReject(): void
    {
        $entity = $this->makeEntity('pending');
        $entity->reject();
        $this->assertSame('rejected', $entity->getStatus());
    }

    public function testRejectNotPendingThrows(): void
    {
        $entity = $this->makeEntity('approved');
        $this->expectException(\DomainException::class);
        $entity->reject();
    }

    public function testReply(): void
    {
        $entity = $this->makeEntity();
        $entity->reply('感谢您的评价');
        $this->assertSame('感谢您的评价', $entity->getAdminReply());
        $this->assertNotNull($entity->getReplyTime());
    }

    public function testImages(): void
    {
        $entity = $this->makeEntity();
        $entity->setImages(['img1.jpg', 'img2.jpg']);
        $this->assertSame(['img1.jpg', 'img2.jpg'], $entity->getImages());
    }

    public function testIsAnonymous(): void
    {
        $entity = $this->makeEntity();
        $entity->setIsAnonymous(true);
        $this->assertTrue($entity->getIsAnonymous());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        $this->assertSame(100, $arr['order_id']);
        $this->assertSame(5, $arr['rating']);
        $this->assertSame('很好的商品', $arr['content']);
        $this->assertSame('pending', $arr['status']);
    }
}
