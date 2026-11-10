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
use Plugin\Distribution\Domain\Contract\ShareInput;
use Plugin\Distribution\Interface\Dto\ShareDto;

final class ShareRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['target' => ['required', 'in:home,product'], 'channel' => ['required', 'in:link,card,qrcode,short_link,url_link'], 'platform' => ['nullable', 'in:h5,mini_program'], 'spu_id' => ['required_if:target,product', 'nullable', 'integer', 'min:1'], 'sku_id' => ['nullable', 'integer', 'min:1'], 'activity_id' => ['nullable', 'integer', 'min:1'], 'session_id' => ['nullable', 'integer', 'min:1'], 'group_buy_id' => ['nullable', 'integer', 'min:1'], 'group_no' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'], 'order_type' => ['nullable', 'in:normal,seckill,group_buy']];
    }

    public function toDto(): ShareInput
    {
        return new ShareDto($this->validated());
    }
}
