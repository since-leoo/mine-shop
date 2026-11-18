<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

use App\Infrastructure\Error\CustomerServiceError;
use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;

final class DomainConfigurationService
{
    public const RESOURCES = ['categories', 'keyword_rules', 'skill_groups', 'agent_skills', 'ai_connections', 'ai_agents'];
    public const DEFAULTS = [
        'enabled' => true, 'ai_enabled' => false, 'default_ai_agent_id' => null, 'fallback_group_id' => null,
        'assignment_strategy' => 'least_load', 'queue_enabled' => true, 'max_queue_size' => 100,
        'queue_timeout_seconds' => 600, 'accept_timeout_seconds' => 60, 'max_conversations_per_agent' => 5,
        'history_page_size' => 30, 'max_message_length' => 1000, 'max_image_size_mb' => 5,
        'allow_member_image' => true, 'allow_product_card' => true, 'ai_timeout_seconds' => 15,
        'ai_max_clarifications' => 1, 'ai_max_rounds' => 8, 'ai_daily_token_budget' => 100000,
        'welcome_message' => '您好，请问有什么可以帮助您？', 'offline_message' => '当前暂无客服在线，请留下您的问题。',
        'schedule' => [],
    ];

    public function __construct(private readonly SupportConfigurationInterface $repository, private readonly DomainCategoryService $categories, private readonly \Plugin\CustomerService\Service\CustomerServiceSettingsResolver $settings) {}

    public function defaults(array $snapshot): array
    {
        $snapshot['settings'] = array_replace(self::DEFAULTS, $this->settings->toArray(), $snapshot['settings'] ?? []);
        foreach (self::RESOURCES as $resource) {
            $snapshot[$resource] ??= [];
        }
        return $snapshot;
    }

    public function validate(array $snapshot, bool $publishing = false): array
    {
        $snapshot = $this->defaults($snapshot);
        if (array_diff(array_keys($snapshot), [...self::RESOURCES, 'settings', '_next_ids'])) { throw CustomerServiceError::CONFIGURATION_GROUP_UNKNOWN->invalid(); }
        if (array_diff(array_keys($snapshot['settings']), [...array_keys(self::DEFAULTS), 'gateway_url', 'socket_path'])) { throw CustomerServiceError::CONFIGURATION_SETTING_UNKNOWN->invalid(); }
        foreach (self::RESOURCES as $resource) {
            if (!is_array($snapshot[$resource]) || count($snapshot[$resource]) > 2000) { throw CustomerServiceError::CONFIGURATION_RECORDS_INVALID->invalid(); }
            foreach ($snapshot[$resource] as $record) { if (!is_array($record)) { throw CustomerServiceError::CONFIGURATION_RECORD_INVALID->invalid(); } $this->validateRecord($resource, $record); }
        }
        $settings = $snapshot['settings'];
        foreach (['max_queue_size', 'queue_timeout_seconds', 'accept_timeout_seconds', 'max_conversations_per_agent', 'history_page_size', 'max_message_length', 'max_image_size_mb', 'ai_timeout_seconds', 'ai_max_rounds', 'ai_daily_token_budget'] as $key) {
            if (!is_numeric($settings[$key]) || (int) $settings[$key] < 1 || (int) $settings[$key] > 10000000) {
                throw CustomerServiceError::POSITIVE_INTEGER_REQUIRED->invalid([$key]);
            }
            if ((string) (int) $settings[$key] !== (string) $settings[$key]) { throw CustomerServiceError::INTEGER_REQUIRED->invalid([$key]); }
            $snapshot['settings'][$key] = (int) $settings[$key];
        }
        if ((int) $settings['ai_max_clarifications'] < 0 || (int) $settings['ai_max_clarifications'] > 5 || (int) $settings['ai_timeout_seconds'] > 60) {
            throw CustomerServiceError::AI_CLARIFICATION_OR_TIMEOUT_INVALID->invalid();
        }
        if (!in_array($settings['assignment_strategy'], ['least_load', 'round_robin', 'weighted', 'manual'], true)) {
            throw CustomerServiceError::ASSIGNMENT_STRATEGY_INVALID->invalid();
        }
        foreach (['enabled', 'ai_enabled', 'queue_enabled', 'allow_member_image', 'allow_product_card'] as $key) {
            if (!is_bool($settings[$key])) {
                throw CustomerServiceError::BOOLEAN_REQUIRED->invalid([$key]);
            }
        }
        $this->validateSchedule($settings['schedule'] ?? []);
        $snapshot['categories'] = $this->categories->validate($snapshot['categories']);
        $ids = [];
        foreach (self::RESOURCES as $resource) {
            $ids[$resource] = [];
            foreach ($snapshot[$resource] as $entry) {
                if (!is_array($entry) || (int) ($entry['id'] ?? 0) <= 0 || isset($ids[$resource][$entry['id']])) {
                    throw CustomerServiceError::CONFIGURATION_ID_INVALID->invalid();
                }
                $ids[$resource][(int) $entry['id']] = $entry;
            }
        }
        $requireRef = static function (string $resource, mixed $id) use ($ids): void {
            if ($id && !isset($ids[$resource][(int) $id])) {
                throw CustomerServiceError::CONFIGURATION_REFERENCE_NOT_FOUND->exception([$resource]);
            }
        };
        foreach ($snapshot['categories'] as $category) {
            $requireRef('skill_groups', $category['default_group_id'] ?? null);
            $requireRef('ai_agents', $category['default_ai_agent_id'] ?? null);
        }
        foreach ($snapshot['keyword_rules'] as $rule) {
            $requireRef('categories', $rule['category_id'] ?? null);
            if (empty($rule['category_id']) || !in_array($rule['match_type'] ?? '', ['exact', 'contains', 'any', 'all'], true) || empty($rule['include_terms'])) {
                throw CustomerServiceError::KEYWORD_RULE_INCOMPLETE->exception();
            }
            foreach (array_merge($rule['include_terms'], $rule['exclude_terms'] ?? []) as $term) {
                if (!is_string($term) || trim($term) === '' || mb_strlen($term) > 100) {
                    throw CustomerServiceError::KEYWORD_LENGTH_INVALID->invalid();
                }
            }
        }
        foreach ($snapshot['skill_groups'] as $group) {
            $visited = [(int) $group['id']];
            $next = (int) ($group['fallback_group_id'] ?? 0);
            while ($next) {
                $requireRef('skill_groups', $next);
                if (in_array($next, $visited, true)) {
                    throw CustomerServiceError::SKILL_FALLBACK_CYCLE->exception();
                }
                $visited[] = $next;
                $next = (int) ($ids['skill_groups'][$next]['fallback_group_id'] ?? 0);
            }
        }
        foreach ($snapshot['agent_skills'] as $skill) {
            $requireRef('categories', $skill['category_id'] ?? null);
            $requireRef('skill_groups', $skill['group_id'] ?? null);
            if ((int) ($skill['agent_id'] ?? 0) < 1 || empty($skill['group_id'])) {
                throw CustomerServiceError::SKILL_BINDING_INCOMPLETE->exception();
            }
        }
        foreach ($snapshot['ai_connections'] as $connection) {
            if (isset($connection['api_key']) || isset($connection['secret'])) {
                throw CustomerServiceError::PLAINTEXT_SECRET_FORBIDDEN->invalid();
            }
            $url = parse_url((string) ($connection['endpoint'] ?? ''));
            if (($url['scheme'] ?? '') !== 'https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment']) || (isset($url['port']) && $url['port'] !== 443)) {
                throw CustomerServiceError::AI_URL_INVALID->invalid();
            }
            if (!preg_match('/^CUSTOMER_SERVICE_AI_[A-Z0-9_]+_KEY$/D', $connection['credential_env'] ?? '')) {
                throw CustomerServiceError::AI_SECRET_REFERENCE_INVALID->invalid();
            }
        }
        foreach ($snapshot['ai_agents'] as $agent) {
            $requireRef('ai_connections', $agent['connection_id'] ?? null);
            $requireRef('skill_groups', $agent['fallback_group_id'] ?? null);
            if (empty($agent['connection_id']) || trim($agent['model'] ?? '') === '' || trim($agent['prompt'] ?? '') === '') {
                throw CustomerServiceError::AI_AGENT_INCOMPLETE->exception();
            }
            foreach ($agent['category_ids'] ?? [] as $categoryId) {
                $requireRef('categories', $categoryId);
            }
        }
        $requireRef('skill_groups', $settings['fallback_group_id']);
        $requireRef('ai_agents', $settings['default_ai_agent_id']);
        if ($publishing && $settings['ai_enabled']) {
            $agent = $ids['ai_agents'][(int) $settings['default_ai_agent_id']] ?? null;
            $connection = $agent ? ($ids['ai_connections'][(int) $agent['connection_id']] ?? null) : null;
            if (!$agent || !($agent['enabled'] ?? true) || !$connection || !($connection['enabled'] ?? true)) {
                throw CustomerServiceError::DEFAULT_AI_AGENT_UNAVAILABLE->exception();
            }
        }
        return $snapshot;
    }

    public function edit(string $resource, ?int $id, array $input, int $expectedRevision, int $operatorId, bool $delete = false): array
    {
        if (!in_array($resource, self::RESOURCES, true)) {
            throw CustomerServiceError::CONFIGURATION_TYPE_UNKNOWN->invalid();
        }
        $draft = $this->repository->draft();
        if ($draft['revision'] !== $expectedRevision) {
            throw CustomerServiceError::CONFIGURATION_REVISION_CONFLICT->exception();
        }
        $snapshot = $this->defaults($draft['snapshot']);
        $items = $snapshot[$resource];
        $creating = $id === null;
        if ($id === null) {
            $id = max((int) ($snapshot['_next_ids'][$resource] ?? 1), max([0, ...array_column($items, 'id')]) + 1);
            $snapshot['_next_ids'][$resource] = $id + 1;
        }
        $found = false;
        foreach ($items as $index => $item) {
            if ((int) $item['id'] === $id) {
                $found = true;
                if ($delete) {
                    unset($items[$index]);
                } else {
                    $items[$index] = array_replace($item, $input, ['id' => $id]);
                }
            }
        }
        if (!$found && (!$creating || $delete)) {
            throw CustomerServiceError::CONFIGURATION_RECORD_NOT_FOUND->internal();
        }
        if (!$found) {
            $items[] = array_replace(['enabled' => true], $input, ['id' => $id]);
        }
        $snapshot[$resource] = array_values($items);
        return $this->repository->save($this->validate($snapshot), $expectedRevision, $operatorId, false);
    }

    /** @param value-of<self::RESOURCES> $resource */
    private function validateRecord(string $resource, array $record): void
    {
        $fields = match ($resource) {
            'categories' => ['parent_id', 'code', 'depth', 'path', 'default_group_id', 'default_ai_agent_id', 'force_human', 'priority', 'sla_seconds'],
            'keyword_rules' => ['category_id', 'match_type', 'include_terms', 'exclude_terms', 'priority', 'channels'],
            'skill_groups' => ['fallback_group_id'],
            'agent_skills' => ['agent_id', 'category_id', 'group_id', 'priority', 'include_descendants'],
            'ai_connections' => ['provider', 'endpoint', 'credential_env', 'model_capabilities'],
            'ai_agents' => ['connection_id', 'model', 'prompt', 'category_ids', 'tool_allowlist', 'fallback_group_id', 'max_output_tokens', 'retrieval_threshold', 'avatar'],
        };
        if (array_diff(array_keys($record), ['id', 'name', 'enabled', 'sort', ...$fields])) { throw CustomerServiceError::CONFIGURATION_FIELD_UNSUPPORTED->invalid([$resource]); }
        foreach (['id', 'parent_id', 'category_id', 'agent_id', 'connection_id', 'group_id', 'default_group_id', 'default_ai_agent_id', 'fallback_group_id', 'priority', 'sort'] as $key) {
            if (isset($record[$key]) && (!is_int($record[$key]) || ($key !== 'priority' && $record[$key] < 0))) { throw CustomerServiceError::VALID_INTEGER_REQUIRED->invalid([$key]); }
        }
        foreach (['enabled', 'force_human', 'include_descendants'] as $key) {
            if (isset($record[$key]) && !is_bool($record[$key])) { throw CustomerServiceError::BOOLEAN_REQUIRED->invalid([$key]); }
        }
        if ($resource !== 'agent_skills' && !isset($record['name'])) { throw CustomerServiceError::CONFIGURATION_NAME_REQUIRED->invalid(); }
        if (isset($record['name']) && (!is_string($record['name']) || trim($record['name']) === '' || mb_strlen($record['name']) > 100)) { throw CustomerServiceError::NAME_INVALID->invalid(); }
        if ($resource === 'ai_agents') {
            if (mb_strlen((string) ($record['prompt'] ?? '')) > 4000) { throw CustomerServiceError::AI_PROMPT_TOO_LONG->invalid(); }
            if (array_diff($record['tool_allowlist'] ?? [], ['order_status'])) { throw CustomerServiceError::AI_TOOL_UNAVAILABLE->invalid(); }
            if (isset($record['retrieval_threshold']) && (!is_numeric($record['retrieval_threshold']) || $record['retrieval_threshold'] < 0.2 || $record['retrieval_threshold'] > 1)) { throw CustomerServiceError::RETRIEVAL_THRESHOLD_INVALID->invalid(); }
            if (isset($record['max_output_tokens']) && (!is_int($record['max_output_tokens']) || $record['max_output_tokens'] < 128 || $record['max_output_tokens'] > 4096)) { throw CustomerServiceError::OUTPUT_TOKEN_LIMIT_INVALID->invalid(); }
        }
    }

    public function validateSchedule(array $schedule): void
    {
        if (!$schedule) {
            return;
        }
        try {
            new \DateTimeZone($schedule['timezone'] ?? 'Asia/Shanghai');
        } catch (\Throwable) {
            throw CustomerServiceError::TIMEZONE_INVALID->invalid();
        }
        foreach (array_merge(array_values($schedule['weekly'] ?? []), array_values($schedule['exceptions'] ?? [])) as $slots) {
            foreach ($slots as $slot) {
                if (count($slot) !== 2 || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $slot[0]) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $slot[1]) || $slot[0] >= $slot[1]) {
                    throw CustomerServiceError::BUSINESS_HOURS_INVALID->invalid();
                }
            }
        }
    }
}
