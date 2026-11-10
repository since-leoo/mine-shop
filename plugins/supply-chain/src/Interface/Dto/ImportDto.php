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

namespace Plugin\SupplyChain\Interface\Dto;

use Plugin\SupplyChain\Domain\Contract\ImportInput;

final readonly class ImportDto implements ImportInput
{
    public function __construct(private array $data) {}

    public function toArray(): array
    {
        return $this->data;
    }
}
