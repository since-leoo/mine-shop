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

namespace Plugin\Since\Seckill\Interface\Request;

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;
use Plugin\Since\Seckill\Domain\Contract\SeckillActivityInput;
use Plugin\Since\Seckill\Interface\Dto\SeckillActivityDto;

class SeckillActivityRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return ['title' => ['nullable', 'string', 'max:200'], 'keyword' => ['nullable', 'string', 'max:200'], 'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled'])], 'is_enabled' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'], 'page_size' => ['nullable', 'integer', 'min:1', 'max:100']];
    }

    public function storeRules(): array
    {
        return ['title' => ['required', 'string', 'min:2', 'max:200'], 'description' => ['nullable', 'string'], 'rules' => ['nullable', 'array'], 'remark' => ['nullable', 'string']];
    }

    public function updateRules(): array
    {
        return ['title' => ['required', 'string', 'min:2', 'max:200'], 'description' => ['nullable', 'string'], 'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled'])], 'rules' => ['nullable', 'array'], 'remark' => ['nullable', 'string']];
    }

    public function toggleStatusRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return ['title' => '活动标题', 'description' => '活动描述', 'status' => '活动状态', 'is_enabled' => '是否启用', 'rules' => '活动规则', 'remark' => '备注'];
    }

    public function messages(): array
    {
        return ['title.required' => '活动标题不能为空', 'title.min' => '活动标题至少需要2个字符', 'title.max' => '活动标题不能超过200个字符'];
    }

    public function toDto(?int $id = null): SeckillActivityInput
    {
        $params = $this->validated();
        $params['id'] = $id;
        return Mapper::map($params, new SeckillActivityDto());
    }
}
