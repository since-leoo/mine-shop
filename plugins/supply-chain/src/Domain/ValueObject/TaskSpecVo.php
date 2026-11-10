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

namespace Plugin\SupplyChain\Domain\ValueObject;

use Plugin\SupplyChain\Domain\Contract\TaskInput;

final readonly class TaskSpecVo implements TaskInput
{
    public function __construct(public int $connectionId, public string $type, public string $businessKey, public array $payload, public int $operatorId = 0) {}

    public function toArray(): array
    {
        return ['connection_id' => $this->connectionId, 'type' => $this->type, 'business_key' => $this->businessKey, 'payload' => $this->payload, 'operator_id' => $this->operatorId];
    }
}
