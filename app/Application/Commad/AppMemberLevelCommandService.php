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

use App\Domain\Member\Contract\MemberLevelInput;
use App\Domain\Member\Service\DomainMemberLevelService;
use Hyperf\DbConnection\Db;

final class AppMemberLevelCommandService
{
    public function __construct(
        private readonly DomainMemberLevelService $memberLevelService
    ) {}

    /**
     * 创建会员等级.
     *
     * @return array<string, mixed>
     */
    public function create(MemberLevelInput $input): array
    {
        // 事务管理
        $level = Db::transaction(fn () => $this->memberLevelService->create($input));

        return $level->toArray();
    }

    /**
     * 更新会员等级.
     *
     * @return array<string, mixed>
     */
    public function update(MemberLevelInput $input): array
    {
        // 事务管理
        $level = Db::transaction(fn () => $this->memberLevelService->update($input));

        return $level->toArray();
    }

    /**
     * 删除会员等级.
     */
    public function delete(int $id): bool
    {
        // 事务管理
        return Db::transaction(fn () => $this->memberLevelService->delete($id));
    }
}
