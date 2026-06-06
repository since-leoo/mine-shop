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

use App\Domain\Content\DiyPage\Service\DomainDiyTemplateService;
use App\Infrastructure\Model\Content\DiyTemplate;

final class AppDiyTemplateQueryService
{
    public function __construct(private readonly DomainDiyTemplateService $templateService) {}

    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->templateService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?DiyTemplate
    {
        return $this->templateService->repository->findDetail($id);
    }
}
