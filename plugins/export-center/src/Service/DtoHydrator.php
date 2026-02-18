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

namespace Plugin\ExportCenter\Service;

/**
 * DTO 数据填充器.
 *
 * 根据 ExportColumn 注解的元数据，自动从原始数据行中提取值并填充到 DTO 实例。
 * 支持：点号路径取关联字段、枚举映射、除数转换、数组拼接、默认值。
 */
class DtoHydrator
{
    /**
     * 将原始数据行转换为一行导出值数组（按 columns 顺序）。
     *
     * @param array $columns 由 ExportDtoResolver 解析出的列元数据
     * @param mixed $row 原始数据行（Model、stdClass、array）
     * @return array 一行导出值
     */
    public function hydrate(array $columns, mixed $row): array
    {
        $data = $this->toAccessible($row);
        $values = [];

        foreach ($columns as $col) {
            $raw = $this->extractValue($data, $col['field']);

            // 除数转换（如分转元）
            if (! empty($col['divisor']) && $col['divisor'] !== 0 && is_numeric($raw)) {
                $raw /= $col['divisor'];
            }

            // 枚举映射
            if (! empty($col['enum']) && \is_array($col['enum'])) {
                $raw = $col['enum'][$raw] ?? $raw;
            }

            // 数组拼接
            if ($col['glue'] !== null && \is_array($raw)) {
                $raw = implode($col['glue'], $raw);
            }

            // 布尔转中文
            if ($col['type'] === 'boolean') {
                $raw = $raw ? '是' : '否';
            }

            // 默认值
            if ($raw === null || $raw === '') {
                $raw = $col['default'] ?? '';
            }

            // 类型转换
            $values[] = $this->castValue($raw, $col);
        }

        return $values;
    }

    /**
     * 将 Model/对象/数组统一转为可用点号路径访问的结构。
     */
    private function toAccessible(mixed $row): array
    {
        if (\is_array($row)) {
            return $row;
        }
        if (method_exists($row, 'toArray')) {
            return $row->toArray();
        }
        return (array) $row;
    }

    /**
     * 支持点号路径提取嵌套值，如 member.nickname、address.full_address。
     */
    private function extractValue(array $data, string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $data;

        foreach ($keys as $key) {
            if (\is_array($value) && \array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    private function castValue(mixed $value, array $col): mixed
    {
        if ($value === null || $value === '') {
            return $col['default'] ?? '';
        }

        return match ($col['type']) {
            'float' => is_numeric($value) ? round((float) $value, 2) : $value,
            'int' => is_numeric($value) ? (int) $value : $value,
            'date' => $col['format']
                ? (is_numeric($value) ? date($col['format'], (int) $value) : $value)
                : $value,
            default => (string) $value,
        };
    }
}
