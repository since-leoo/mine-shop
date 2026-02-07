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

namespace App\Domain\Order\Service;

use App\Domain\Coupon\Service\CouponUserService;
use App\Domain\Member\Service\MemberAddressService;
use App\Domain\Order\Contract\OrderPreviewInput;
use App\Domain\Order\Contract\OrderSubmitInput;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Order\Mapper\OrderMapper;
use App\Domain\Order\Repository\OrderRepository;
use App\Domain\Order\ValueObject\OrderAddressValue;
use App\Domain\SystemSetting\Service\MallSettingService;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Order\Order;
use App\Interface\Common\ResultCode;

final class OrderService extends IService
{
    public function __construct(
        public readonly OrderRepository $repository,
        private readonly OrderTypeStrategyFactory $strategyFactory,
        private readonly OrderStockService $stockService,
        private readonly MallSettingService $mallSettingService,
        private readonly MemberAddressService $addressService,
        private readonly CouponUserService $couponUserService,
    ) {}

    /**
     * 更新订单.
     */
    public function update(OrderEntity $entity): bool
    {
        return $this->repository->updateById($entity->getId(), $entity->toArray());
    }

    /**
     * 获取订单.
     */
    public function getEntity(int $id = 0, string $orderNo = ''): OrderEntity
    {
        /** @var null|Order $order */
        $order = $id ? $this->repository->findById($id) : $this->repository->findByOrderNo($orderNo);

        $orderEntity = OrderMapper::fromModel($order);

        if ($orderEntity->getMemberId() !== memberId()) {
            throw new BusinessException(ResultCode::FORBIDDEN, '订单不存在');
        }

        return $orderEntity;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function stats(array $filters): array
    {
        return $this->repository->stats($filters);
    }

    public function findDetail(int $id): ?array
    {
        return $this->repository->findDetail($id);
    }

    /**
     * 预览订单.
     */
    public function preview(OrderPreviewInput $input): OrderEntity
    {
        // 构建 Entity
        $entity = $this->buildEntityFromInput($input);
        // 获取商品配置
        $entity->guardPreorderAllowed($this->mallSettingService->product()->allowPreorder());
        // 构建策略
        $strategy = $this->strategyFactory->make($entity->getOrderType());
        // 策略验证
        $strategy->validate($entity);
        // 构建订单
        $strategy->buildDraft($entity);
        // 优惠券
        $strategy->applyCoupon($entity, $input->getCouponList() ?? []);
        // 调整价格
        $strategy->adjustPrice($entity);

        return $entity;
    }

    /**
     * 提交订单.
     *
     * @throws \Throwable
     */
    public function submit(OrderSubmitInput $input): OrderEntity
    {
        $entity = $this->buildEntityFromInput($input);
        $entity->guardPreorderAllowed($this->mallSettingService->product()->allowPreorder());
        $entity->applySubmissionPolicy($this->mallSettingService->order());
        $strategy = $this->strategyFactory->make($entity->getOrderType());
        $strategy->validate($entity);
        // 先 buildDraft（校验商品状态），再扣库存
        $strategy->buildDraft($entity);
        $strategy->applyCoupon($entity, $input->getCouponList() ?? []);
        $strategy->adjustPrice($entity);
        // 价格校验
        $entity->verifyPrice($input->getTotalAmount());
        // 库存扣减
        $items = array_map(static fn ($item) => $item->toArray(), $entity->getItems());
        $locks = $this->stockService->acquireLocks($items);
        try {
            $this->stockService->reserve($items);
            try {
                $entity = $this->repository->save($entity);
                // 标记优惠券已使用
                $this->markCouponsUsed($entity);
                $strategy->postCreate($entity);
            } catch (\Throwable $e) {
                $this->stockService->rollback($items);
                throw $e;
            }
        } finally {
            $this->stockService->releaseLocks($locks);
        }

        return $entity;
    }

    public function ship(OrderEntity $entity): OrderEntity
    {
        $entity->ensureShippable($this->mallSettingService->shipping());
        $this->repository->ship($entity);

        return $entity;
    }

    public function cancel(OrderEntity $entity): OrderEntity
    {
        $this->repository->cancel($entity);

        return $entity;
    }

    public function countByMemberAndStatuses(int $memberId): array
    {
        return $this->repository->countByMemberAndStatuses($memberId);
    }

    /**
     * 从 Input 构建 Entity（原 PayloadFactory 逻辑下沉到领域层）.
     */
    private function buildEntityFromInput(OrderPreviewInput $input): OrderEntity
    {
        $entity = OrderMapper::getNewEntity();
        $entity->initFromInput($input);
        // 地址解析
        $address = $this->resolveAddress($input);
        if ($address) {
            $entity->setAddress($address);
        }

        return $entity;
    }

    /**
     * 解析用户地址：优先使用直接传入的地址，其次按 ID 查询，最后使用默认地址.
     */
    private function resolveAddress(OrderPreviewInput $input): ?OrderAddressValue
    {
        if ($input->getUserAddress()) {
            return OrderAddressValue::fromArray($input->getUserAddress());
        }
        if ($input->getAddressId()) {
            $detail = $this->addressService->detail($input->getMemberId(), $input->getAddressId());
            return OrderAddressValue::fromArray($detail);
        }
        $default = $this->addressService->default($input->getMemberId());
        return $default ? OrderAddressValue::fromArray($default) : null;
    }

    /**
     * 标记订单关联的优惠券为已使用.
     */
    private function markCouponsUsed(OrderEntity $entity): void
    {
        $couponUserIds = $entity->getAppliedCouponUserIds();
        if (empty($couponUserIds)) {
            return;
        }

        foreach ($couponUserIds as $couponUserId) {
            $couponUserEntity = $this->couponUserService->getEntity($couponUserId);
            $this->couponUserService->markUsed($couponUserEntity, $entity->getId());
        }
    }
}
