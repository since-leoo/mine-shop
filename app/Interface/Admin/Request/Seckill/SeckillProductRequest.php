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

namespace App\Interface\Admin\Request\Seckill;

use App\Domain\Trade\Seckill\Contract\SeckillProductInput;
use App\Interface\Admin\Dto\Seckill\SeckillProductDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

class SeckillProductRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return ['session_id' => ['nullable', 'integer', 'min:1'], 'activity_id' => ['nullable', 'integer', 'min:1'], 'product_id' => ['nullable', 'integer', 'min:1'], 'is_enabled' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'], 'page_size' => ['nullable', 'integer', 'min:1', 'max:100']];
    }

    public function storeRules(): array
    {
        return ['activity_id' => ['required', 'integer', 'min:1', 'exists:seckill_activities,id'], 'session_id' => ['required', 'integer', 'min:1', 'exists:seckill_sessions,id'], 'product_id' => ['required', 'integer', 'min:1', 'exists:products,id'], 'product_sku_id' => ['required', 'integer', 'min:1', 'exists:product_skus,id'], 'original_price' => ['required', 'numeric', 'min:0.01'], 'seckill_price' => ['required', 'numeric', 'min:0.01', 'lt:original_price'], 'quantity' => ['required', 'integer', 'min:1'], 'max_quantity_per_user' => ['nullable', 'integer', 'min:1'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'is_enabled' => ['nullable', 'boolean']];
    }

    public function updateRules(): array
    {
        return ['original_price' => ['nullable', 'numeric', 'min:0.01'], 'seckill_price' => ['nullable', 'numeric', 'min:0.01'], 'quantity' => ['nullable', 'integer', 'min:1'], 'max_quantity_per_user' => ['nullable', 'integer', 'min:1'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'is_enabled' => ['nullable', 'boolean']];
    }

    public function batchStoreRules(): array
    {
        return ['activity_id' => ['required', 'integer', 'min:1', 'exists:seckill_activities,id'], 'session_id' => ['required', 'integer', 'min:1', 'exists:seckill_sessions,id'], 'products' => ['required', 'array', 'min:1'], 'products.*.product_id' => ['required', 'integer', 'min:1', 'exists:products,id'], 'products.*.product_sku_id' => ['required', 'integer', 'min:1', 'exists:product_skus,id'], 'products.*.original_price' => ['required', 'numeric', 'min:0.01'], 'products.*.seckill_price' => ['required', 'numeric', 'min:0.01'], 'products.*.quantity' => ['required', 'integer', 'min:1'], 'products.*.max_quantity_per_user' => ['nullable', 'integer', 'min:1'], 'products.*.sort_order' => ['nullable', 'integer', 'min:0'], 'products.*.is_enabled' => ['nullable', 'boolean']];
    }

    public function toggleStatusRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return ['activity_id' => '活动ID', 'session_id' => '场次ID', 'product_id' => '商品ID', 'product_sku_id' => '商品SKU ID', 'original_price' => '原价', 'seckill_price' => '秒杀价', 'quantity' => '库存数量', 'max_quantity_per_user' => '每人限购数量', 'sort_order' => '排序', 'is_enabled' => '是否启用', 'products' => '商品列表'];
    }

    public function toDto(?int $id = null): SeckillProductInput
    {
        $params = $this->validated();
        $params['id'] = $id;
        return Mapper::map($params, new SeckillProductDto());
    }
}
