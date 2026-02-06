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

use App\Domain\Member\Contract\MemberInput;
use App\Interface\Admin\DTO\Member\MemberDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

class MemberRequest extends BaseRequest
{
    public function listRules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'banned'])],
            'level' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'tag_id' => ['nullable', 'integer', 'exists:member_tags,id'],
            'created_start' => ['nullable', 'date'],
            'created_end' => ['nullable', 'date', 'after_or_equal:created_start'],
            'last_login_start' => ['nullable', 'date'],
            'last_login_end' => ['nullable', 'date', 'after_or_equal:last_login_start'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function statsRules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['active', 'inactive', 'banned'])],
            'level' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'tag_id' => ['nullable', 'integer', 'exists:member_tags,id'],
            'created_start' => ['nullable', 'date'],
            'created_end' => ['nullable', 'date', 'after_or_equal:created_start'],
        ];
    }

    public function overviewRules(): array
    {
        return array_merge($this->statsRules(), [
            'trend_days' => ['nullable', 'integer', 'min:3', 'max:30'],
        ]);
    }

    public function storeRules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['unknown', 'male', 'female'])],
            'phone' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/', 'unique:members,phone'],
            'birthday' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:50'],
            'district' => ['nullable', 'string', 'max:80'],
            'street' => ['nullable', 'string', 'max:120'],
            'region_path' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:50'],
            'level' => ['nullable', Rule::in(['bronze', 'silver', 'gold', 'diamond'])],
            'growth_value' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
            'source' => ['nullable', Rule::in(['wechat', 'mini_program', 'h5', 'admin'])],
            'remark' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:member_tags,id'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'nickname' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['unknown', 'male', 'female'])],
            'phone' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/'],
            'birthday' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:50'],
            'district' => ['nullable', 'string', 'max:80'],
            'street' => ['nullable', 'string', 'max:120'],
            'region_path' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:50'],
            'level' => ['nullable', 'string', 'max:50'],
            'growth_value' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
            'source' => ['nullable', 'string', 'max:50'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function updateStatusRules(): array
    {
        return [
            'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
        ];
    }

    public function syncTagsRules(): array
    {
        return [
            'tags' => ['array'],
            'tags.*' => ['integer', 'exists:member_tags,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nickname' => '昵称',
            'avatar' => '头像',
            'gender' => '性别',
            'phone' => '手机号',
            'birthday' => '生日',
            'city' => '城市',
            'province' => '省份',
            'district' => '区县',
            'street' => '街道',
            'region_path' => '地区编码路径',
            'country' => '国家',
            'level' => '会员等级',
            'growth_value' => '成长值',
            'status' => '会员状态',
            'source' => '来源渠道',
            'remark' => '备注',
            'tags' => '标签',
        ];
    }

    /**
     * 转换为 DTO.
     * @param null|int $id 会员ID，创建时为null，更新时传入
     * @param int $operatorId 操作者ID
     */
    public function toDto(?int $id, int $operatorId): MemberInput
    {
        $params = $this->validated();
        $params['id'] = $id;
        $params['operator_id'] = $operatorId;

        return Mapper::map($params, new MemberDto());
    }
}
