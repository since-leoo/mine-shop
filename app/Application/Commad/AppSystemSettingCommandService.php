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

namespace App\Application\Commad;

use App\Domain\SystemSetting\Service\DomainSystemSettingService;
use App\Interface\Admin\Dto\SystemSetting\SystemSettingDto;

final class AppSystemSettingCommandService
{
    public function __construct(
        private readonly DomainSystemSettingService $service
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function update(string $key, mixed $value): array
    {
        $dto = new SystemSettingDto();
        $dto->key = $key;
        $dto->value = $value;
        $dto->type = $this->resolveType($key, $value);

        return $this->service->update($dto);
    }

    private function resolveType(string $key, mixed $value): string
    {
        foreach (config('mall.groups', []) as $group) {
            $settings = $group['settings'] ?? [];
            if (isset($settings[$key])) {
                return (string) ($settings[$key]['type'] ?? '');
            }
        }

        return \is_array($value) ? json_encode($value) : '';
    }
}
