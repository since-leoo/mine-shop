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

namespace App\Interface\Admin\Request\Review;

use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Rule;

class ReviewRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function listRules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'product_id' => ['nullable', 'integer'],
            'member_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function showRules(): array
    {
        return [];
    }

    public function approveRules(): array
    {
        return [];
    }

    public function rejectRules(): array
    {
        return [];
    }

    public function statsRules(): array
    {
        return [];
    }

    public function byOrderRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'status' => '评价状态',
            'rating' => '评分',
            'product_id' => '商品ID',
            'member_id' => '用户ID',
            'start_date' => '开始日期',
            'end_date' => '结束日期',
        ];
    }
}
