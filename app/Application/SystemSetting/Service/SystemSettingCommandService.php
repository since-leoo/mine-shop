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

namespace App\Application\SystemSetting\Service;

use App\Application\SystemSetting\Assembler\SystemSettingAssembler;
use App\Domain\SystemSetting\Service\SystemSettingService;

final class SystemSettingCommandService
{
    public function __construct(
        private readonly SystemSettingService $service,
        private readonly SystemSettingAssembler $assembler
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function update(string $key, mixed $value): array
    {
        $entity = $this->assembler->fromRequest($key, $value);
        return $this->service->update($entity);
    }
}
