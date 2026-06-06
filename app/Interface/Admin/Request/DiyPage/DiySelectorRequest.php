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

namespace App\Interface\Admin\Request\DiyPage;

use App\Domain\Trade\GroupBuy\Enum\GroupBuyStatus;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\Validation\Rule;

final class DiySelectorRequest extends BaseRequest
{
    public function productsRules(): array
    {
        return $this->pageRules() + [
            'keyword' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function categoriesRules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function couponsRules(): array
    {
        return $this->pageRules() + [
            'keyword' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function seckillsRules(): array
    {
        return $this->pageRules() + [
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                SeckillStatus::ACTIVE->value,
                SeckillStatus::PENDING->value,
            ])],
        ];
    }

    public function groupBuysRules(): array
    {
        return $this->pageRules() + [
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                GroupBuyStatus::ACTIVE->value,
                GroupBuyStatus::PENDING->value,
            ])],
        ];
    }

    private function pageRules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
