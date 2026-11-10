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

use Plugin\SupplyChain\Interfaces\Data\AfterSaleCommand;

/** A frozen, supplier-specific submission; all financial values are integer cents. */
final readonly class AfterSaleSubmissionVo
{
    public function __construct(public array $data)
    {
        foreach (['external_order_item_id', 'merchant_order_no', 'reason'] as $field) {
            if (! \is_string($data[$field] ?? null) || trim($data[$field]) === '') {
                throw new \InvalidArgumentException('采购售后缺少字段：' . $field);
            }
        }
        foreach (['quantity', 'amount_cents', 'freight_cents', 'technical_service_fee_cents', 'reason_type', 'received_status', 'refund_type', 'refund_way'] as $field) {
            if (! \is_int($data[$field] ?? null) || $data[$field] < 0 || $data[$field] > 2147483647) {
                throw new \InvalidArgumentException('采购售后整数参数无效：' . $field);
            }
        }
        if ($data['quantity'] < 1 || $data['refund_type'] === 2) {
            throw new \DomainException('采购售后数量无效或使用了尚未支持的换货类型');
        }
    }

    public function assertLimits(array $customer, array $remoteLine, array $used, int $freightLimit): void
    {
        $quantity = $this->data['quantity'];
        $amount = $this->data['amount_cents'];
        $fee = $this->data['technical_service_fee_cents'];
        $freight = $this->data['freight_cents'];
        $customerAmount = (int) (($customer['refund_amount'] ?? 0) > 0 ? $customer['refund_amount'] : ($customer['apply_amount'] ?? 0));
        if ($quantity > (int) ($customer['quantity'] ?? 0) || $amount + $fee + $freight > $customerAmount) {
            throw new \DomainException('采购售后数量或合计金额超过本次客户售后范围');
        }
        $totalQuantity = (int) $remoteLine['quantity'];
        $remainingQuantity = $totalQuantity - (int) ($used['quantity'] ?? 0);
        $remainingAmount = (int) $remoteLine['amount_cents'] - (int) ($used['amount_cents'] ?? 0);
        $remainingFee = (int) $remoteLine['technical_service_fee_cents'] - (int) ($used['technical_service_fee_cents'] ?? 0);
        if ($totalQuantity < 1 || $quantity > $remainingQuantity || $remainingAmount < 0 || $remainingFee < 0) {
            throw new \DomainException('采购行可售后数量或金额已被其他售后占用');
        }
        // Allocate rounding residue only to the last remaining quantity.
        $amountLimit = $quantity === $remainingQuantity ? $remainingAmount : min($remainingAmount, intdiv((int) $remoteLine['amount_cents'] * $quantity, $totalQuantity));
        $feeLimit = $quantity === $remainingQuantity ? $remainingFee : min($remainingFee, intdiv((int) $remoteLine['technical_service_fee_cents'] * $quantity, $totalQuantity));
        if ($amount > $amountLimit || $fee > $feeLimit || $freight > $freightLimit - (int) ($used['freight_cents'] ?? 0)) {
            throw new \DomainException('采购售后金额超过该行剩余可退金额或运费额度');
        }
    }

    public function assertTypes(array $types): void
    {
        if (! \array_key_exists($this->data['refund_type'], $types) || ! \is_string($types[$this->data['refund_type']])) {
            throw new \DomainException('该采购行当前不支持所选退款类型，或上游政策格式尚未确认');
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
