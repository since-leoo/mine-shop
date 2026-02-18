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

namespace Plugin\ExportCenter\Annotation;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ExportColumn
{
    /**
     * @param string $title 表头名称（必填）
     * @param string $type 字段类型：string|int|float|date|boolean（默认 string）
     * @param int $order 排序位置（默认 0）
     * @param null|float $width 列宽（可选，null 表示自动）
     * @param null|string $format 格式化模板（如日期 Y-m-d，数值 #,##0.00）
     * @param null|string $field 数据源字段路径，支持点号访问关联（如 member.nickname），为 null 时使用属性名
     * @param array $enum 枚举映射 ['pending' => '待付款', 'paid' => '已付款']，原始值自动转为标签
     * @param float|int $divisor 除数，用于金额分转元等场景（如 100），0 表示不转换
     * @param null|string $default 默认值，当数据源值为 null 时使用
     * @param null|string $glue 数组拼接符，当值为数组时用此分隔符拼接为字符串
     * @param array $arrayColumns 数组字段的嵌套列定义 [['title' => '子列名', 'key' => '子键名', 'type' => 'string']]
     * @param string $align 对齐方式：left|center|right（默认 left）
     * @param bool $bold 是否加粗（默认 false）
     * @param null|string $fontColor 字体颜色，十六进制如 FF0000（可选）
     * @param null|string $bgColor 背景颜色，十六进制如 FFFF00（可选）
     * @param bool $wrapText 是否自动换行（默认 false）
     */
    public function __construct(
        public readonly string $title,
        public readonly string $type = 'string',
        public readonly int $order = 0,
        public readonly ?float $width = null,
        public readonly ?string $format = null,
        public readonly ?string $field = null,
        public readonly array $enum = [],
        public readonly float|int $divisor = 0,
        public readonly ?string $default = null,
        public readonly ?string $glue = null,
        public readonly array $arrayColumns = [],
        public readonly string $align = 'left',
        public readonly bool $bold = false,
        public readonly ?string $fontColor = null,
        public readonly ?string $bgColor = null,
        public readonly bool $wrapText = false,
    ) {}
}
