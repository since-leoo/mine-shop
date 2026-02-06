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

namespace App\Application\Commad;

use App\Application\Query\CouponQueryService;
use App\Domain\Coupon\Contract\CouponInput;
use App\Domain\Coupon\Service\CouponService;

/**
 * 优惠券命令服务.
 */
final class CouponCommandService
{
    /**
     * 构造函数.
     */
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CouponQueryService $queryService
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
        // 验证优惠券是否存在
        $this->queryService->find($dto->getId());

        return $this->couponService->update($dto);
    }

    /**
     * 删除优惠券.
     *
     * @param int $id 优惠券ID
     * @return bool 删除结果
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        // 验证优惠券是否存在
        $couponEntity = $this->queryService->findEntity($id);

        return $this->couponService->delete($couponEntity);
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
