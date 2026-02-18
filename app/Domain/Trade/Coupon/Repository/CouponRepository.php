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

namespace App\Domain\Trade\Coupon\Repository;

use App\Domain\Trade\Coupon\Entity\CouponEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Coupon\Coupon;
use App\Infrastructure\Model\Coupon\CouponUser;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;

/**
 * 优惠券仓储.
 *
 * @extends IRepository<Coupon>
 */
final class CouponRepository extends IRepository
{
    /**
     * 构造函数.
     *
     * @param Coupon $model 优惠券模型实例
     */
    public function __construct(protected readonly Coupon $model) {}

    /**
     * 根据实体创建优惠券记录.
     *
     * @param CouponEntity $entity 优惠券实体对象
     * @return Coupon 创建后的优惠券模型实例
     */
    public function createFromEntity(CouponEntity $entity): Coupon
    {
        $coupon = Coupon::create($entity->toArray());
        $entity->setId((int) $coupon->id);
        return $coupon;
    }

    /**
     * 根据实体更新优惠券记录.
     *
     * @param CouponEntity $entity 优惠券实体对象
     * @return bool 更新是否成功
     */
    public function updateFromEntity(CouponEntity $entity): bool
    {
        $coupon = Coupon::find($entity->getId());
        if ($coupon === null) {
            return false;
        }

        return $coupon->update($entity->toArray());
    }

    /**
     * 获取优惠券统计数据.
     *
     * @return array 包含总数、激活数、未激活数的统计数组
     */
    public function getStatistics(): array
    {
        return [
            'total' => Coupon::count(),
            'active' => Coupon::where('status', 'active')->count(),
            'inactive' => Coupon::where('status', 'inactive')->count(),
        ];
    }

    /**
     * 统计指定优惠券的发放数量.
     *
     * @param int $couponId 优惠券ID
     * @return int 发放数量
     */
    public function countIssued(int $couponId): int
    {
        return CouponUser::where('coupon_id', $couponId)->count();
    }

    /**
     * 统计指定会员领取指定优惠券的数量.
     *
     * @param int $couponId 优惠券ID
     * @param int $memberId 会员ID
     * @return int 领取数量
     */
    public function countIssuedByMember(int $couponId, int $memberId): int
    {
        return CouponUser::where('coupon_id', $couponId)
            ->where('member_id', $memberId)
            ->count();
    }

    /**
     * @return Collection<int, Coupon>
     */
    public function listAvailable(array $filters = [], int $limit = 20): Collection
    {
        /* @var Collection<int, Coupon> $result */
        return $this->buildAvailableQuery($filters)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function countAvailable(array $filters = []): int
    {
        return $this->buildAvailableQuery($filters)->count();
    }

    /**
     * 同步更新优惠券使用统计信息.
     *
     * @param int $couponId 优惠券ID
     */
    public function syncUsageStatistics(int $couponId): void
    {
        // 统计指定优惠券的已使用数量
        $used = CouponUser::where('coupon_id', $couponId)
            ->where('status', 'used')
            ->count();

        // 更新优惠券表中的已使用数量字段
        Coupon::whereKey($couponId)->update(['used_quantity' => $used]);
    }

    /**
     * 处理优惠券搜索查询条件.
     *
     * @param Builder $query 查询构建器
     * @param array $params 搜索参数数组
     * @return Builder 处理后的查询构建器
     */
    public function handleSearch(Builder $query, array $params): Builder
    {
        // 根据搜索参数构建查询条件，包括名称模糊匹配、类型、状态、时间范围等筛选条件，并统计发放数量
        return $query
            ->when(isset($params['name']), static fn (Builder $q) => $q->where('name', 'like', '%' . $params['name'] . '%'))
            ->when(isset($params['type']), static fn (Builder $q) => $q->where('type', $params['type']))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['start_time']), static fn (Builder $q) => $q->where('start_time', '>=', $params['start_time']))
            ->when(isset($params['end_time']), static fn (Builder $q) => $q->where('end_time', '<=', $params['end_time']))
            ->withCount(['users as issued_quantity']) // 统计每个优惠券的发放数量
            ->orderByDesc('id'); // 按ID降序排列
    }

    /**
     * 导出数据提供者.
     */
    public function getExportData(array $params): iterable
    {
        $query = $this->perQuery($this->getQuery(), $params);

        foreach ($query->cursor() as $coupon) {
            $data = $coupon->toArray();
            // 面值/折扣需要根据类型计算显示值
            $data['value_display'] = $coupon->type === 'percent'
                ? ($coupon->value / 10) . '折'
                : '¥' . number_format(($coupon->value ?? 0) / 100, 2);
            yield $data;
        }
    }

    private function buildAvailableQuery(array $filters = []): Builder
    {
        $now = Carbon::now();
        $query = $this->getModel()
            ->newQuery()
            ->where('status', 'active')
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->whereColumn('used_quantity', '<', 'total_quantity');

        if (isset($filters['spu_id']) && (int) $filters['spu_id'] > 0) {
            // 预留扩展：按商品筛选可用优惠券
        }

        return $query;
    }
}
