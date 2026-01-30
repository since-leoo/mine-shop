<?php

declare(strict_types=1);

namespace App\Domain\Order\Contract;

use App\Domain\Order\Entity\OrderEntity;

interface OrderTypeStrategyInterface
{
    public function type(): string;

    public function validate(OrderEntity $orderEntity): void;

    public function buildDraft(OrderEntity $orderEntity): OrderEntity;

    public function postCreate(OrderEntity $orderEntity): void;
}
