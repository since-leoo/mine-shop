<?php

declare(strict_types=1);

namespace App\Domain\Order\Contract;

use App\Domain\Order\Entity\OrderDraftEntity;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Entity\OrderSubmitCommand;

interface OrderTypeStrategyInterface
{
    public function type(): string;

    public function validate(OrderSubmitCommand $command): void;

    public function buildDraft(OrderSubmitCommand $command): OrderDraftEntity;

    public function postCreate(OrderEntity $order, OrderDraftEntity $draft): void;
}
