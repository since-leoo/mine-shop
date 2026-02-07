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

namespace App\Interface\Admin\Request\Member;

use App\Domain\Member\Contract\MemberLevelInput;
use App\Interface\Admin\Dto\Member\MemberLevelDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;
use Hyperf\Validation\Rules\Unique;

class MemberLevelRequest extends BaseRequest
{
    public function listRules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', $this->uniqueRule('name')],
            'level' => ['required', 'integer', 'min:1', 'max:255', $this->uniqueRule('level')],
            'growth_value_min' => ['required', 'integer', 'min:0'],
            'growth_value_max' => ['nullable', 'integer', 'gte:growth_value_min'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'point_rate' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'privileges' => ['nullable', 'array'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function updateRules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => ['nullable', 'string', 'max:50', $this->uniqueRule('name', $id)],
            'level' => ['nullable', 'integer', 'min:1', 'max:255', $this->uniqueRule('level', $id)],
            'growth_value_min' => ['nullable', 'integer', 'min:0'],
            'growth_value_max' => ['nullable', 'integer', 'gte:growth_value_min'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'point_rate' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'privileges' => ['nullable', 'array'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '等级名称',
            'level' => '等级值',
            'growth_value_min' => '最低成长值',
            'growth_value_max' => '最高成长值',
            'discount_rate' => '折扣率',
            'point_rate' => '积分倍率',
            'status' => '状态',
        ];
    }

    /**
     * 转换为 DTO.
     * @param null|int $id 会员等级ID，创建时为null，更新时传入
     * @param int $operatorId 操作者ID
     */
    public function toDto(?int $id, int $operatorId): MemberLevelInput
    {
        $params = $this->validated();
        $params['id'] = $id;
        $params['operator_id'] = $operatorId;

        return Mapper::map($params, new MemberLevelDto());
    }

    private function uniqueRule(string $column, ?int $ignoreId = null): Unique
    {
        $rule = Rule::unique('member_levels', $column);
        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }
}
