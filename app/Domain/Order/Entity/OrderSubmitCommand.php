<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

final class OrderSubmitCommand
{
    private int $memberId = 0;

    private string $orderType = 'normal';

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $items = [];

    /**
     * @var array<string, mixed>
     */
    private array $address = [];

    /**
     * @var array<int, int>
     */
    private array $couponIds = [];

    private string $buyerRemark = '';

    /**
     * @var array<string, mixed>
     */
    private array $extra = [];

    public function setMemberId(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setOrderType(string $orderType): void
    {
        $this->orderType = $orderType;
    }

    public function getOrderType(): string
    {
        return $this->orderType;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function setItems(array $items): void
    {
        $this->items = array_values($items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<string, mixed> $address
     */
    public function setAddress(array $address): void
    {
        $this->address = $address;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAddress(): array
    {
        return $this->address;
    }

    /**
     * @param array<int, int> $couponIds
     */
    public function setCouponIds(array $couponIds): void
    {
        $this->couponIds = $couponIds;
    }

    /**
     * @return array<int, int>
     */
    public function getCouponIds(): array
    {
        return $this->couponIds;
    }

    public function setBuyerRemark(string $buyerRemark): void
    {
        $this->buyerRemark = $buyerRemark;
    }

    public function getBuyerRemark(): string
    {
        return $this->buyerRemark;
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function setExtra(array $extra): void
    {
        $this->extra = $extra;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}
