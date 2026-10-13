<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Interface\Admin\Request;

use App\Interface\Common\Request\BaseRequest;
use Plugin\CustomerService\Domain\Contract\ConfigurationInput;
use Plugin\CustomerService\Interface\Admin\Dto\ConfigurationDto;

final class ConfigurationRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['snapshot' => 'required|array', 'expected_revision' => 'required|integer|min:0', 'publish' => 'sometimes|boolean'];
    }
    public function toDto(): ConfigurationInput
    {
        $data = $this->validated();
        return new ConfigurationDto($data['snapshot'], (int) $data['expected_revision'], (bool) ($data['publish'] ?? false));
    }
}
