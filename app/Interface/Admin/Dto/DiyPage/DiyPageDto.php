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

use App\Domain\Content\DiyPage\Contract\DiyPageInput;
use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use Hyperf\DTO\Annotation\Validation\Required;

final class DiyPageDto implements DiyPageInput
{
    #[Required]
    public string $page_key = 'home';

    #[Required]
    public string $page_type = DiyPageStatus::TYPE_MINIPROGRAM;

    #[Required]
    public string $title = '';

    public ?string $description = null;

    public function getPageKey(): string
    {
        return $this->page_key;
    }

    public function getPageType(): string
    {
        return $this->page_type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
