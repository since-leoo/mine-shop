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

use App\Domain\Member\Contract\MemberTagInput;
use App\Interface\Admin\DTO\Member\MemberTagDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

class MemberTagRequest extends BaseRequest
{
    public function listRules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '标签名称',
            'color' => '标签颜色',
            'description' => '标签说明',
            'status' => '状态',
            'sort_order' => '排序',
        ];
    }

    /**
     * 转换为 DTO.
     * @param null|int $id 会员标签ID，创建时为null，更新时传入
     * @param int $operatorId 操作者ID
     */
    public function toDto(?int $id, int $operatorId): MemberTagInput
    {
        $params = $this->validated();
        $params['id'] = $id;
        $params['operator_id'] = $operatorId;

        return Mapper::map($params, new MemberTagDto());
    }
}
