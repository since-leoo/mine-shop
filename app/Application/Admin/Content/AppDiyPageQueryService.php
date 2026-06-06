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

namespace App\Application\Admin\Content;

use App\Domain\Content\DiyPage\Service\DomainDiyPageService;
use App\Infrastructure\Model\Content\DiyPage;

final class AppDiyPageQueryService
{
    public function __construct(private readonly DomainDiyPageService $diyPageService) {}

    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->diyPageService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?DiyPage
    {
        return $this->diyPageService->findDetail($id);
    }
}
