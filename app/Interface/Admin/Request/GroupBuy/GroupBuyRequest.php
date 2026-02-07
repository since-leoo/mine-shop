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

namespace App\Interface\Admin\Request\GroupBuy;

use App\Domain\Marketing\GroupBuy\Contract\GroupBuyCreateInput;
use App\Domain\Marketing\GroupBuy\Contract\GroupBuyUpdateInput;
use App\Interface\Admin\Dto\GroupBuy\GroupBuyDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

class GroupBuyRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled', 'sold_out'])],
            'is_enabled' => ['nullable', 'boolean'],
            'product_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function storeRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'original_price' => ['required', 'numeric', 'min:0.01'],
            'group_price' => ['required', 'numeric', 'min:0.01', 'lt:original_price'],
            'min_people' => ['required', 'integer', 'min:2', 'max:100'],
            'max_people' => ['required', 'integer', 'gte:min_people', 'max:1000'],
            'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_time' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_time'],
            'group_time_limit' => ['required', 'integer', 'min:1', 'max:720'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled'])],
            'total_quantity' => ['required', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
            'rules' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'original_price' => ['required', 'numeric', 'min:0.01'],
            'group_price' => ['required', 'numeric', 'min:0.01', 'lt:original_price'],
            'min_people' => ['required', 'integer', 'min:2', 'max:100'],
            'max_people' => ['required', 'integer', 'gte:min_people', 'max:1000'],
            'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_time' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_time'],
            'group_time_limit' => ['required', 'integer', 'min:1', 'max:720'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'ended', 'cancelled'])],
            'total_quantity' => ['required', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
            'rules' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toggleStatusRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'title' => '活动标题',
            'description' => '活动描述',
            'product_id' => '商品',
            'sku_id' => 'SKU',
            'original_price' => '原价',
            'group_price' => '团购价',
            'min_people' => '最少成团人数',
            'max_people' => '最多成团人数',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'group_time_limit' => '成团时限',
            'status' => '活动状态',
            'total_quantity' => '总库存',
            'sort_order' => '排序',
            'is_enabled' => '是否启用',
            'rules' => '活动规则',
            'images' => '活动图片',
            'remark' => '备注',
        ];
    }

    /**
     * 转换为 DTO.
     * @param null|int $id 团购活动ID，创建时为null，更新时传入
     */
    public function toDto(?int $id): GroupBuyCreateInput|GroupBuyUpdateInput
    {
        $params = $this->validated();
        $params['id'] = $id;

        return Mapper::map($params, new GroupBuyDto());
    }
}
