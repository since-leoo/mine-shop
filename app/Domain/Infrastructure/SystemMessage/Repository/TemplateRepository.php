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

namespace App\Domain\Infrastructure\SystemMessage\Repository;

use App\Infrastructure\Model\SystemMessage\MessageTemplate;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

class TemplateRepository
{
    public function create(array $data): MessageTemplate
    {
        return MessageTemplate::create($data);
    }

    public function findById(int $id): ?MessageTemplate
    {
        return MessageTemplate::find($id);
    }

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
            'data' => $templates, 'total' => $total, 'page' => $page,
            'page_size' => $pageSize, 'total_pages' => ceil($total / $pageSize),
        ];
    }

    public function search(string $keyword, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = MessageTemplate::query();
        $query->where(static function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('title', 'like', "%{$keyword}%")
                ->orWhere('content', 'like', "%{$keyword}%")
                ->orWhere('remark', 'like', "%{$keyword}%");
        });
        $this->applyFilters($query, $filters);
        $total = $query->count();
        $templates = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();
        return [
            'data' => $templates, 'total' => $total, 'page' => $page,
            'page_size' => $pageSize, 'total_pages' => ceil($total / $pageSize),
            'keyword' => $keyword,
        ];
    }

    public function getCategories(): array
    {
        return MessageTemplate::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')->orderBy('category')
            ->pluck('count', 'category')->toArray();
    }

    public function getActiveTemplates(?string $type = null): Collection
    {
        $query = MessageTemplate::where('is_active', true);
        if ($type) {
            $query->where('type', $type);
        }
        return $query->orderBy('name')->get();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }
}
