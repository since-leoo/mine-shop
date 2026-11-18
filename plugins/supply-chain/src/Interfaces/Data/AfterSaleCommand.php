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

namespace Plugin\SupplyChain\Interfaces\Data;

use App\Infrastructure\Error\SupplyChainError;

/** Enum values must come from the provider's actual after-sales policy, never local enum guesses. */
final readonly class AfterSaleCommand
{
    public function __construct(
        public string $merchantOrderNo,
        public string $externalOrderItemId,
        public int $quantity,
        public int $amountCents,
        public int $freightCents,
        public int $technicalServiceFeeCents,
        public int $reasonType,
        public string $reason,
        public string $description,
        public int $receivedStatus,
        public int $refundType,
        public int $refundWay,
        public array $images = [],
    ) {
        if ($merchantOrderNo === '' || $externalOrderItemId === '' || $quantity < 1
            || min($amountCents, $freightCents, $technicalServiceFeeCents) < 0) {
            throw SupplyChainError::AFTER_SALE_PARAMETERS_INVALID->invalid();
        }
        if ($refundType === 2) {
            throw SupplyChainError::EXCHANGE_UNSUPPORTED->exception();
        }
    }
}
