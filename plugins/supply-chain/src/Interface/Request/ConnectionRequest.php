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
use Plugin\SupplyChain\Domain\Contract\ConnectionInput;
use Plugin\SupplyChain\Interface\Dto\ConnectionDto;

final class ConnectionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'api_origin' => ['required', 'url', 'max:255', 'regex:#^https://[^/?\#@]+/?$#i'],
            'api_path' => ['sometimes', 'string', 'max:120', 'regex:#^/(?!/)[a-zA-Z0-9/_-]*$#'],
            'app_key' => ['required', 'string', 'max:160'],
            'app_secret' => [$this->getActionMethod() === 'createConnection' ? 'required' : 'nullable', 'string', 'max:2000'],
            'enabled' => ['sometimes', 'boolean'],
            'mode' => ['required', 'in:active,pause_new,draining'],
            'timeout_seconds' => ['sometimes', 'integer', 'between:1,30'],
            'connect_timeout_seconds' => ['sometimes', 'integer', 'between:1,10'],
            'settings' => ['sometimes', 'array'],
            'settings.return_carriers' => ['sometimes', 'array', 'max:100'],
            'settings.return_carriers.*' => ['required', 'array'],
            'settings.return_carriers.*.name' => ['required', 'string', 'max:100'],
            'settings.return_carriers.*.code' => ['required', 'string', 'max:64'],
        ];
    }

    public function toDto(): ConnectionInput
    {
        $data = $this->validated();
        $secret = $data['app_secret'] ?? null;
        unset($data['app_secret']);
        $data['api_domain'] = rtrim($data['api_origin'], '/');
        unset($data['api_origin']);
        return new ConnectionDto($data, $secret === '' ? null : $secret);
    }
}
