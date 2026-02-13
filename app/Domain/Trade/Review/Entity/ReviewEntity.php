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

namespace App\Domain\Trade\Review\Entity;

use App\Domain\Trade\Review\Contract\ReviewInput;

/**
 * 评价实体.
 */
final class ReviewEntity
{
    private int $id = 0;

    private int $orderId = 0;

    private int $orderItemId = 0;

    private int $productId = 0;

    private int $skuId = 0;

    private int $memberId = 0;

    private int $rating = 0;

    private string $content = '';

    private ?array $images = null;

    private bool $isAnonymous = false;

    private string $status = 'pending';

    private ?string $adminReply = null;

    private ?string $replyTime = null;

    /**
     * 创建评价，验证 rating 和 content.
     */
    public function create(ReviewInput $dto): self
    {
        $rating = $dto->getRating();
        if ($rating === null || $rating < 1 || $rating > 5) {
            throw new \DomainException('评分必须在1到5之间');
        }

        $content = $dto->getContent();
        if ($content === null || trim($content) === '') {
            throw new \DomainException('评价内容不能为空');
        }

        $this->orderId = $dto->getOrderId() ?? 0;
        $this->orderItemId = $dto->getOrderItemId() ?? 0;
        $this->productId = $dto->getProductId() ?? 0;
        $this->skuId = $dto->getSkuId() ?? 0;
        $this->memberId = $dto->getMemberId() ?? 0;
        $this->rating = $rating;
        $this->content = trim($content);
        $this->images = $dto->getImages();
        $this->isAnonymous = $dto->getIsAnonymous() ?? false;
        $this->status = 'pending';

        return $this;
    }

    /**
     * 审核通过 pending → approved.
     */
    public function approve(): self
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('只能审核待审核状态的评价');
        }
        $this->status = 'approved';
        return $this;
    }

    /**
     * 审核拒绝 pending → rejected.
     */
    public function reject(): self
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('只能审核待审核状态的评价');
        }
        $this->status = 'rejected';
        return $this;
    }

    /**
     * 管理员回复.
     */
    public function reply(string $content): self
    {
        $this->adminReply = $content;
        $this->replyTime = date('Y-m-d H:i:s');
        return $this;
    }

    /**
     * 转换为持久化数组.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'order_id' => $this->orderId,
            'order_item_id' => $this->orderItemId,
            'product_id' => $this->productId,
            'sku_id' => $this->skuId,
            'member_id' => $this->memberId,
            'rating' => $this->rating,
            'content' => $this->content,
            'images' => $this->images,
            'is_anonymous' => $this->isAnonymous,
            'status' => $this->status,
            'admin_reply' => $this->adminReply,
            'reply_time' => $this->replyTime,
        ], static fn ($value) => $value !== null);
    }

    // Getters & Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getOrderItemId(): int
    {
        return $this->orderItemId;
    }

    public function setOrderItemId(int $orderItemId): self
    {
        $this->orderItemId = $orderItemId;
        return $this;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function getSkuId(): int
    {
        return $this->skuId;
    }

    public function setSkuId(int $skuId): self
    {
        $this->skuId = $skuId;
        return $this;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setMemberId(int $memberId): self
    {
        $this->memberId = $memberId;
        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): self
    {
        $this->images = $images;
        return $this;
    }

    public function getIsAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function setIsAnonymous(bool $isAnonymous): self
    {
        $this->isAnonymous = $isAnonymous;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAdminReply(): ?string
    {
        return $this->adminReply;
    }

    public function setAdminReply(?string $adminReply): self
    {
        $this->adminReply = $adminReply;
        return $this;
    }

    public function getReplyTime(): ?string
    {
        return $this->replyTime;
    }

    public function setReplyTime(?string $replyTime): self
    {
        $this->replyTime = $replyTime;
        return $this;
    }
}
