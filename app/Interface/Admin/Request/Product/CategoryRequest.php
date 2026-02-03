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

use App\Domain\Product\Repository\CategoryRepository;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Rule;
use Hyperf\Validation\Rules\Unique;

class CategoryRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'parent_id' => ['nullable', 'integer', 'min:0'],
            'level' => ['nullable', 'integer', 'min:1', 'max:3'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function treeRules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'min:0'],
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

    public function moveRules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function optionsRules(): array
    {
        return [
            'exclude_id' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function storeRules(): array
    {
        return $this->baseRules();
    }

    public function updateRules(): array
    {
        return $this->baseRules((int) $this->route('id'));
    }

    public function attributes(): array
    {
        return [
            'name' => '分类名称',
            'parent_id' => '父级分类',
            'icon' => '分类图标',
            'thumbnail' => '分类图片',
            'description' => '分类描述',
            'sort' => '排序',
            'status' => '状态',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRules(?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule($ignoreId)],
            'parent_id' => ['nullable', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];

        $currentId = $ignoreId ?? 0;
        $rules['parent_id'][] = function ($attribute, $value, $fail) use ($currentId) {
            $parentId = (int) $value;
            if ($parentId <= 0) {
                return;
            }

            if ($currentId > 0 && $parentId === $currentId) {
                $fail('不能将分类设置为自己的子分类');
                return;
            }

            $repository = $this->categoryRepository();
            if (! $repository->findById($parentId)) {
                $fail('父分类不存在');
            }
        };

        return $rules;
    }

    private function uniqueNameRule(?int $ignoreId = null): Unique
    {
        $parentId = (int) ($this->input('parent_id') ?? 0);
        if ($parentId === 0 && $ignoreId) {
            $category = $this->categoryRepository()->findById($ignoreId);
            $parentId = $category ? (int) $category->parent_id : 0;
        }

        $rule = Rule::unique('categories', 'name')->where(static function ($query) use ($parentId) {
            return $query->where('parent_id', $parentId);
        });
        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }

    private function categoryRepository(): CategoryRepository
    {
        return make(CategoryRepository::class);
    }
}
