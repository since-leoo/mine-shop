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

namespace App\Interface\Admin\Request\DiyPage;

use App\Domain\Content\DiyPage\Contract\DiyPublishScheduleInput;
use App\Interface\Admin\Dto\DiyPage\DiyPublishScheduleDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;

final class DiyPublishRequest extends BaseRequest
{
    public function publishRecordsRules(): array
    {
        return [];
    }

    public function schedulePublishRules(): array
    {
        return [
            'version_id' => ['required', 'integer', 'min:1'],
            'scheduled_at' => ['required', 'date'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function cancelScheduleRules(): array
    {
        return [
            'record_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function rollbackRules(): array
    {
        return [
            'version_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function previewTokenRules(): array
    {
        return [
            'version_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'version_id' => '版本ID',
            'scheduled_at' => '定时发布时间',
            'remark' => '备注',
            'record_id' => '发布记录ID',
        ];
    }

    public function toScheduleDto(int $pageId): DiyPublishScheduleInput
    {
        return Mapper::map($this->validated() + ['page_id' => $pageId], new DiyPublishScheduleDto($pageId));
    }
}
