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

namespace Plugin\Diy\Application\Admin\Content;

use Plugin\Diy\Domain\Content\DiyPage\Contract\DiyPublishScheduleInput;
use Plugin\Diy\Domain\Content\DiyPage\Service\DomainDiyPublishService;
use Plugin\Diy\Infrastructure\Model\Content\DiyPagePreviewToken;
use Plugin\Diy\Infrastructure\Model\Content\DiyPagePublishRecord;
use Plugin\Diy\Infrastructure\Model\Content\DiyPageVersion;
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
