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

namespace Plugin\Distribution\Interface\Request;

use App\Interface\Common\Request\BaseRequest;

final class DistributionQueryRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['page' => ['nullable', 'integer', 'between:1,100000'], 'page_size' => ['nullable', 'integer', 'between:1,100'], 'member_id' => ['nullable', 'integer', 'min:1'], 'scope' => ['nullable', 'in:direct,all,self'], 'keyword' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'string', 'max:30'], 'start_date' => ['nullable', 'date_format:Y-m-d'], 'end_date' => array_merge(['nullable', 'date_format:Y-m-d'], $this->input('start_date') ? ['after_or_equal:start_date'] : [])];
    }
}
