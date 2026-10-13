<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

final class DomainKeywordService
{
    public function __construct(private readonly DomainCategoryService $categories) {}

    public function identify(string $text, array $snapshot, ?int $selectedId = null, bool $confirmed = false, string $channel = 'miniapp'): array
    {
        $selected = $selectedId ? $this->categories->effective($snapshot['categories'] ?? [], $selectedId) : null;
        if ($confirmed && $selected) {
            return ['category_id' => $selectedId, 'source' => 'human', 'matches' => [], 'conflict' => false];
        }
        $text = $this->normalize($text);
        $matches = [];
        foreach ($snapshot['keyword_rules'] ?? [] as $rule) {
            if (!($rule['enabled'] ?? true) || (!empty($rule['channels']) && !in_array($channel, $rule['channels'], true))) {
                continue;
            }
            $category = $this->categories->effective($snapshot['categories'] ?? [], (int) $rule['category_id']);
            if (!$category || ($selected && !in_array($selectedId, $category['path'], true))) {
                continue;
            }
            $excluded = array_filter($rule['exclude_terms'] ?? [], fn ($term) => $this->normalize($term) !== '' && str_contains($text, $this->normalize($term)));
            if ($excluded) {
                continue;
            }
            $terms = array_values(array_unique(array_filter(array_map($this->normalize(...), $rule['include_terms'] ?? []))));
            $hits = array_filter($terms, static fn ($term) => str_contains($text, $term));
            $match = match ($rule['match_type'] ?? 'any') {
                'exact' => in_array($text, $terms, true),
                'all' => count($terms) > 0 && count($hits) === count($terms),
                default => count($hits) > 0,
            };
            if ($match) {
                $matches[] = ['rule_id' => (int) $rule['id'], 'category_id' => (int) $category['id'], 'priority' => (int) ($rule['priority'] ?? 0), 'exact' => ($rule['match_type'] ?? '') === 'exact' ? 1 : 0, 'specificity' => max(array_map('mb_strlen', $hits ?: $terms)), 'terms' => array_values($hits)];
            }
        }
        usort($matches, static fn ($a, $b) => [$b['priority'], $b['exact'], $b['specificity']] <=> [$a['priority'], $a['exact'], $a['specificity']]);
        $first = $matches[0] ?? null;
        $conflict = $first && count(array_filter($matches, static fn ($m) => $m['category_id'] !== $first['category_id'] && [$m['priority'], $m['exact'], $m['specificity']] === [$first['priority'], $first['exact'], $first['specificity']])) > 0;
        return ['category_id' => $conflict ? null : ($first['category_id'] ?? $selectedId), 'source' => $first ? 'keyword' : ($selectedId ? 'user' : 'unknown'), 'matches' => $matches, 'conflict' => (bool) $conflict];
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/[\s\p{P}]+/u', ' ', mb_strtolower(mb_convert_kana($text, 'asKV', 'UTF-8'))) ?? $text);
    }
}
