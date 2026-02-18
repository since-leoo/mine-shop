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

namespace HyperfTests\Unit\Domain\Trade\Review\Entity;

use App\Domain\Trade\Review\Entity\ReviewEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ReviewEntityTest extends TestCase
{
    public function testBasicProperties(): void
    {
        $entity = $this->makeEntity();
        self::assertSame(1, $entity->getId());
        self::assertSame(100, $entity->getOrderId());
        self::assertSame(5, $entity->getRating());
        self::assertSame('很好的商品', $entity->getContent());
        self::assertSame('pending', $entity->getStatus());
    }

    public function testApprove(): void
    {
        $entity = $this->makeEntity('pending');
        $entity->approve();
        self::assertSame('approved', $entity->getStatus());
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
        self::assertSame('rejected', $entity->getStatus());
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
        self::assertSame('感谢您的评价', $entity->getAdminReply());
        self::assertNotNull($entity->getReplyTime());
    }

    public function testImages(): void
    {
        $entity = $this->makeEntity();
        $entity->setImages(['img1.jpg', 'img2.jpg']);
        self::assertSame(['img1.jpg', 'img2.jpg'], $entity->getImages());
    }

    public function testIsAnonymous(): void
    {
        $entity = $this->makeEntity();
        $entity->setIsAnonymous(true);
        self::assertTrue($entity->getIsAnonymous());
    }

    public function testToArray(): void
    {
        $entity = $this->makeEntity();
        $arr = $entity->toArray();
        self::assertSame(100, $arr['order_id']);
        self::assertSame(5, $arr['rating']);
        self::assertSame('很好的商品', $arr['content']);
        self::assertSame('pending', $arr['status']);
    }

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
}
