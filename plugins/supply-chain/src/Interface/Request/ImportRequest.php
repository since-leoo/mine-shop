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
use Plugin\SupplyChain\Domain\Contract\ImportInput;
use Plugin\SupplyChain\Interface\Dto\ImportDto;

final class ImportRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['connection_id' => ['required', 'integer', 'min:1'], 'external_product_ids' => ['required', 'array', 'min:1', 'max:50'], 'external_product_ids.*' => ['required', 'string', 'max:128', 'distinct'], 'category_id' => ['required', 'integer', 'min:1'], 'price_multiplier' => ['sometimes', 'numeric', 'between:1,10']];
    }

    public function toDto(): ImportInput
    {
        return new ImportDto($this->validated());
    }
}
