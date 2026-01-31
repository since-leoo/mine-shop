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

namespace App\Application\Member\Assembler;

use App\Domain\Member\Entity\MemberWalletEntity;

final class MemberAccountAssembler
{
    /**
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): MemberWalletEntity
    {
        $entity = new MemberWalletEntity();

        $entity->setMemberId((int) $data['member_id']);
        $entity->setType((string) ($data['type'] ?? 'balance'));
        $entity->setChangeBalance((float) $data['value']);
        $entity->setSource((string) ($data['source'] ?? 'manual'));
        $entity->setRemark((string) ($data['remark'] ?? ''));

        return $entity;
    }
}
