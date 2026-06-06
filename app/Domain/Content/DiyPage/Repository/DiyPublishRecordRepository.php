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

namespace App\Domain\Content\DiyPage\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Content\DiyPagePreviewToken;
use App\Infrastructure\Model\Content\DiyPagePublishRecord;

/**
 * @extends IRepository<DiyPagePublishRecord>
 */
class DiyPublishRecordRepository extends IRepository
{
    public function __construct(protected readonly DiyPagePublishRecord $model) {}

    public function records(int $pageId): array
    {
        return DiyPagePublishRecord::query()
            ->where('page_id', $pageId)
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    public function hasPendingSchedule(int $pageId): bool
    {
        return DiyPagePublishRecord::query()
            ->where('page_id', $pageId)
            ->where('publish_type', 'scheduled')
            ->where('publish_status', 'pending')
            ->exists();
    }

    public function createRecord(array $data): DiyPagePublishRecord
    {
        /** @var DiyPagePublishRecord $record */
        $record = DiyPagePublishRecord::query()->create($data);

        return $record;
    }

    public function findRecord(int $id): ?DiyPagePublishRecord
    {
        /** @var DiyPagePublishRecord|null $record */
        $record = DiyPagePublishRecord::query()->whereKey($id)->first();

        return $record;
    }

    public function cancelRecord(int $id): bool
    {
        return (bool) DiyPagePublishRecord::query()
            ->whereKey($id)
            ->update(['publish_status' => 'cancelled']);
    }

    public function createPreviewToken(array $data): DiyPagePreviewToken
    {
        /** @var DiyPagePreviewToken $token */
        $token = DiyPagePreviewToken::query()->create($data);

        return $token;
    }

    public function findPreviewToken(string $token): ?DiyPagePreviewToken
    {
        /** @var DiyPagePreviewToken|null $model */
        $model = DiyPagePreviewToken::query()
            ->where('token', $token)
            ->first();

        return $model;
    }
}
