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

use App\Domain\Content\DiyPage\Contract\DiyTemplateApplyInput;
use Hyperf\DTO\Annotation\Validation\Required;

final class DiyTemplateApplyDto implements DiyTemplateApplyInput
{
    #[Required]
    public int $template_id;

    #[Required]
    public int $page_id = 0;

    public function __construct(int $templateId = 0)
    {
        $this->template_id = $templateId;
    }

    public function getTemplateId(): int
    {
        return $this->template_id;
    }

    public function getPageId(): int
    {
        return $this->page_id;
    }
}
