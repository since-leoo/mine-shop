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

namespace App\Domain\Member\Repository;

use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Enum\MemberLevel as MemberLevelEnum;
use App\Domain\Member\Enum\MemberSource;
use App\Domain\Member\Mapper\MemberMapper;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Member\Member;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * 会员仓储.
 *
 * 负责会员数据的持久化操作，包括增删改查、统计分析等。
 * 支持会员标签、钱包、等级等关联数据的加载。
 *
 * @extends IRepository<Member>
 */
final class MemberRepository extends IRepository
{
    public function __construct(protected readonly Member $model) {}

    /**
     * 处理列表项，加载关联数据.
     *
     * @param Collection $items 会员集合
     * @return Collection 加载关联后的会员集合
     */
    public function handleItems(Collection $items): Collection
    {
        return $items->map(static fn (Member $member) => $member->loads('wallet', 'pointsWallet', 'tags', 'levelDefinition'));
    }

    /**
     * 获取会员统计数据.
     *
     * @param array $filters 筛选条件
     * @return array<string, int> 统计数据（总数、今日新增、活跃、沉睡、封禁）
     */
    public function stats(array $filters = []): array
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $activeThreshold = $now->copy()->subDays(30);
        $query = $this->perQuery($this->getQuery(), $filters);

        $total = (clone $query)->count();
        $banned = (clone $query)->where('status', 'banned')->count();
        $active = (clone $query)->where('last_login_at', '>=', $activeThreshold)->count();
        $sleeping = (clone $query)->where(static function (Builder $q) use ($activeThreshold) {
            $q->whereNull('last_login_at')->orWhere('last_login_at', '<', $activeThreshold);
        })->count();

        $newToday = (clone $query)->whereBetween('created_at', [$today, $today->copy()->endOfDay()])->count();

        return [
            'total' => $total,
            'new_today' => $newToday,
            'active_30d' => $active,
            'sleeping_30d' => $sleeping,
            'banned' => $banned,
        ];
    }

    /**
     * 获取会员概览数据.
     *
     * 包含趋势图、来源分布、地区分布、等级分布等数据。
     *
     * @param array $filters 筛选条件，支持 trend_days 指定趋势天数（3-30）
     * @return array<string, mixed> 概览数据
     */
    public function overview(array $filters = []): array
    {
        $trendDays = ! empty($filters['trend_days']) ? (int) $filters['trend_days'] : 7;
        unset($filters['trend_days']);
        $trendDays = $trendDays > 0 ? min(30, max(3, $trendDays)) : 7;

        $query = $this->perQuery($this->getQuery(), $filters);

        $trend = $this->buildTrendSeries(clone $query, $trendDays);
        $sources = $this->buildSourceBreakdown(clone $query);
        $regions = $this->buildRegionBreakdown(clone $query);
        $levels = $this->buildLevelBreakdown(clone $query);

        return [
            'trend' => $trend,
            'source_breakdown' => $sources,
            'region_breakdown' => $regions,
            'level_breakdown' => $levels,
        ];
    }

    public function detail(int $id): ?array
    {
        /** @var null|Member $member */
        $member = $this->getQuery()->with(['tags', 'wallet', 'pointsWallet', 'levelDefinition'])->find($id);

        if (! $member) {
            return null;
        }

        return $member->loads('wallet', 'pointsWallet', 'tags', 'levelDefinition');
    }

    public function findByOpenid(string $openid): ?MemberEntity
    {
        /** @var null|Member $member */
        $member = $this->getQuery()->where('openid', $openid)->first();
        return $member ? MemberMapper::fromModel($member) : null;
    }

    public function findByInviteCode(string $inviteCode): ?MemberEntity
    {
        /** @var null|Member $member */
        $member = $this->getQuery()->where('invite_code', $inviteCode)->first();
        return $member ? MemberMapper::fromModel($member) : null;
    }

    /**
     * 查询指定会员的直接下级（分页）.
     *
     * @return array{list: array, total: int}
     */
    public function referralPage(int $memberId, int $page = 1, int $pageSize = 10): array
    {
        $paginator = $this->getQuery()
            ->where('referrer_id', $memberId)
            ->orderByDesc('id')
            ->paginate(perPage: $pageSize, page: $page);

        return [
            'list' => collect($paginator->items())->map(static fn (Member $m) => [
                'id' => $m->id,
                'nickname' => $m->nickname,
                'avatar' => $m->avatar,
                'created_at' => $m->created_at?->toDateTimeString(),
            ])->toArray(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * 查询指定会员的直接下级数量.
     */
    public function referralCount(int $memberId): int
    {
        return $this->getQuery()->where('referrer_id', $memberId)->count();
    }

    public function save(MemberEntity $entity): Member
    {
        return $this->model->newQuery()->create($entity->toArray());
    }

    public function updateEntity(MemberEntity $entity): bool
    {
        return $this->updateById($entity->getId(), $entity->toArray());
    }

    /**
     * @param int[] $tagIds
     */
    public function syncTags(int $memberId, array $tagIds): void
    {
        /** @var null|Member $member */
        $member = $this->getQuery()->find($memberId);
        if (! $member) {
            throw new \RuntimeException('会员不存在');
        }

        $member->tags()->sync($tagIds);
        $member->refresh();
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->with(['tags', 'wallet', 'pointsWallet', 'levelDefinition'])
            ->when(! empty($params['keyword']), static function (Builder $q) use ($params) {
                $keyword = trim((string) $params['keyword']);
                $q->where(static function (Builder $sub) use ($keyword) {
                    $sub->where('nickname', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%')
                        ->orWhere('openid', 'like', '%' . $keyword . '%');
                });
            })
            ->when(! empty($params['level']), static fn (Builder $q) => $q->where('level', $params['level']))
            ->when(! empty($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(! empty($params['source']), static fn (Builder $q) => $q->where('source', $params['source']))
            ->when(! empty($params['phone']), static fn (Builder $q) => $q->where('phone', 'like', '%' . $params['phone'] . '%'))
            ->when(! empty($params['tag_id']), static fn (Builder $q) => $q->whereHas('tags', static function (Builder $tagQuery) use ($params) {
                $tagQuery->where('member_tags.id', (int) $params['tag_id']);
            }))
            ->when(! empty($params['created_start']), static fn (Builder $q) => $q->whereDate('created_at', '>=', $params['created_start']))
            ->when(! empty($params['created_end']), static fn (Builder $q) => $q->whereDate('created_at', '<=', $params['created_end']))
            ->when(! empty($params['last_login_start']), static fn (Builder $q) => $q->whereDate('last_login_at', '>=', $params['last_login_start']))
            ->when(! empty($params['last_login_end']), static fn (Builder $q) => $q->whereDate('last_login_at', '<=', $params['last_login_end']))
            ->orderByDesc('id');
    }

    /**
     * @return array{
     *     labels: string[],
     *     new_members: int[],
     *     active_members: int[],
     * }
     */
    private function buildTrendSeries(Builder $query, int $days): array
    {
        $end = Carbon::today()->endOfDay();
        $start = Carbon::today()->subDays($days - 1)->startOfDay();

        $newMembers = (clone $query)
            ->selectRaw('DATE(created_at) as date_key, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date_key')
            ->pluck('total', 'date_key')
            ->map(static fn ($value) => (int) $value)
            ->toArray();

        $activeMembers = (clone $query)
            ->selectRaw('DATE(last_login_at) as date_key, COUNT(*) as total')
            ->whereNotNull('last_login_at')
            ->whereBetween('last_login_at', [$start, $end])
            ->groupBy('date_key')
            ->pluck('total', 'date_key')
            ->map(static fn ($value) => (int) $value)
            ->toArray();

        $labels = [];
        $newSeries = [];
        $activeSeries = [];

        for ($i = $days - 1; $i >= 0; --$i) {
            $date = Carbon::today()->subDays($i);
            $key = $date->toDateString();
            $labels[] = $date->format('m-d');
            $newSeries[] = $newMembers[$key] ?? 0;
            $activeSeries[] = $activeMembers[$key] ?? 0;
        }

        return [
            'labels' => $labels,
            'new_members' => $newSeries,
            'active_members' => $activeSeries,
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, value: int}>
     */
    private function buildSourceBreakdown(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('COALESCE(source, \'unknown\') as source_key, COUNT(*) as total')
            ->groupBy('source_key')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(static function ($row) {
                $key = (string) $row->source_key;
                $label = MemberSource::tryFrom($key)?->label() ?? ($key === 'unknown' ? '未标记' : $key);
                return [
                    'key' => $key,
                    'label' => $label,
                    'value' => (int) $row->total,
                ];
            })
            ->toArray();
    }

    /**
     * @return array<int, array{key: string, label: string, value: int}>
     */
    private function buildRegionBreakdown(Builder $query): array
    {
        $fallbackLabel = '未填写地区';

        return (clone $query)
            ->selectRaw("COALESCE(NULLIF(province, ''), ?) as region_key, COUNT(*) as total", [$fallbackLabel])
            ->groupBy('region_key')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(static function ($row) {
                $label = (string) $row->region_key;
                return [
                    'key' => $label,
                    'label' => $label,
                    'value' => (int) $row->total,
                ];
            })
            ->toArray();
    }

    /**
     * @return array<int, array{key: string, label: string, value: int}>
     */
    private function buildLevelBreakdown(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('COALESCE(level, \'unranked\') as level_key, COUNT(*) as total')
            ->groupBy('level_key')
            ->orderByDesc('total')
            ->get()
            ->map(static function ($row) {
                $key = (string) $row->level_key;
                $label = MemberLevelEnum::tryFrom($key)?->label() ?? ($key === 'unranked' ? '未分级' : $key);
                return [
                    'key' => $key,
                    'label' => $label,
                    'value' => (int) $row->total,
                ];
            })
            ->toArray();
    }
}
