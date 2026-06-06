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

use App\Domain\Content\DiyPage\Contract\DiyTemplateApplyInput;
use App\Domain\Content\DiyPage\Contract\DiyTemplateInput;
use App\Domain\Content\DiyPage\Service\DomainDiyTemplateService;
use App\Infrastructure\Model\Content\DiyPageVersion;
use App\Infrastructure\Model\Content\DiyTemplate;
use Hyperf\DbConnection\Db;

final class AppDiyTemplateCommandService
{
    public function __construct(private readonly DomainDiyTemplateService $templateService) {}

    public function create(DiyTemplateInput $input, ?int $operatorId = null): DiyTemplate
    {
        return Db::transaction(fn () => $this->templateService->create($input, $operatorId));
    }

    public function update(int $id, DiyTemplateInput $input, ?int $operatorId = null): bool
    {
        return Db::transaction(fn () => $this->templateService->update($id, $input, $operatorId));
    }

    public function enable(int $id): bool
    {
        return Db::transaction(fn () => $this->templateService->enable($id));
    }

    public function disable(int $id): bool
    {
        return Db::transaction(fn () => $this->templateService->disable($id));
    }

    public function apply(DiyTemplateApplyInput $input, ?int $operatorId = null): DiyPageVersion
    {
        return Db::transaction(fn () => $this->templateService->apply($input, $operatorId));
    }

    public function savePageAsTemplate(int $pageId, DiyTemplateInput $input, ?int $operatorId = null): DiyTemplate
    {
        return Db::transaction(fn () => $this->templateService->savePageAsTemplate($pageId, $input, $operatorId));
    }
}
