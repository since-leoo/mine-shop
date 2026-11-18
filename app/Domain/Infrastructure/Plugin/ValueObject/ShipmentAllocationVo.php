<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\ValueObject;

use App\Infrastructure\Error\CommerceError;

/** 包裹数量不变量与幂等参数摘要；不依赖数据库，便于独立验证。 */
final readonly class ShipmentAllocationVo
{
    public array $quantities;
    public string $digest;
    public string $status;

    public function __construct(array $orderItems, array $alreadyShipped, string $provider, array $package, array $items)
    {
        if (trim((string) ($package['shipping_company'] ?? '')) === '' || trim((string) ($package['shipping_no'] ?? '')) === '' || $items === []) {
            throw CommerceError::SHIPMENT_DETAILS_REQUIRED->invalid();
        }
        $known = array_column($orderItems, null, 'id');
        $quantities = [];
        foreach ($items as $item) {
            $id = (int) ($item['order_item_id'] ?? 0);
            $quantity = $item['quantity'] ?? 0;
            if (! is_int($quantity) || $quantity <= 0 || ! isset($known[$id])) {
                throw CommerceError::SHIPMENT_ITEM_INVALID->exception();
            }
            if (($known[$id]['fulfillment_provider'] ?? 'merchant') !== $provider) {
                throw CommerceError::SHIPMENT_PROVIDER_MISMATCH->exception();
            }
            $quantities[$id] = ($quantities[$id] ?? 0) + $quantity;
        }
        ksort($quantities);
        foreach ($quantities as $id => $quantity) {
            if ($quantity + (int) ($alreadyShipped[$id] ?? 0) > (int) $known[$id]['quantity']) {
                throw CommerceError::SHIPMENT_QUANTITY_EXCEEDED->exception();
            }
        }
        $allShipped = true;
        foreach ($known as $id => $item) {
            if ((int) ($alreadyShipped[$id] ?? 0) + ($quantities[$id] ?? 0) < (int) $item['quantity']) {
                $allShipped = false;
            }
        }
        $this->quantities = $quantities;
        $this->status = $allShipped ? 'shipped' : 'partial_shipped';
        $this->digest = self::digest($package, $quantities);
    }

    public static function digest(array $package, array $quantities): string
    {
        ksort($quantities);
        return hash('sha256', json_encode([
            trim((string) ($package['shipping_company'] ?? '')),
            trim((string) ($package['shipping_no'] ?? '')),
            $quantities,
        ], JSON_THROW_ON_ERROR));
    }
}
