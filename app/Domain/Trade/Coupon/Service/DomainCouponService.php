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

use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use App\Domain\Trade\Coupon\Contract\CouponInput;
use App\Domain\Trade\Coupon\Entity\CouponEntity;
use App\Domain\Trade\Coupon\Mapper\CouponMapper;
use App\Domain\Trade\Coupon\Repository\CouponRepository;
use App\Domain\Trade\Coupon\Repository\CouponUserRepository;
use App\Infrastructure\Model\Coupon\Coupon;

/**
 * 优惠券领域服务.
 */
final class DomainCouponService extends IService
{
    public function __construct(
        public readonly CouponRepository $repository,
        private readonly CouponUserRepository $couponUserRepository
    ) {}

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
     * @param CouponInput $dto 优惠券输入数据传输对象
     * @return bool 创建是否成功
     */
    public function create(CouponInput $dto): bool
    {
        $entity = CouponMapper::getNewEntity();
        $entity->create($dto);

        return (bool) $this->repository->createFromEntity($entity);
    }

    /**
     * 更新优惠券.
     *
     * @param CouponInput $dto 优惠券输入数据传输对象
     * @return bool 更新是否成功
     */
    public function update(CouponInput $dto): bool
    {
        $entity = $this->getEntity($dto->getId());
        $entity->update($dto);

        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 删除优惠券.
     *
     * @return int 删除是否成功
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
     * 切换优惠券状态
     *
     * @return bool 状态切换是否成功
     */
    public function toggleStatus(CouponEntity $entity): bool
    {
        $stored = $this->getEntity($entity->getId());
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
