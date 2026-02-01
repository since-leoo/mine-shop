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

namespace App\Domain\Coupon\Service;

use App\Domain\Coupon\Entity\CouponEntity;
use App\Domain\Coupon\Repository\CouponRepository;
use App\Domain\Coupon\Repository\CouponUserRepository;

/**
 * 优惠券领域服务.
 */
final class CouponService
{
    public function __construct(
        private readonly CouponRepository $repository,
        private readonly CouponUserRepository $couponUserRepository
    ) {}

    /**
     * 分页查询优惠券列表.
     *
     * @param array $filters 查询条件
     * @param int $page 页码
     * @param int $pageSize 每页大小
     * @return array 分页结果数组
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    /**
     * 根据ID查找优惠券.
     *
     * @param int $id 优惠券ID
     * @return CouponEntity 优惠券对象或null
     * @throws \Exception
     */
    public function findById(int $id): CouponEntity
    {
        $info = $this->repository->findById($id);

        if ($info === null) {
            throw new \RuntimeException('优惠券不存在');
        }

        return $info;
    }

    /**
     * 获取优惠券统计信息.
     *
     * @return array 统计数据数组
     */
    public function stats(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * 创建优惠券.
     *
     * @param CouponEntity $entity 优惠券实体对象
     * @return bool 创建后的优惠券对象
     */
    public function create(CouponEntity $entity): bool
    {
        $entity->ensureTimeWindowIsValid();
        return (bool) $this->repository->createFromEntity($entity);
    }

    /**
     * 更新优惠券.
     *
     * @param CouponEntity $entity 优惠券实体对象
     * @return bool 更新是否成功
     */
    public function update(CouponEntity $entity): bool
    {
        $entity->ensureTimeWindowIsValid();
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 删除优惠券.
     *
     * @return bool 删除是否成功
     */
    public function delete(CouponEntity $entity): bool
    {
        // 检查是否有发放记录，有则不允许删除
        $issued = $this->couponUserRepository->countByCouponId($entity->getId());
        if ($issued > 0) {
            throw new \RuntimeException('已有发放记录，无法删除');
        }

        return $this->repository->deleteById($entity->getId()) > 0;
    }

    /**
     * 切换优惠券状态
     *
     * @return bool 状态切换是否成功
     */
    public function toggleStatus(CouponEntity $entity): bool
    {
        $stored = $this->repository->findById($entity->getId());
        if (! $stored) {
            throw new \InvalidArgumentException('优惠券不存在');
        }

        $stored->toggleStatus();
        return $this->repository->updateFromEntity($stored);
    }

    /**
     * 同步优惠券使用统计
     *
     * @param int $couponId 优惠券ID
     */
    public function syncUsage(int $couponId): void
    {
        $this->repository->syncUsageStatistics($couponId);
    }
}
