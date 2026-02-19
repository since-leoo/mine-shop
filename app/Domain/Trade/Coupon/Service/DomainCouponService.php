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

namespace App\Domain\Trade\Coupon\Service;

use App\Domain\Trade\Coupon\Entity\CouponEntity;
use App\Domain\Trade\Coupon\Mapper\CouponMapper;
use App\Domain\Trade\Coupon\Repository\CouponRepository;
use App\Domain\Trade\Coupon\Repository\CouponUserRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Coupon\Coupon;
use App\Interface\Common\ResultCode;

/**
 * 优惠券领域服务.
 *
 * 负责优惠券的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainCouponService extends IService
{
    public function __construct(
        public readonly CouponRepository $repository,
        private readonly CouponUserRepository $couponUserRepository
    ) {}

    /**
     * 根据 ID 获取优惠券实体.
     *
     * @param int $id 优惠券 ID
     * @return CouponEntity 优惠券实体
     * @throws BusinessException 优惠券不存在时抛出
     */
    public function getEntity(int $id): CouponEntity
    {
        /** @var null|Coupon $coupon */
        $coupon = $this->findById($id);
        if (! $coupon) {
            throw new BusinessException(ResultCode::FORBIDDEN, '优惠券不存在');
        }

        return CouponMapper::fromModel($coupon);
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
     * @param CouponEntity $entity 优惠券实体
     * @return Coupon 创建的模型
     */
    public function create(CouponEntity $entity): Coupon
    {
        return $this->repository->createFromEntity($entity);
    }

    /**
     * 更新优惠券.
     *
     * @param CouponEntity $entity 更新后的实体
     * @return bool 是否更新成功
     */
    public function update(CouponEntity $entity): bool
    {
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 删除优惠券.
     *
     * @param int $id 优惠券 ID
     * @return int 删除的记录数
     * @throws \RuntimeException 有发放记录时抛出
     */
    public function deleteById(mixed $id): int
    {
        // 检查是否有发放记录，有则不允许删除
        $issued = $this->couponUserRepository->countByCouponId($id);
        if ($issued > 0) {
            throw new \RuntimeException('已有发放记录，无法删除');
        }

        return $this->repository->deleteById($id);
    }

    /**
     * 切换优惠券状态.
     *
     * @param CouponEntity $entity 优惠券实体
     * @return bool 状态切换是否成功
     */
    public function toggleStatus(CouponEntity $entity): bool
    {
        $stored = $this->getEntity($entity->getId());
        $stored->toggleStatus();
        return $this->repository->updateFromEntity($stored);
    }

    /**
     * 同步优惠券使用统计.
     *
     * @param int $couponId 优惠券ID
     */
    public function syncUsage(int $couponId): void
    {
        $this->repository->syncUsageStatistics($couponId);
    }
}
