<?php

declare(strict_types=1);

use App\Domain\Content\DiyPage\Enum\DiyPageStatus;
use App\Domain\Content\DiyPage\ValueObject\DiyPagePublishValidationVo;
use App\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use App\Infrastructure\Model\Content\DiyPage;
use App\Infrastructure\Model\Content\DiyPagePublishRecord;
use App\Infrastructure\Model\Content\DiyPageVersion;
use Carbon\Carbon;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;
use seeders\support\DefaultDiyPageSchemas;

require_once __DIR__ . '/support/DefaultDiyPageSchemas.php';

class DiyDefaultPageSeeder20260616 extends Seeder
{
    public function run(): void
    {
        Db::transaction(function (): void {
            foreach (DefaultDiyPageSchemas::all() as $definition) {
                $this->seedPage($definition);
            }
        });
    }

    private function seedPage(array $definition): void
    {
        $pageKey = (string)$definition['page_key'];
        $pageType = (string)$definition['page_type'];
        $schema = DiyPageSchemaVo::fromArray($definition['schema'], $pageKey)->toArray();
        $validation = DiyPagePublishValidationVo::inspect($schema);

        if (!$validation->passed()) {
            $firstIssue = $validation->issues()[0];
            throw new RuntimeException(sprintf(
                '默认DIY页面发布校验失败：%s/%s - %s',
                $pageKey,
                $pageType,
                $firstIssue['message'] ?? '未知错误'
            ));
        }

        $page = DiyPage::query()
            ->where('page_key', $pageKey)
            ->where('page_type', $pageType)
            ->first();

        if (!$page instanceof DiyPage) {
            $page = new DiyPage();
            $page->page_key = $pageKey;
            $page->page_type = $pageType;
            $page->created_by = null;
        }

        $page->fill([
            'title' => $definition['title'],
            'description' => $definition['description'],
            'status' => DiyPageStatus::PAGE_PUBLISHED,
            'is_enabled' => true,
            'updated_by' => null,
        ]);
        $page->save();

        $version = DiyPageVersion::query()
            ->where('page_id', $page->id)
            ->where('version_no', 1)
            ->first();

        if (!$version instanceof DiyPageVersion) {
            $version = new DiyPageVersion();
            $version->page_id = $page->id;
            $version->version_no = 1;
        }

        $now = Carbon::now();
        $version->fill([
            'status' => DiyPageStatus::VERSION_PUBLISHED,
            'schema' => $schema,
            'published_at' => $version->published_at ?: $now,
            'created_by' => null,
        ]);
        $version->save();

        if ($this->shouldUseSeededVersion($page)) {
            $page->fill([
                'published_version_id' => $version->id,
                'status' => DiyPageStatus::PAGE_PUBLISHED,
                'is_enabled' => true,
            ])->save();
        }

        $this->recordPublish($page, $version, $now);
    }

    private function shouldUseSeededVersion(DiyPage $page): bool
    {
        if (empty($page->published_version_id)) {
            return true;
        }

        $published = DiyPageVersion::query()
            ->whereKey((int)$page->published_version_id)
            ->first();

        return !$published instanceof DiyPageVersion || (int)$published->version_no <= 1;
    }

    private function recordPublish(DiyPage $page, DiyPageVersion $version, Carbon $now): void
    {
        $record = DiyPagePublishRecord::query()
            ->where('page_id', $page->id)
            ->where('version_id', $version->id)
            ->where('remark', 'default_seed_20260616')
            ->first();

        if (!$record instanceof DiyPagePublishRecord) {
            $record = new DiyPagePublishRecord();
            $record->page_id = $page->id;
            $record->version_id = $version->id;
            $record->remark = 'default_seed_20260616';
        }

        $record->fill([
            'publish_type' => 'manual',
            'publish_status' => 'published',
            'published_at' => $record->published_at ?: $now,
            'operator_id' => null,
            'error_message' => null,
        ]);
        $record->save();
    }
}
