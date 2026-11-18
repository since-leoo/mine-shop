<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Repository;

use App\Infrastructure\Error\CustomerServiceError;
use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;

final class DatabaseSupportConfiguration implements SupportConfigurationInterface
{
    public function draft(): array
    {
        $state = Db::table('customer_service_config_state')->where('id', 1)->first();
        return $state && $state->draft_revision ? $this->revision((int) $state->draft_revision) : ['revision' => 0, 'snapshot' => []];
    }

    public function published(): array
    {
        $state = Db::table('customer_service_config_state')->where('id', 1)->first();
        return $state && $state->published_revision ? $this->revision((int) $state->published_revision) : ['revision' => 0, 'snapshot' => []];
    }

    public function save(array $snapshot, int $expectedRevision, int $operatorId, bool $publish): array
    {
        return Db::transaction(function () use ($snapshot, $expectedRevision, $operatorId, $publish) {
            // 版本指针加锁后再比较期望版本，拒绝覆盖其他管理员刚保存的配置。
            $state = Db::table('customer_service_config_state')->where('id', 1)->lockForUpdate()->first();
            if (!$state || (int) $state->draft_revision !== $expectedRevision) {
                throw CustomerServiceError::CONFIGURATION_REVISION_CONFLICT->exception();
            }
            $categoryIds = array_column($snapshot['categories'] ?? [], 'id');
            if (Db::table('customer_service_faqs')->whereNotNull('category_id')->whereNotIn('category_id', $categoryIds)->exists()) {
                throw CustomerServiceError::CATEGORY_REFERENCED_BY_KNOWLEDGE->exception();
            }
            $agentIds = array_unique(array_column($snapshot['agent_skills'] ?? [], 'agent_id'));
            if ($agentIds && Db::table('customer_service_agents')->whereIn('id', $agentIds)->count() !== count($agentIds)) {
                throw CustomerServiceError::SKILL_AGENT_NOT_FOUND->exception();
            }
            $previous = $state->draft_revision ? $this->revision((int) $state->draft_revision)['snapshot'] : [];
            foreach (\Plugin\CustomerService\Domain\Service\DomainConfigurationService::RESOURCES as $resource) {
                $snapshot['_next_ids'][$resource] = max((int) ($previous['_next_ids'][$resource] ?? 1), (int) ($snapshot['_next_ids'][$resource] ?? 1), max([0, ...array_column($snapshot[$resource] ?? [], 'id')]) + 1);
            }
            $id = Db::table('customer_service_config_revisions')->insertGetId([
                'snapshot_json' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'published' => $publish, 'operator_id' => $operatorId, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $update = ['draft_revision' => $id];
            if ($publish) {
                $update['published_revision'] = $id;
            }
            Db::table('customer_service_config_state')->where('id', 1)->update($update);
            return ['revision' => $id, 'snapshot' => $snapshot, 'published' => $publish];
        });
    }

    public function history(): array
    {
        return Db::table('customer_service_config_revisions')->orderByDesc('id')->limit(100)->get(['id', 'published', 'operator_id', 'created_at'])->map(static fn ($row) => (array) $row)->all();
    }

    public function revision(int $revision): array
    {
        $row = Db::table('customer_service_config_revisions')->where('id', $revision)->first() ?? throw CustomerServiceError::CONFIGURATION_REVISION_NOT_FOUND->internal();
        return ['revision' => (int) $row->id, 'snapshot' => json_decode($row->snapshot_json, true, 512, JSON_THROW_ON_ERROR), 'published' => (bool) $row->published];
    }
}
