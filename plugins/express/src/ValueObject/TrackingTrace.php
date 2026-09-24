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

namespace Plugin\Express\ValueObject;

final class TrackingTrace
{
    public function __construct(
        private readonly string $time,
        private readonly string $context,
        private readonly string $location = '',
        private readonly string $status = 'unknown',
    ) {}

    /**
     * @return array{time:string,context:string,location:string,status:string}
     */
    public function toArray(): array
    {
        return [
            'time' => $this->time,
            'context' => $this->context,
            'location' => $this->location,
            'status' => $this->status,
        ];
    }
}
