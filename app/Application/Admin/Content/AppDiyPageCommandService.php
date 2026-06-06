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

use App\Domain\Content\DiyPage\Contract\DiyPageDraftInput;
use App\Domain\Content\DiyPage\Contract\DiyPageInput;
use App\Domain\Content\DiyPage\Service\DomainDiyPageService;
use App\Infrastructure\Model\Content\DiyPage;
use App\Infrastructure\Model\Content\DiyPageVersion;
use Hyperf\DbConnection\Db;

final class AppDiyPageCommandService
{
    public function __construct(private readonly DomainDiyPageService $diyPageService) {}

    public function create(DiyPageInput $input, ?int $operatorId = null): DiyPage
    {
        return Db::transaction(fn () => $this->diyPageService->create($input, $operatorId));
    }

    public function update(int $id, DiyPageInput $input, ?int $operatorId = null): bool
    {
        return Db::transaction(fn () => $this->diyPageService->update($id, $input, $operatorId));
    }

    public function copy(int $id, ?int $operatorId = null): DiyPage
    {
        return Db::transaction(fn () => $this->diyPageService->copy($id, $operatorId));
    }

    public function saveDraft(int $id, DiyPageDraftInput $input, ?int $operatorId = null): DiyPageVersion
    {
        return Db::transaction(fn () => $this->diyPageService->saveDraft($id, $input, $operatorId));
    }

    public function publish(int $id, ?int $operatorId = null): DiyPageVersion
    {
        return Db::transaction(fn () => $this->diyPageService->publish($id, $operatorId));
    }

    public function enable(int $id, ?int $operatorId = null): bool
    {
        return Db::transaction(fn () => $this->diyPageService->enable($id, $operatorId));
    }

    public function disable(int $id, ?int $operatorId = null): bool
    {
        return Db::transaction(fn () => $this->diyPageService->disable($id, $operatorId));
    }

    public function resetDraft(int $id, ?int $operatorId = null): DiyPageVersion
    {
        return Db::transaction(fn () => $this->diyPageService->resetDraft($id, $operatorId));
    }
}
