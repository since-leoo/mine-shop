<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Mapper;

/** 将订单明细快照映射为导出数据，不改变持久化的规格结构。 */
final class OrderExportMapper
{
    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public static function toRow(array $order, array $item): array
    {
        $row = array_merge($order, $item);
        $specs = $item['spec_values'] ?? [];
        $values = \is_array($specs) ? array_values($specs) : [$specs];
        $row['spec_values'] = [];

        foreach ($values as $value) {
            if (\is_array($value)) {
                $value = $value['value'] ?? $value['value_name'] ?? $value['valueName'] ?? $value['spec_value'] ?? $value['specValue'] ?? null;
            }
            if (! \is_string($value) && ! \is_int($value) && ! \is_float($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $row['spec_values'][] = $value;
            }
        }

        return $row;
    }
}
