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

namespace Plugin\CustomerService\Domain\Entity;

final class CustomerServiceConversationEntity
{
    public function __construct(
        private readonly int $id,
        private readonly int $memberId,
        private string $status,
    ) {}

    public function id(): int
    {
        return $this->id;
    }

    public function memberId(): int
    {
        return $this->memberId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function close(): void
    {
        if (! \in_array($this->status, ['waiting', 'assigned', 'active'], true)) {
            throw new \DomainException('当前会话不能关闭');
        }
        $this->status = 'closed';
    }
}
