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

namespace Plugin\SupplyChain\Domain\ValueObject;

use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Interfaces\Data\AfterSaleCommand;

/** A frozen, supplier-specific submission; all financial values are integer cents. */
final readonly class AfterSaleSubmissionVo
{
    public function __construct(public array $data)
    {
        foreach (['external_order_item_id', 'merchant_order_no', 'reason'] as $field) {
            if (! \is_string($data[$field] ?? null) || trim($data[$field]) === '') {
                throw SupplyChainError::AFTER_SALE_FIELD_REQUIRED->invalid([$field]);
            }
        }
        foreach (['quantity', 'amount_cents', 'freight_cents', 'technical_service_fee_cents', 'reason_type', 'received_status', 'refund_type', 'refund_way'] as $field) {
            if (! \is_int($data[$field] ?? null) || $data[$field] < 0 || $data[$field] > 2147483647) {
                throw SupplyChainError::AFTER_SALE_INTEGER_INVALID->invalid([$field]);
            }
        }
        if ($data['quantity'] < 1 || $data['refund_type'] === 2) {
            throw SupplyChainError::AFTER_SALE_QUANTITY_OR_EXCHANGE_INVALID->exception();
        }
    }

    public function assertLimits(array $customer, array $remoteLine, array $used, int $freightLimit): void
    {
        $quantity = (int) $this->data['quantity'];
        $amount = (int) $this->data['amount_cents'];
        $fee = (int) $this->data['technical_service_fee_cents'];
        $freight = (int) $this->data['freight_cents'];
        $customerAmount = (int) (($customer['refund_amount'] ?? 0) > 0 ? $customer['refund_amount'] : ($customer['apply_amount'] ?? 0));
        if ($quantity > (int) ($customer['quantity'] ?? 0) || $amount + $fee + $freight > $customerAmount) {
            throw SupplyChainError::AFTER_SALE_CUSTOMER_LIMIT_EXCEEDED->exception();
        }
        $totalQuantity = (int) $remoteLine['quantity'];
        $remainingQuantity = $totalQuantity - (int) ($used['quantity'] ?? 0);
        $remainingAmount = (int) $remoteLine['amount_cents'] - (int) ($used['amount_cents'] ?? 0);
        $remainingFee = (int) $remoteLine['technical_service_fee_cents'] - (int) ($used['technical_service_fee_cents'] ?? 0);
        if ($totalQuantity < 1 || $quantity > $remainingQuantity || $remainingAmount < 0 || $remainingFee < 0) {
            throw SupplyChainError::AFTER_SALE_CAPACITY_RESERVED->exception();
        }
        // Allocate rounding residue only to the last remaining quantity.
        $amountLimit = $quantity === $remainingQuantity ? $remainingAmount : min($remainingAmount, intdiv((int) $remoteLine['amount_cents'] * $quantity, $totalQuantity));
        $feeLimit = $quantity === $remainingQuantity ? $remainingFee : min($remainingFee, intdiv((int) $remoteLine['technical_service_fee_cents'] * $quantity, $totalQuantity));
        if ($amount > $amountLimit || $fee > $feeLimit || $freight > $freightLimit - (int) ($used['freight_cents'] ?? 0)) {
            throw SupplyChainError::AFTER_SALE_REFUND_LIMIT_EXCEEDED->exception();
        }
    }

    public function assertTypes(array $types): void
    {
        if (! \array_key_exists($this->data['refund_type'], $types) || ! \is_string($types[$this->data['refund_type']])) {
            throw SupplyChainError::REFUND_TYPE_UNSUPPORTED->exception();
        }
    }

    public function toCommand(): AfterSaleCommand
    {
        return new AfterSaleCommand(
            $this->data['merchant_order_no'],
            $this->data['external_order_item_id'],
            $this->data['quantity'],
            $this->data['amount_cents'],
            $this->data['freight_cents'],
            $this->data['technical_service_fee_cents'],
            $this->data['reason_type'],
            $this->data['reason'],
            (string) ($this->data['description'] ?? ''),
            $this->data['received_status'],
            $this->data['refund_type'],
            $this->data['refund_way'],
            $this->data['images'] ?? []
        );
    }
}
