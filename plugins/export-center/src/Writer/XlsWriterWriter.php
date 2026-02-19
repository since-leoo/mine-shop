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

use Vtiful\Kernel\Excel;
use Vtiful\Kernel\Format;

class XlsWriterWriter extends AbstractExportWriter
{
    public function writeExcel(string $filePath, array $meta, iterable $data, callable $progressCallback, int $maxRowsPerFile = 0): array
    {
        $columns = $meta['columns'];
        $files = [];
        $fileIndex = 0;
        $rowCount = 0;
        $count = 0;
        $batch = [];

        $excel = $this->createExcel($filePath, $meta, $columns);

        foreach ($data as $row) {
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
            ++$rowCount;
            ++$count;

            if (\count($batch) >= self::BATCH_SIZE) {
                $excel->data($batch);
                $batch = [];
                $progressCallback(min(95, (int) ($count / 100)));
            }

            // 分片
            if ($maxRowsPerFile > 0 && $rowCount >= $maxRowsPerFile) {
                if (! empty($batch)) {
                    $excel->data($batch);
                    $batch = [];
                }
                $excel->output();
                $path = $this->shardPath($filePath, $fileIndex, '.xlsx');
                rename($filePath, $path);
                $files[] = $path;
                ++$fileIndex;
                $excel = $this->createExcel($filePath, $meta, $columns);
                $rowCount = 0;
            }
        }

        if (! empty($batch)) {
            $excel->data($batch);
        }
        $excel->output();

        if ($fileIndex === 0) {
            $files[] = $filePath;
        } else {
            $path = $this->shardPath($filePath, $fileIndex, '.xlsx');
            rename($filePath, $path);
            $files[] = $path;
        }

        $progressCallback(100);
        return $files;
    }

    private function createExcel(string $filePath, array $meta, array $columns): Excel
    {
        $dir = \dirname($filePath);
        $filename = basename($filePath);

        $excel = new Excel(['path' => $dir]);
        $sheetName = $meta['sheet']['name'] ?? 'Sheet1';
        $excel->fileName($filename, $sheetName);

        $headerFormat = (new Format($excel->getHandle()))
            ->bold()
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        if ($meta['sheet']['freezeHeader']) {
            $excel->freezePanes(1, 0);
        }

        foreach ($columns as $colIdx => $col) {
            $colLetter = $this->columnLetter($colIdx);
            if ($col['width']) {
                $excel->setColumn("{$colLetter}:{$colLetter}", $col['width']);
            } elseif ($meta['sheet']['defaultColumnWidth']) {
                $excel->setColumn("{$colLetter}:{$colLetter}", $meta['sheet']['defaultColumnWidth']);
            }
        }

        $excel->header(array_column($columns, 'title'), $headerFormat);
        return $excel;
    }

    /**
     * 将 0-based 列索引转为 Excel 列字母（0→A, 1→B, 25→Z, 26→AA）.
     */
    private function columnLetter(int $index): string
    {
        $letter = '';
        ++$index;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = \chr(65 + $mod) . $letter;
            $index = (int) (($index - $mod) / 26);
        }
        return $letter;
    }
}
