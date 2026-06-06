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

use App\Domain\Content\DiyPage\Contract\DiyPublishScheduleInput;
use App\Domain\Content\DiyPage\Service\DomainDiyPublishService;
use App\Infrastructure\Model\Content\DiyPagePreviewToken;
use App\Infrastructure\Model\Content\DiyPagePublishRecord;
use App\Infrastructure\Model\Content\DiyPageVersion;
use Hyperf\DbConnection\Db;

final class AppDiyPublishCommandService
{
    public function __construct(private readonly DomainDiyPublishService $publishService) {}

    public function records(int $pageId): array
    {
        return $this->publishService->records($pageId);
    }

    public function schedule(DiyPublishScheduleInput $input, ?int $operatorId = null): DiyPagePublishRecord
    {
        return Db::transaction(fn () => $this->publishService->schedule($input, $operatorId));
    }

    public function cancelSchedule(int $recordId): bool
    {
        return Db::transaction(fn () => $this->publishService->cancelSchedule($recordId));
    }

    public function rollback(int $pageId, int $versionId, ?int $operatorId = null): DiyPageVersion
    {
        return Db::transaction(fn () => $this->publishService->rollback($pageId, $versionId, $operatorId));
    }

    public function createPreviewToken(int $pageId, ?int $versionId, ?int $operatorId = null): DiyPagePreviewToken
    {
        return Db::transaction(fn () => $this->publishService->createPreviewToken($pageId, $versionId, $operatorId));
    }
}
