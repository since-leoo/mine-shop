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

namespace App\Interface\Admin\Request\Product;

use App\Domain\Product\Contract\BrandInput;
use App\Interface\Admin\DTO\Product\BrandDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;
use Hyperf\Validation\Rules\Unique;

class BrandRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function sortRules(): array
    {
        return [
            'sort_data' => ['required', 'array', 'min:1'],
            'sort_data.*.id' => ['required', 'integer', 'min:1'],
            'sort_data.*.sort' => ['required', 'integer', 'min:0'],
        ];
    }

    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule()],
            'logo' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule((int) $this->route('id'))],
            'logo' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '品牌名称',
            'logo' => '品牌Logo',
            'description' => '品牌描述',
            'website' => '官方网站',
            'sort' => '排序',
            'status' => '状态',
        ];
    }

    /**
     * 转换为 DTO.
     */
    public function toDto(?int $id): BrandInput
    {
        $params = $this->validated();
        $params['id'] = $id;

        return Mapper::map($params, new BrandDto());
    }

    private function uniqueNameRule(?int $ignoreId = null): Unique
    {
        $rule = Rule::unique('brands', 'name');
        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }
}
