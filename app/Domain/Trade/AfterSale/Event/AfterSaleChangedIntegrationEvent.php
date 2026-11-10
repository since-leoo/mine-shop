<?php

declare(strict_types=1);

namespace App\Domain\Trade\AfterSale\Event;

/** 可靠售后事件；业务快照通过 CommerceAfterSaleInterface 读取。 */
final readonly class AfterSaleChangedIntegrationEvent
{
    public function __construct(public int $afterSaleId, public string $action) {}
}
