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

namespace App\Interface\Admin\Dto\DiyPage;

use App\Domain\Content\DiyPage\Contract\DiyPageDraftInput;
use Hyperf\DTO\Annotation\Validation\Required;

final class DiyPageDraftDto implements DiyPageDraftInput
{
    #[Required]
    public array $schema = [];

    public function getSchema(): array
    {
        return $this->schema;
    }
}
