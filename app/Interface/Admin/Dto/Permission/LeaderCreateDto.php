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

namespace App\Interface\Admin\DTO\Permission;

use App\Domain\Permission\Contract\Leader\LeaderCreateInput;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 创建领导操作 DTO.
 */
#[Valid]
class LeaderCreateDto implements LeaderCreateInput
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

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dept_id' => $this->dept_id,
            'user_id' => $this->user_ids,
            'created_by' => $this->operator_id,
        ];
    }
}
