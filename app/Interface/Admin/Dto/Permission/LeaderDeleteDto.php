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

namespace App\Interface\Admin\Dto\Permission;

use App\Domain\Organization\Contract\Leader\LeaderDeleteInput;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 删除领导操作 DTO.
 */
class LeaderDeleteDto implements LeaderDeleteInput
{
    #[Required]
    public int $dept_id = 0;

    #[Required]
    public array $user_ids = [];

    #[Required]
    public int $operator_id = 0;

    public function getDeptId(): int
    {
        return $this->dept_id;
    }

    public function getUserIds(): array
    {
        return $this->user_ids;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
