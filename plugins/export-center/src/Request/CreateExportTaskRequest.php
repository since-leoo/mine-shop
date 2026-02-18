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

namespace Plugin\ExportCenter\Request;

use App\Interface\Common\Request\BaseRequest;
use Plugin\ExportCenter\Contract\ExportTaskInput;
use Plugin\ExportCenter\Dto\ExportTaskDto;

/**
 * 创建导出任务请求
 */
class CreateExportTaskRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'task_name' => ['required', 'string', 'max:255'],
            'dto_class' => ['required', 'string'],
            'export_format' => ['required', 'in:excel,csv'],
            'export_params' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'task_name' => '任务名称',
            'dto_class' => 'DTO类名',
            'export_format' => '导出格式',
            'export_params' => '导出参数',
        ];
    }

    /**
     * 转换为 DTO.
     */
    public function toDto(int $userId): ExportTaskInput
    {
        $validated = $this->validated();

        return new ExportTaskDto(
            userId: $userId,
            taskName: $validated['task_name'],
            dtoClass: $validated['dto_class'],
            exportFormat: $validated['export_format'],
            exportParams: $validated['export_params'] ?? [],
        );
    }
}
