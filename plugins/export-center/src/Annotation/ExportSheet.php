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

#[\Attribute(\Attribute::TARGET_CLASS)]
class ExportSheet
{
    /**
     * @param null|string $name 工作表名称（可选，默认 Sheet1）
     * @param null|string $description 描述（可选）
     * @param bool $freezeHeader 是否冻结表头行（默认 true）
     * @param null|float $defaultColumnWidth 默认列宽（可选）
     * @param null|array $dataProvider 数据提供者 [ServiceClass::class, 'methodName']，由 DI 容器解析
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly bool $freezeHeader = true,
        public readonly ?float $defaultColumnWidth = null,
        public readonly ?array $dataProvider = null,
    ) {}
}
