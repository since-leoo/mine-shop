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

use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

class ExportDtoResolver
{
    /**
     * 内存缓存，避免同一请求内重复反射解析.
     */
    private array $cache = [];

    /**
     * 解析 DTO 类上的 ExportColumn 和 ExportSheet 注解，生成列元数据。
     *
     * @param string $dtoClass DTO 类的完整类名
     * @return array{
     *   sheet: array{name: ?string, description: ?string, freezeHeader: bool, defaultColumnWidth: ?float},
     *   columns: array<int, array{
     *     title: string,
     *     property: string,
     *     type: string,
     *     order: int,
     *     width: ?float,
     *     format: ?string,
     *     arrayColumns: array,
     *     style: array{align: string, bold: bool, fontColor: ?string, bgColor: ?string, wrapText: bool}
     *   }>
     * }
     * @throws \InvalidArgumentException 当 DTO 类不包含任何 ExportColumn 注解时
     */
    public function resolve(string $dtoClass): array
    {
        if (isset($this->cache[$dtoClass])) {
            return $this->cache[$dtoClass];
        }

        return $this->cache[$dtoClass] = $this->doResolve($dtoClass);
    }

    /**
     * 实际解析逻辑.
     */
    private function doResolve(string $dtoClass): array
    {
        $refClass = new \ReflectionClass($dtoClass);

        // 解析 ExportSheet 注解
        $sheetMeta = $this->resolveSheetAttribute($refClass);

        // 解析 ExportColumn 注解
        $columns = [];
        foreach ($refClass->getProperties() as $property) {
            $attrs = $property->getAttributes(ExportColumn::class);
            if (empty($attrs)) {
                continue;
            }

            /** @var ExportColumn $annotation */
            $annotation = $attrs[0]->newInstance();

            $column = [
                'title' => $annotation->title,
                'property' => $property->getName(),
                'field' => $annotation->field ?? $property->getName(),
                'type' => $annotation->type,
                'order' => $annotation->order,
                'width' => $annotation->width,
                'format' => $annotation->format,
                'enum' => $annotation->enum,
                'divisor' => $annotation->divisor,
                'default' => $annotation->default,
                'glue' => $annotation->glue,
                'arrayColumns' => $annotation->arrayColumns,
                'style' => [
                    'align' => $annotation->align,
                    'bold' => $annotation->bold,
                    'fontColor' => $annotation->fontColor,
                    'bgColor' => $annotation->bgColor,
                    'wrapText' => $annotation->wrapText,
                ],
            ];

            // 数组字段展开为子列
            if (! empty($annotation->arrayColumns)) {
                $subColumns = $this->expandArrayColumns($column);
                foreach ($subColumns as $sub) {
                    $columns[] = $sub;
                }
            } else {
                $columns[] = $column;
            }
        }

        if (empty($columns)) {
            throw new \InvalidArgumentException(
                "DTO 类 {$dtoClass} 未定义任何 #[ExportColumn] 注解"
            );
        }

        // 按 order 排序
        usort($columns, static fn ($a, $b) => $a['order'] <=> $b['order']);

        return [
            'sheet' => $sheetMeta,
            'columns' => $columns,
        ];
    }

    private function resolveSheetAttribute(\ReflectionClass $refClass): array
    {
        $attrs = $refClass->getAttributes(ExportSheet::class);
        if (! empty($attrs)) {
            /** @var ExportSheet $sheet */
            $sheet = $attrs[0]->newInstance();
            return [
                'name' => $sheet->name,
                'description' => $sheet->description,
                'freezeHeader' => $sheet->freezeHeader,
                'defaultColumnWidth' => $sheet->defaultColumnWidth,
                'dataProvider' => $sheet->dataProvider,
            ];
        }

        return [
            'name' => null,
            'description' => null,
            'freezeHeader' => true,
            'defaultColumnWidth' => null,
            'dataProvider' => null,
        ];
    }

    /**
     * 展开数组字段为多个子列.
     */
    private function expandArrayColumns(array $parentColumn): array
    {
        $subColumns = [];
        $baseOrder = $parentColumn['order'];

        foreach ($parentColumn['arrayColumns'] as $index => $subDef) {
            $subColumns[] = [
                'title' => $subDef['title'] ?? $parentColumn['title'] . '.' . ($subDef['key'] ?? $index),
                'property' => $parentColumn['property'] . '.' . ($subDef['key'] ?? $index),
                'field' => $parentColumn['field'] . '.' . ($subDef['key'] ?? $index),
                'type' => $subDef['type'] ?? 'string',
                'order' => $baseOrder + $index + 1,
                'width' => $subDef['width'] ?? $parentColumn['width'],
                'format' => $subDef['format'] ?? null,
                'enum' => [],
                'divisor' => 0,
                'default' => null,
                'glue' => null,
                'arrayColumns' => [],
                'style' => $parentColumn['style'],
            ];
        }

        return $subColumns;
    }
}
