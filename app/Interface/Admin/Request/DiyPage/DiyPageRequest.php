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

use App\Domain\Content\DiyPage\Contract\DiyPageDraftInput;
use App\Domain\Content\DiyPage\Contract\DiyPageInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Interface\Admin\Dto\DiyPage\DiyPageDraftDto;
use App\Interface\Admin\Dto\DiyPage\DiyPageDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

final class DiyPageRequest extends BaseRequest
{
    public function listRules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'page_key' => ['nullable', 'string', 'max:64'],
            'page_type' => ['nullable', Rule::in(DiyPageStatus::pageTypes())],
            'is_enabled' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in([
                DiyPageStatus::PAGE_DRAFT,
                DiyPageStatus::PAGE_PUBLISHED,
                DiyPageStatus::PAGE_DISABLED,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function storeRules(): array
    {
        return $this->pageRules();
    }

    public function updateRules(): array
    {
        return $this->pageRules();
    }

    public function draftRules(): array
    {
        return $this->schemaRules();
    }

    public function publishRules(): array
    {
        return [];
    }

    public function enableRules(): array
    {
        return [];
    }

    public function disableRules(): array
    {
        return [];
    }

    public function copyRules(): array
    {
        return [];
    }

    public function resetRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'page_key' => '页面键',
            'page_type' => '页面类型',
            'title' => '页面名称',
            'description' => '页面说明',
            'schema' => '页面结构',
            'schema.version' => '页面结构版本',
            'schema.page' => '页面基础信息',
            'schema.components' => '装修组件',
        ];
    }

    public function toDto(): DiyPageInput
    {
        return Mapper::map($this->validated(), new DiyPageDto());
    }

    public function toDraftDto(): DiyPageDraftInput
    {
        return Mapper::map($this->validated(), new DiyPageDraftDto());
    }

    private function pageRules(): array
    {
        return [
            'page_key' => ['required', 'string', 'max:64'],
            'page_type' => ['required', Rule::in(DiyPageStatus::pageTypes())],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function schemaRules(): array
    {
        return [
            'schema' => ['required', 'array'],
            'schema.version' => ['required', 'integer', 'min:1'],
            'schema.page' => ['required', 'array'],
            'schema.page.key' => ['required', 'string', 'max:64'],
            'schema.page.title' => ['nullable', 'string', 'max:100'],
            'schema.components' => ['required', 'array', 'max:50'],
            'schema.components.*.id' => ['required', 'string', 'max:64'],
            'schema.components.*.type' => ['required', 'string', 'max:64'],
            'schema.components.*.name' => ['nullable', 'string', 'max:100'],
            'schema.components.*.enabled' => ['nullable', 'boolean'],
            'schema.components.*.props' => ['nullable', 'array'],
            'schema.components.*.style' => ['nullable', 'array'],
            'schema.components.*.data' => ['nullable', 'array'],
        ];
    }
}
