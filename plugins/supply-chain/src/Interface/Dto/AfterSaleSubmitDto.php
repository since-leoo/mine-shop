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

namespace Plugin\SupplyChain\Interface\Dto;

use Plugin\SupplyChain\Domain\Contract\AfterSaleInput;

final readonly class AfterSaleSubmitDto implements AfterSaleInput
{
    public function __construct(private array $data) {}

    public function toArray(): array
    {
        $data = $this->data;
        foreach (['quantity', 'amount_cents', 'freight_cents', 'technical_service_fee_cents', 'reason_type', 'received_status', 'refund_type', 'refund_way'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }
        foreach (['external_order_item_id', 'reason', 'description'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = trim((string) $data[$key]);
            }
        }
        return $data;
    }
}
