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

namespace App\Domain\Trade\GroupBuy\Service;

use App\Domain\Trade\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Infrastructure\Abstract\IService;
use Carbon\Carbon;

final class DomainGroupBuyOrderService extends IService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly GroupBuyOrderRepository $groupBuyOrderRepository,
    ) {}

    /**
     * 验证团购活动
     * @param int $groupBuyId
     * @param int $skuId
     * @param int $quantity
     * @param int $memberId
     * @param string|null $groupNo
     * @param bool $buyOriginal
     * @return GroupBuyEntity
     */
    public function validateActivity(int $groupBuyId, int $skuId, int $quantity, int $memberId, ?string $groupNo, bool $buyOriginal = false): GroupBuyEntity
    {
        try {
            $entity = $this->groupBuyService->getEntity($groupBuyId);
        } catch (\RuntimeException) {
            throw new \RuntimeException('Group buy activity not found');
        }

        if ($entity->getSkuId() !== $skuId) {
            throw new \RuntimeException('SKU does not belong to current group buy activity');
        }

        if ($buyOriginal) {
            return $entity;
        }

        if (! $entity->canJoin()) {
            throw new \RuntimeException('Current group buy activity is unavailable');
        }

        $remainingStock = $entity->getTotalQuantity() - $entity->getSoldQuantity();
        if ($remainingStock < $quantity) {
            throw new \RuntimeException('Insufficient group buy stock');
        }

        if ($this->hasMemberJoined($groupBuyId, $memberId)) {
            throw new \RuntimeException('Member has already joined this activity');
        }

        if ($groupNo !== null) {
            $this->validateJoinGroup($groupNo, $entity->getMaxPeople());
        }

        return $entity;
    }

    /**
     * 创建团购订单
     * @param OrderEntity $orderEntity
     * @param GroupBuyEntity $entity
     */
    public function createGroupBuyOrder(OrderEntity $orderEntity, GroupBuyEntity $entity): void
    {
        $item = $orderEntity->getItems()[0];
        $groupNo = $orderEntity->getExtra('group_no');
        $isLeader = $groupNo === null;
        $now = Carbon::now();

        $record = [
            'group_buy_id' => $entity->getId(),
            'order_id' => $orderEntity->getId(),
            'member_id' => $orderEntity->getMemberId(),
            'quantity' => $item->getQuantity(),
            'original_price' => $entity->getOriginalPrice(),
            'group_price' => $entity->getGroupPrice(),
            'total_amount' => $item->getTotalPrice(),
            'status' => 'pending',
            'join_time' => $now,
        ];

        if ($isLeader) {
            $record['group_no'] = $this->generateGroupNo();
            $record['share_code'] = $this->generateShareCode();
            $record['is_leader'] = true;
            $record['expire_time'] = $now->copy()->addHours($entity->getGroupTimeLimit());
        } else {
            $leaderOrder = $this->findLeaderOrder($groupNo);
            $record['group_no'] = $groupNo;
            $record['is_leader'] = false;
            $record['parent_order_id'] = $leaderOrder?->order_id;
            $record['expire_time'] = $leaderOrder?->expire_time;
        }

        $this->groupBuyOrderRepository->createRecord($record);
        $this->groupBuyService->increaseSoldQuantity($entity->getId(), $item->getQuantity());

        if ($isLeader) {
            $this->groupBuyService->increaseGroupCount($entity->getId());
        }
    }

    /**
     * 判断会员是否已加入该活动
     * @param int $groupBuyId
     * @param int $memberId
     * @return bool
     */
    public function hasMemberJoined(int $groupBuyId, int $memberId): bool
    {
        return $this->groupBuyOrderRepository->hasJoined($groupBuyId, $memberId);
    }

    public function validateJoinGroup(string $groupNo, int $maxPeople): void
    {
        $groupOrders = $this->groupBuyOrderRepository->findActiveByGroupNo($groupNo);
        if ($groupOrders === []) {
            throw new \RuntimeException('Unable to join this group');
        }

        if (\count($groupOrders) >= $maxPeople) {
            throw new \RuntimeException('Unable to join this group');
        }

        $leaderOrder = null;
        foreach ($groupOrders as $groupOrder) {
            if ($groupOrder->is_leader) {
                $leaderOrder = $groupOrder;
                break;
            }
        }

        if ($leaderOrder && $leaderOrder->expire_time && Carbon::now()->greaterThan($leaderOrder->expire_time)) {
            throw new \RuntimeException('Unable to join this group');
        }
    }

    private function findLeaderOrder(string $groupNo): ?object
    {
        return $this->groupBuyOrderRepository->findLeaderByGroupNo($groupNo);
    }

    private function generateGroupNo(): string
    {
        return 'GB' . date('Ymd') . mb_str_pad((string) random_int(0, 99999999), 8, '0', \STR_PAD_LEFT);
    }

    private function generateShareCode(): string
    {
        return bin2hex(random_bytes(8));
    }
}
