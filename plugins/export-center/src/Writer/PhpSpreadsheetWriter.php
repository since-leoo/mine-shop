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

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Plugin\ExportCenter\Contract\ExportWriterInterface;

class PhpSpreadsheetWriter implements ExportWriterInterface
{
    private const BATCH_SIZE = 500;

    public function writeExcel(string $filePath, array $meta, iterable $data, callable $progressCallback, int $maxRowsPerFile = 0): array
    {
        $columns = $meta['columns'];
        $files = [];
        $fileIndex = 0;
        $rowIndex = 2;
        $count = 0;
        $batch = [];

        // 初始化第一个文件
        [$spreadsheet, $sheet] = $this->createSpreadsheet($meta, $columns);

        foreach ($data as $row) {
            // 支持预处理的行值数组（由 DtoHydrator 生成）或原始数据对象
            if (\is_array($row) && array_is_list($row)) {
                $rowValues = $row;
            } else {
                $rowData = \is_array($row) ? $row : (array) $row;
                $rowValues = [];
                foreach ($columns as $col) {
                    $value = $this->extractValue($rowData, $col['property']);
                    $rowValues[] = $this->formatValue($value, $col);
                }
            }
            $batch[] = $rowValues;
            ++$count;

            // 批量写入
            if (\count($batch) >= self::BATCH_SIZE) {
                $sheet->fromArray($batch, null, "A{$rowIndex}");
                $rowIndex += \count($batch);
                $batch = [];
                $progressCallback(min(95, (int) ($count / 100)));
            }

            // 分片检查
            if ($maxRowsPerFile > 0 && ($rowIndex - 2 + \count($batch)) >= $maxRowsPerFile) {
                // 写入剩余 batch
                if (! empty($batch)) {
                    $sheet->fromArray($batch, null, "A{$rowIndex}");
                    $batch = [];
                }
                $path = $this->shardPath($filePath, $fileIndex, '.xlsx');
                (new Xlsx($spreadsheet))->save($path);
                $spreadsheet->disconnectWorksheets();
                $files[] = $path;
                ++$fileIndex;
                // 新建下一个文件
                [$spreadsheet, $sheet] = $this->createSpreadsheet($meta, $columns);
                $rowIndex = 2;
            }
        }

        // 写入最后的 batch
        if (! empty($batch)) {
            $sheet->fromArray($batch, null, "A{$rowIndex}");
        }

        $path = ($fileIndex === 0) ? $filePath : $this->shardPath($filePath, $fileIndex, '.xlsx');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $files[] = $path;

        $progressCallback(100);
        return $files;
    }

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

            if ($count % 500 === 0) {
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

    private function createSpreadsheet(array $meta, array $columns): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($meta['sheet']['name']) {
            $sheet->setTitle($meta['sheet']['name']);
        }
        if ($meta['sheet']['defaultColumnWidth']) {
            $sheet->getDefaultColumnDimension()->setWidth($meta['sheet']['defaultColumnWidth']);
        }

        $headerRow = [];
        foreach ($columns as $colIdx => $col) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $headerRow[] = $col['title'];
            if ($col['width']) {
                $sheet->getColumnDimension($colLetter)->setWidth($col['width']);
            }
        }
        $sheet->fromArray($headerRow, null, 'A1');

        $lastColLetter = Coordinate::stringFromColumnIndex(\count($columns));
        $sheet->getStyle("A1:{$lastColLetter}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColLetter}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($meta['sheet']['freezeHeader']) {
            $sheet->freezePane('A2');
        }

        return [$spreadsheet, $sheet];
    }

    private function openCsv(string $filePath, array $columns): mixed
    {
        $fp = fopen($filePath, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, array_column($columns, 'title'));
        return $fp;
    }

    private function shardPath(string $basePath, int $index, string $ext): string
    {
        $dir = \dirname($basePath);
        $name = pathinfo($basePath, \PATHINFO_FILENAME);
        return $dir . '/' . $name . '_part' . ($index + 1) . $ext;
    }

    private function extractValue(array $data, string $property): mixed
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

    private function formatValue(mixed $value, array $column): mixed
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
