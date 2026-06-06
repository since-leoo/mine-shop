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

use App\Domain\Content\DiyPage\Contract\DiyTemplateApplyInput;
use App\Domain\Content\DiyPage\Contract\DiyTemplateInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Interface\Admin\Dto\DiyPage\DiyTemplateApplyDto;
use App\Interface\Admin\Dto\DiyPage\DiyTemplateDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

final class DiyTemplateRequest extends BaseRequest
{
    public function listRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'page_key' => ['nullable', 'string', 'max:64'],
            'page_type' => ['nullable', Rule::in(DiyPageStatus::pageTypes())],
            'is_enabled' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function storeRules(): array
    {
        return $this->templateRules();
    }

    public function updateRules(): array
    {
        return $this->templateRules();
    }

    public function enableRules(): array
    {
        return [];
    }

    public function disableRules(): array
    {
        return [];
    }

    public function applyRules(): array
    {
        return [
            'page_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function saveAsTemplateRules(): array
    {
        return $this->templateRules();
    }

    public function attributes(): array
    {
        return [
            'category_id' => '模板分类',
            'name' => '模板名称',
            'page_key' => '页面键',
            'page_type' => '页面类型',
            'cover' => '模板封面',
            'description' => '模板说明',
            'schema' => '模板结构',
            'sort' => '排序',
            'is_enabled' => '启用状态',
            'template_id' => '模板ID',
            'page_id' => '页面ID',
        ];
    }

    public function toDto(): DiyTemplateInput
    {
        return Mapper::map($this->validated(), new DiyTemplateDto());
    }

    public function toApplyDto(int $templateId): DiyTemplateApplyInput
    {
        return Mapper::map($this->validated() + ['template_id' => $templateId], new DiyTemplateApplyDto($templateId));
    }

    private function templateRules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:100'],
            'page_key' => ['required', 'string', 'max:64'],
            'page_type' => ['required', Rule::in(DiyPageStatus::pageTypes())],
            'cover' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'schema' => ['required', 'array'],
            'schema.version' => ['required', 'integer', 'min:1'],
            'schema.page' => ['required', 'array'],
            'schema.page.key' => ['required', 'string', 'max:64'],
            'schema.components' => ['required', 'array', 'max:50'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
