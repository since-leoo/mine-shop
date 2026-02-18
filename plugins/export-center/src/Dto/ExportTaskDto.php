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

namespace Plugin\ExportCenter\Dto;

use Plugin\ExportCenter\Contract\ExportTaskInput;

/**
 * 导出任务 DTO.
 */
class ExportTaskDto implements ExportTaskInput
{
    public function __construct(
        private readonly int $userId,
        private readonly string $taskName,
        private readonly string $dtoClass,
        private readonly string $exportFormat,
        private readonly array $exportParams = [],
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function getDtoClass(): string
    {
        return $this->dtoClass;
    }

    public function getExportFormat(): string
    {
        return $this->exportFormat;
    }

    public function getExportParams(): array
    {
        return $this->exportParams;
    }
}
