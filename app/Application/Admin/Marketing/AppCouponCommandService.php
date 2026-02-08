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

namespace App\Application\Admin\Marketing;

use App\Domain\Marketing\Coupon\Contract\CouponInput;
use App\Domain\Marketing\Coupon\Service\DomainCouponService;
use App\Infrastructure\Abstract\IService;

/**
 * 优惠券命令服务.
 */
final class AppCouponCommandService extends IService
{
    /**
     * 构造函数.
     */
    public function __construct(
        private readonly DomainCouponService $couponService,
        private readonly AppCouponQueryService $queryService
    ) {}

    /**
     * 创建优惠券.
     *
     * @param CouponInput $dto 优惠券输入数据传输对象
     * @return bool 创建后的优惠券对象
     */
    public function create(CouponInput $dto): bool
    {
        return $this->couponService->create($dto);
    }

    /**
     * 更新优惠券.
     *
     * @param CouponInput $dto 优惠券输入数据传输对象
     * @return bool 更新结果
     * @throws \Exception
     */
    public function update(CouponInput $dto): bool
    {
        return $this->couponService->update($dto);
    }

    /**
     * 切换优惠券状态
     *
     * @param int $id 优惠券ID
     * @return bool 状态切换结果
     */
    public function toggleStatus(int $id): bool
    {
        $entity = $this->couponService->getEntity($id);
        return $this->couponService->toggleStatus($entity);
    }
}
