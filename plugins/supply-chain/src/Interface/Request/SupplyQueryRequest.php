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

namespace Plugin\SupplyChain\Interface\Request;

use App\Interface\Common\Request\BaseRequest;

final class SupplyQueryRequest extends BaseRequest
{
    public function rules(): array
    {
        $connectionRequired = \in_array($this->getActionMethod(), ['catalog', 'catalogDetail', 'afterSaleTypes'], true);
        return [
            'connection_id' => [$connectionRequired ? 'required' : 'sometimes', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            'page_size' => ['sometimes', 'integer', 'between:1,100'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:32'],
            'type' => ['nullable', 'string', 'max:40'],
            'task_id' => ['sometimes', 'integer', 'min:1'],
            'order_id' => ['sometimes', 'integer', 'min:1'],
            'trace_id' => ['nullable', 'string', 'max:64'],
            'created_from' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'created_to' => array_merge(['nullable', 'date_format:Y-m-d H:i:s'], $this->filled('created_from') ? ['after_or_equal:created_from'] : []),
            'external_order_item_id' => [$this->getActionMethod() === 'afterSaleTypes' ? 'required' : 'sometimes', 'string', 'max:128'],
        ];
    }
}
