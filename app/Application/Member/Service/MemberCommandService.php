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

namespace App\Application\Member\Service;

use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Service\MemberService;

/**
 * 会员写应用服务.
 */
final class MemberCommandService
{
    public function __construct(private readonly MemberService $memberService) {}

    public function create(MemberEntity $entity): void
    {
        $this->memberService->create($entity);
    }

    public function update(MemberEntity $entity): void
    {
        $this->memberService->update($entity);
    }

    public function updateStatus(MemberEntity $entity): void
    {
        $this->memberService->updateStatus($entity);
    }

    public function syncTags(MemberEntity $entity): void
    {
        $this->memberService->syncTags($entity);
    }
}
