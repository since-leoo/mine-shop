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

namespace Plugin\ExportCenter\Contract;

/**
 * Excel 写入器接口.
 *
 * 抽象不同 Excel 扩展（xlswriter、PhpSpreadsheet）的写入操作。
 * 支持自动分片：当数据量超过 maxRowsPerFile 时拆分为多个文件。
 */
interface ExportWriterInterface
{
    /**
     * 写入 Excel 文件（支持自动分片）.
     *
     * @param string $filePath 输出文件路径（不含扩展名时自动追加）
     * @param array $meta 由 ExportDtoResolver 解析出的列/工作表元数据
     * @param iterable $data 数据迭代器
     * @param callable $progressCallback 进度回调
     * @param int $maxRowsPerFile 单文件最大行数，0 表示不限制
     * @return string[] 生成的文件路径数组（单文件时为 1 个元素，分片时为多个）
     */
    public function writeExcel(string $filePath, array $meta, iterable $data, callable $progressCallback, int $maxRowsPerFile = 0): array;

    /**
     * 写入 CSV 文件（支持自动分片）.
     *
     * @param string $filePath 输出文件路径
     * @param array $meta 列元数据
     * @param iterable $data 数据迭代器
     * @param callable $progressCallback 进度回调
     * @param int $maxRowsPerFile 单文件最大行数，0 表示不限制
     * @return string[] 生成的文件路径数组
     */
    public function writeCsv(string $filePath, array $meta, iterable $data, callable $progressCallback, int $maxRowsPerFile = 0): array;
}
