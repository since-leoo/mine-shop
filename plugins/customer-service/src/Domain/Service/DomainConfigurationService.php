<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

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
        if (array_diff(array_keys($snapshot), [...self::RESOURCES, 'settings', '_next_ids'])) { throw new \InvalidArgumentException('配置包含未知分组'); }
        if (array_diff(array_keys($snapshot['settings']), [...array_keys(self::DEFAULTS), 'gateway_url', 'socket_path'])) { throw new \InvalidArgumentException('配置包含未知设置'); }
        foreach (self::RESOURCES as $resource) {
            if (!is_array($snapshot[$resource]) || count($snapshot[$resource]) > 2000) { throw new \InvalidArgumentException('配置记录过多或格式无效'); }
            foreach ($snapshot[$resource] as $record) { if (!is_array($record)) { throw new \InvalidArgumentException('配置记录格式无效'); } $this->validateRecord($resource, $record); }
        }
        $settings = $snapshot['settings'];
        foreach (['max_queue_size', 'queue_timeout_seconds', 'accept_timeout_seconds', 'max_conversations_per_agent', 'history_page_size', 'max_message_length', 'max_image_size_mb', 'ai_timeout_seconds', 'ai_max_rounds', 'ai_daily_token_budget'] as $key) {
            if (!is_numeric($settings[$key]) || (int) $settings[$key] < 1 || (int) $settings[$key] > 10000000) {
                throw new \InvalidArgumentException($key . ' 必须是有效正整数');
            }
            if ((string) (int) $settings[$key] !== (string) $settings[$key]) { throw new \InvalidArgumentException($key . ' 必须是整数'); }
            $snapshot['settings'][$key] = (int) $settings[$key];
        }
        if ((int) $settings['ai_max_clarifications'] < 0 || (int) $settings['ai_max_clarifications'] > 5 || (int) $settings['ai_timeout_seconds'] > 60) {
            throw new \InvalidArgumentException('AI 澄清次数或超时时间无效');
        }
        if (!in_array($settings['assignment_strategy'], ['least_load', 'round_robin', 'weighted', 'manual'], true)) {
            throw new \InvalidArgumentException('分配策略无效');
        }
        foreach (['enabled', 'ai_enabled', 'queue_enabled', 'allow_member_image', 'allow_product_card'] as $key) {
            if (!is_bool($settings[$key])) {
                throw new \InvalidArgumentException($key . ' 必须是布尔值');
            }
        }
        $this->validateSchedule($settings['schedule'] ?? []);
        $snapshot['categories'] = $this->categories->validate($snapshot['categories']);
        $ids = [];
        foreach (self::RESOURCES as $resource) {
            $ids[$resource] = [];
            foreach ($snapshot[$resource] as $entry) {
                if (!is_array($entry) || (int) ($entry['id'] ?? 0) <= 0 || isset($ids[$resource][$entry['id']])) {
                    throw new \InvalidArgumentException('配置编号重复或无效');
                }
                $ids[$resource][(int) $entry['id']] = $entry;
            }
        }
        $requireRef = static function (string $resource, mixed $id) use ($ids): void {
            if ($id && !isset($ids[$resource][(int) $id])) {
                throw new \DomainException('配置引用不存在：' . $resource);
            }
        };
        foreach ($snapshot['categories'] as $category) {
            $requireRef('skill_groups', $category['default_group_id'] ?? null);
            $requireRef('ai_agents', $category['default_ai_agent_id'] ?? null);
        }
        foreach ($snapshot['keyword_rules'] as $rule) {
            $requireRef('categories', $rule['category_id'] ?? null);
            if (empty($rule['category_id']) || !in_array($rule['match_type'] ?? '', ['exact', 'contains', 'any', 'all'], true) || empty($rule['include_terms'])) {
                throw new \DomainException('关键词规则需要目标类目、匹配方式和正向词');
            }
            foreach (array_merge($rule['include_terms'], $rule['exclude_terms'] ?? []) as $term) {
                if (!is_string($term) || trim($term) === '' || mb_strlen($term) > 100) {
                    throw new \InvalidArgumentException('关键词必须为 1 至 100 字的文本');
                }
            }
        }
        foreach ($snapshot['skill_groups'] as $group) {
            $visited = [(int) $group['id']];
            $next = (int) ($group['fallback_group_id'] ?? 0);
            while ($next) {
                $requireRef('skill_groups', $next);
                if (in_array($next, $visited, true)) {
                    throw new \DomainException('技能组兜底不能形成循环');
                }
                $visited[] = $next;
                $next = (int) ($ids['skill_groups'][$next]['fallback_group_id'] ?? 0);
            }
        }
        foreach ($snapshot['agent_skills'] as $skill) {
            $requireRef('categories', $skill['category_id'] ?? null);
            $requireRef('skill_groups', $skill['group_id'] ?? null);
            if ((int) ($skill['agent_id'] ?? 0) < 1 || empty($skill['group_id'])) {
                throw new \DomainException('技能绑定需要坐席及技能组');
            }
        }
        foreach ($snapshot['ai_connections'] as $connection) {
            if (isset($connection['api_key']) || isset($connection['secret'])) {
                throw new \InvalidArgumentException('请使用服务端环境变量引用，不接受明文密钥');
            }
            $url = parse_url((string) ($connection['endpoint'] ?? ''));
            if (($url['scheme'] ?? '') !== 'https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment']) || (isset($url['port']) && $url['port'] !== 443)) {
                throw new \InvalidArgumentException('AI 连接必须使用不含凭据的 HTTPS 地址');
            }
            if (!preg_match('/^CUSTOMER_SERVICE_AI_[A-Z0-9_]+_KEY$/D', $connection['credential_env'] ?? '')) {
                throw new \InvalidArgumentException('密钥引用必须使用 CUSTOMER_SERVICE_AI_*_KEY 环境变量');
            }
        }
        foreach ($snapshot['ai_agents'] as $agent) {
            $requireRef('ai_connections', $agent['connection_id'] ?? null);
            $requireRef('skill_groups', $agent['fallback_group_id'] ?? null);
            if (empty($agent['connection_id']) || trim($agent['model'] ?? '') === '' || trim($agent['prompt'] ?? '') === '') {
                throw new \DomainException('AI Agent 必须配置连接、模型和角色说明');
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
                throw new \DomainException('开启 AI 需要可用的默认 Agent 和连接');
            }
        }
        return $snapshot;
    }

    public function edit(string $resource, ?int $id, array $input, int $expectedRevision, int $operatorId, bool $delete = false): array
    {
        if (!in_array($resource, self::RESOURCES, true)) {
            throw new \InvalidArgumentException('未知客服配置类型');
        }
        $draft = $this->repository->draft();
        if ($draft['revision'] !== $expectedRevision) {
            throw new \DomainException('配置已被其他管理员更新，请刷新');
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
            throw new \RuntimeException('配置记录不存在');
        }
        if (!$found) {
            $items[] = array_replace(['enabled' => true], $input, ['id' => $id]);
        }
        $snapshot[$resource] = array_values($items);
        return $this->repository->save($this->validate($snapshot), $expectedRevision, $operatorId, false);
    }

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
        if (array_diff(array_keys($record), ['id', 'name', 'enabled', 'sort', ...$fields])) { throw new \InvalidArgumentException($resource . ' 包含未支持字段'); }
        foreach (['id', 'parent_id', 'category_id', 'agent_id', 'connection_id', 'group_id', 'default_group_id', 'default_ai_agent_id', 'fallback_group_id', 'priority', 'sort'] as $key) {
            if (isset($record[$key]) && (!is_int($record[$key]) || ($key !== 'priority' && $record[$key] < 0))) { throw new \InvalidArgumentException($key . ' 必须是有效整数'); }
        }
        foreach (['enabled', 'force_human', 'include_descendants'] as $key) {
            if (isset($record[$key]) && !is_bool($record[$key])) { throw new \InvalidArgumentException($key . ' 必须是布尔值'); }
        }
        if ($resource !== 'agent_skills' && !isset($record['name'])) { throw new \InvalidArgumentException('配置记录需要名称'); }
        if (isset($record['name']) && (!is_string($record['name']) || trim($record['name']) === '' || mb_strlen($record['name']) > 100)) { throw new \InvalidArgumentException('名称无效'); }
        if ($resource === 'ai_agents') {
            if (mb_strlen((string) ($record['prompt'] ?? '')) > 4000) { throw new \InvalidArgumentException('角色说明最多4000字'); }
            if (array_diff($record['tool_allowlist'] ?? [], ['order_status'])) { throw new \InvalidArgumentException('工具未注册或不在允许范围'); }
            if (isset($record['retrieval_threshold']) && (!is_numeric($record['retrieval_threshold']) || $record['retrieval_threshold'] < 0.2 || $record['retrieval_threshold'] > 1)) { throw new \InvalidArgumentException('知识检索阈值必须在0.2至1之间'); }
            if (isset($record['max_output_tokens']) && (!is_int($record['max_output_tokens']) || $record['max_output_tokens'] < 128 || $record['max_output_tokens'] > 4096)) { throw new \InvalidArgumentException('输出Token限制应为128至4096'); }
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
            throw new \InvalidArgumentException('时区无效');
        }
        foreach (array_merge(array_values($schedule['weekly'] ?? []), array_values($schedule['exceptions'] ?? [])) as $slots) {
            foreach ($slots as $slot) {
                if (count($slot) !== 2 || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $slot[0]) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $slot[1]) || $slot[0] >= $slot[1]) {
                    throw new \InvalidArgumentException('营业时段无效；跨日时段请拆成两天');
                }
            }
        }
    }
}
