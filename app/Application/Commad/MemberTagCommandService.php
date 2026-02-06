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

use App\Domain\Member\Contract\MemberTagInput;
use App\Domain\Member\Service\MemberTagService;
use Hyperf\DbConnection\Db;

final class MemberTagCommandService
{
    public function __construct(
        private readonly MemberTagService $memberTagService
    ) {}

    /**
     * 创建会员标签.
     */
    public function create(MemberTagInput $input): void
    {
        Db::transaction(fn () => $this->memberTagService->create($input));
    }

    /**
     * 更新会员标签.
     */
    public function update(MemberTagInput $input): void
    {
        Db::transaction(fn () => $this->memberTagService->update($input));
    }

    /**
     * 删除会员标签.
     */
    public function delete(int $id): bool
    {
        return Db::transaction(fn () => $this->memberTagService->delete($id));
    }
}
