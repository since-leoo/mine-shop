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

use App\Domain\Content\DiyPage\Contract\DiyPublishScheduleInput;
use Carbon\Carbon;
use Hyperf\DTO\Annotation\Validation\Required;

final class DiyPublishScheduleDto implements DiyPublishScheduleInput
{
    #[Required]
    public int $page_id;

    #[Required]
    public int $version_id = 0;

    #[Required]
    public string $scheduled_at = '';

    public ?string $remark = null;

    public function __construct(int $pageId = 0)
    {
        $this->page_id = $pageId;
    }

    public function getPageId(): int
    {
        return $this->page_id;
    }

    public function getVersionId(): int
    {
        return $this->version_id;
    }

    public function getScheduledAt(): Carbon
    {
        return Carbon::parse($this->scheduled_at);
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }
}
