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

namespace Plugin\Since\SystemMessage\Repository;

use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use Plugin\Since\SystemMessage\Model\MessageTemplate;

class TemplateRepository
{
    /**
     * 创建模板
     */
    public function create(array $data): MessageTemplate
    {
        return MessageTemplate::create($data);
    }

    /**
     * 根据ID查找模板
     */
    public function findById(int $id): ?MessageTemplate
    {
        return MessageTemplate::find($id);
    }

    /**
     * 获取模板列表
     */
    public function list(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = MessageTemplate::query();
        
        $this->applyFilters($query, $filters);
        
        $total = $query->count();
        $templates = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'data' => $templates,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
        ];
    }

    /**
     * 搜索模板
     */
    public function search(string $keyword, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = MessageTemplate::query();
        
        // 搜索关键词
        $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('title_template', 'like', "%{$keyword}%")
              ->orWhere('content_template', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%");
        });
        
        $this->applyFilters($query, $filters);
        
        $total = $query->count();
        $templates = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'data' => $templates,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
            'keyword' => $keyword,
        ];
    }

    /**
     * 获取模板分类
     */
    public function getCategories(): array
    {
        return MessageTemplate::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    /**
     * 获取活跃模板
     */
    public function getActiveTemplates(string $type = null): Collection
    {
        $query = MessageTemplate::where('is_active', true);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        return $query->orderBy('name')->get();
    }

    /**
     * 根据名称查找模板
     */
    public function findByName(string $name): ?MessageTemplate
    {
        return MessageTemplate::where('name', $name)->first();
    }

    /**
     * 根据类型获取模板
     */
    public function getByType(string $type): Collection
    {
        return MessageTemplate::where('type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * 根据分类获取模板
     */
    public function getByCategory(string $category): Collection
    {
        return MessageTemplate::where('category', $category)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * 获取最近使用的模板
     */
    public function getRecentlyUsed(int $limit = 10): Collection
    {
        return MessageTemplate::withCount('messages')
            ->where('is_active', true)
            ->orderBy('messages_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取热门模板
     */
    public function getPopular(int $limit = 10): Collection
    {
        return MessageTemplate::withCount(['messages' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }])
            ->where('is_active', true)
            ->orderBy('messages_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取模板统计
     */
    public function getStatistics(): array
    {
        $total = MessageTemplate::count();
        $active = MessageTemplate::where('is_active', true)->count();
        $inactive = MessageTemplate::where('is_active', false)->count();

        // 按类型统计
        $byType = MessageTemplate::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // 按分类统计
        $byCategory = MessageTemplate::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // 使用统计
        $usageStats = MessageTemplate::withCount('messages')
            ->selectRaw('
                COUNT(*) as total_templates,
                SUM(messages_count) as total_usage,
                AVG(messages_count) as avg_usage,
                MAX(messages_count) as max_usage
            ')
            ->first();

        return [
            'templates' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'by_type' => $byType,
                'by_category' => $byCategory,
            ],
            'usage' => [
                'total_usage' => $usageStats->total_usage ?? 0,
                'avg_usage' => round($usageStats->avg_usage ?? 0, 2),
                'max_usage' => $usageStats->max_usage ?? 0,
            ],
        ];
    }

    /**
     * 应用过滤条件
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }
}