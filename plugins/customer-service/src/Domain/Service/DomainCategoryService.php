<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

use App\Infrastructure\Error\CustomerServiceError;

final class DomainCategoryService
{
    /** Validate the entire candidate tree, including descendants of a moved node. */
    public function validate(array $categories): array
    {
        $byId = [];
        $names = $codes = [];
        foreach ($categories as $category) {
            $id = (int) ($category['id'] ?? 0);
            $parent = (int) ($category['parent_id'] ?? 0);
            $name = trim((string) ($category['name'] ?? ''));
            $code = trim((string) ($category['code'] ?? ''));
            if ($id <= 0 || isset($byId[$id]) || $name === '' || mb_strlen($name) > 60 || !preg_match('/^[a-zA-Z0-9_-]{1,60}$/D', $code)) {
                throw CustomerServiceError::CATEGORY_IDENTITY_INVALID->exception();
            }
            if (isset($names[$parent . ':' . $name]) || isset($codes[$code])) {
                throw CustomerServiceError::CATEGORY_DUPLICATE->exception();
            }
            $names[$parent . ':' . $name] = $codes[$code] = true;
            $byId[$id] = $category + ['parent_id' => 0, 'enabled' => true, 'sort' => 0];
        }
        foreach ($byId as $id => &$category) {
            $path = [$id];
            $cursor = (int) $category['parent_id'];
            while ($cursor !== 0) {
                if (!isset($byId[$cursor]) || in_array($cursor, $path, true)) {
                    throw CustomerServiceError::CATEGORY_PARENT_INVALID->exception();
                }
                $path[] = $cursor;
                if (count($path) > 3) {
                    throw CustomerServiceError::CATEGORY_DEPTH_EXCEEDED->exception();
                }
                $cursor = (int) $byId[$cursor]['parent_id'];
            }
            $category['depth'] = count($path);
            $category['path'] = array_reverse($path);
        }
        unset($category);
        return array_values($byId);
    }

    public function effective(array $categories, int $categoryId): ?array
    {
        $byId = array_column($this->validate($categories), null, 'id');
        if (!isset($byId[$categoryId])) {
            return null;
        }
        $result = $byId[$categoryId];
        foreach ($result['path'] as $id) {
            if (!($byId[$id]['enabled'] ?? true)) {
                return null;
            }
            // A parent's forced-human policy cannot be weakened by a descendant.
            $result['force_human'] = ($result['force_human'] ?? false) || ($byId[$id]['force_human'] ?? false);
        }
        foreach (['default_group_id', 'default_ai_agent_id', 'priority'] as $key) {
            foreach (array_reverse($result['path']) as $id) {
                if (isset($byId[$id][$key]) && $byId[$id][$key] !== '') {
                    $result[$key] = $byId[$id][$key];
                    break;
                }
            }
        }
        $result['path_names'] = array_map(static fn ($id) => $byId[$id]['name'], $result['path']);
        return $result;
    }
}
