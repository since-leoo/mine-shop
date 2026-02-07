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

namespace App\Domain\Member\Api\Command;

use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Service\DomainMemberService;

/**
 * 面向 API 场景的会员写领域服务.
 */
final class DomainApiMemberCommandService
{
    public function __construct(private readonly DomainMemberService $memberService) {}

    /**
     * 获取会员实体（用于写操作场景，如支付）.
     */
    public function getEntity(int $memberId): MemberEntity
    {
        return $this->memberService->getEntity($memberId);
    }
}
