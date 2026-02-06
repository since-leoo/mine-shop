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

use App\Domain\Permission\Contract\Common\DeleteInput;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 删除操作 DTO.
 */
#[Valid]
class DeleteDto implements DeleteInput
{
    #[Required]
    public array $ids = [];
    
    #[Required]
    public int $operator_id = 0;
    
    public function getIds(): array
    {
        return $this->ids;
    }
    
    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
