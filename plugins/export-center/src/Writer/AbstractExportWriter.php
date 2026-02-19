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

namespace Plugin\ExportCenter\Writer;

use Plugin\ExportCenter\Contract\ExportWriterInterface;

/**
 * 导出写入器抽象基类.
 *
 * 提供 CSV 写入、值提取、格式化等通用方法。
 */
abstract class AbstractExportWriter implements ExportWriterInterface
{
    protected const BATCH_SIZE = 500;

    public function writeCsv(string $filePath, array $meta, iterable $data, callable $progressCallback, int $maxRowsPerFile = 0): array
    {
        $columns = $meta['columns'];
        $files = [];
        $fileIndex = 0;
        $rowCount = 0;
        $count = 0;

        $fp = $this->openCsv($filePath, $columns);

        foreach ($data as $row) {
            if (\is_array($row) && array_is_list($row)) {
                $csvRow = $row;
            } else {
                $rowData = \is_array($row) ? $row : (array) $row;
                $csvRow = [];
                foreach ($columns as $col) {
                    $value = $this->extractValue($rowData, $col['property']);
                    $csvRow[] = $this->formatValue($value, $col);
                }
            }
            fputcsv($fp, $csvRow);
            ++$rowCount;
            ++$count;

            if ($count % self::BATCH_SIZE === 0) {
                $progressCallback(min(95, (int) ($count / 100)));
            }

            // 分片
            if ($maxRowsPerFile > 0 && $rowCount >= $maxRowsPerFile) {
                fclose($fp);
                $path = $this->shardPath($filePath, $fileIndex, '.csv');
                rename($filePath, $path);
                $files[] = $path;
                ++$fileIndex;
                $fp = $this->openCsv($filePath, $columns);
                $rowCount = 0;
            }
        }

        fclose($fp);
        if ($fileIndex === 0) {
            $files[] = $filePath;
        } else {
            $path = $this->shardPath($filePath, $fileIndex, '.csv');
            rename($filePath, $path);
            $files[] = $path;
        }

        $progressCallback(100);
        return $files;
    }

    /**
     * 打开 CSV 文件并写入表头.
     *
     * @return resource
     */
    protected function openCsv(string $filePath, array $columns): mixed
    {
        $fp = fopen($filePath, 'w');
        fwrite($fp, "\xEF\xBB\xBF"); // UTF-8 BOM
        fputcsv($fp, array_column($columns, 'title'));
        return $fp;
    }

    /**
     * 生成分片文件路径.
     */
    protected function shardPath(string $basePath, int $index, string $ext): string
    {
        $dir = \dirname($basePath);
        $name = pathinfo($basePath, \PATHINFO_FILENAME);
        return $dir . '/' . $name . '_part' . ($index + 1) . $ext;
    }

    /**
     * 支持点号路径提取嵌套值.
     */
    protected function extractValue(array $data, string $property): mixed
    {
        $keys = explode('.', $property);
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

    /**
     * 根据列配置格式化值.
     */
    protected function formatValue(mixed $value, array $column): mixed
    {
        if ($value === null) {
            return '';
        }
        return match ($column['type']) {
            'date' => $column['format']
                ? (is_numeric($value) ? date($column['format'], (int) $value) : $value)
                : $value,
            'boolean' => $value ? '是' : '否',
            'float' => is_numeric($value) ? (float) $value : $value,
            'int' => is_numeric($value) ? (int) $value : $value,
            default => (string) $value,
        };
    }
}
