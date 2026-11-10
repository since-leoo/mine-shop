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

namespace Plugin\SupplyChain\Handler\Yunzhong;

use Plugin\SupplyChain\Interfaces\Data\CheckoutCommand;
use Plugin\SupplyChain\Interfaces\Data\PurchaseLine;

final class PayloadMapper
{
    public static function checkout(CheckoutCommand $command): array
    {
        return ['spu' => array_map(static fn (PurchaseLine $line): array => [
            'sku' => self::id($line->externalSkuId), 'number' => $line->quantity,
        ], $command->lines), 'address' => [
            'consignee' => $command->address->consignee, 'phone' => $command->address->phone,
            'province' => $command->address->province, 'city' => $command->address->city,
            'area' => $command->address->area, 'street' => $command->address->street,
            'description' => $command->address->description,
        ]];
    }

    public static function id(int|string $value): int
    {
        if (! preg_match('/^[1-9][0-9]*$/', (string) $value) || mb_strlen((string) $value) > mb_strlen((string) \PHP_INT_MAX)
            || (mb_strlen((string) $value) === mb_strlen((string) \PHP_INT_MAX) && strcmp((string) $value, (string) \PHP_INT_MAX) > 0)) {
            throw new \InvalidArgumentException('芸众外部 ID 必须为有效正整数');
        }
        return (int) $value;
    }

    public static function ids(array $ids, int $max = 50): array
    {
        if ($ids === [] || \count($ids) > $max) {
            throw new \InvalidArgumentException('供应链批次大小必须为 1 至 ' . $max);
        }
        return array_values(array_unique(array_map(self::id(...), $ids)));
    }

    public static function integer(mixed $value, string $field): int
    {
        if ((! \is_int($value) && ! \is_string($value)) || ! preg_match('/^[0-9]+$/', (string) $value)
            || mb_strlen((string) $value) > mb_strlen((string) \PHP_INT_MAX)
            || (mb_strlen((string) $value) === mb_strlen((string) \PHP_INT_MAX) && strcmp((string) $value, (string) \PHP_INT_MAX) > 0)) {
            throw new \RuntimeException('供应链响应字段不是非负整数：' . $field);
        }
        return (int) $value;
    }

    public static function list(mixed $data, string $key = 'list'): array
    {
        if (! \is_array($data) || ! isset($data[$key]) || ! \is_array($data[$key]) || ! array_is_list($data[$key])) {
            throw new \RuntimeException('供应链响应缺少列表字段：' . $key);
        }
        return $data[$key];
    }
}
