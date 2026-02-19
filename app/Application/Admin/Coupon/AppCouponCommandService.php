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

namespace App\Application\Admin\Coupon;

use App\Domain\Trade\Coupon\Contract\CouponInput;
use App\Domain\Trade\Coupon\Mapper\CouponMapper;
use App\Domain\Trade\Coupon\Service\DomainCouponService;
use App\Infrastructure\Abstract\IService;

/**
 * 优惠券应用层命令服务.
 *
 * 负责协调领域服务，处理 DTO 到实体的转换。
 */
final class AppCouponCommandService extends IService
{
    public function __construct(
        private readonly DomainCouponService $couponService,
        private readonly AppCouponQueryService $queryService
    ) {}

    /**
     * 创建优惠券.
     *
     * @param CouponInput $dto 优惠券输入 DTO
     * @return bool 是否创建成功
     */
    public function create(CouponInput $dto): bool
    {
        // 使用 Mapper 将 DTO 转换为实体
        $entity = CouponMapper::fromDto($dto);
        return (bool) $this->couponService->create($entity);
    }

    /**
     * 更新优惠券.
     *
     * @param CouponInput $dto 优惠券输入 DTO
     * @return bool 是否更新成功
     */
    public function update(CouponInput $dto): bool
    {
        // 从数据库获取实体并更新
        $entity = $this->couponService->getEntity($dto->getId());
        $entity->update($dto);
        return $this->couponService->update($entity);
    }

    /**
     * 切换优惠券状态.
     *
     * @param int $id 优惠券 ID
     * @return bool 是否操作成功
     */
    public function toggleStatus(int $id): bool
    {
        $entity = $this->couponService->getEntity($id);
        return $this->couponService->toggleStatus($entity);
    }
}
