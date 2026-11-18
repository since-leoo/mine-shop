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
use App\Infrastructure\Error\GroupBuyError;
use Carbon\Carbon;

final class DomainGroupBuyOrderService extends IService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
        private readonly GroupBuyOrderRepository $groupBuyOrderRepository,
    ) {}

    /**
     * 验证团购活动.
     */
    public function validateActivity(int $groupBuyId, int $skuId, int $quantity, int $memberId, ?string $groupNo, bool $buyOriginal = false): GroupBuyEntity
    {
        try {
            $entity = $this->groupBuyService->getEntity($groupBuyId);
        } catch (\DomainException) {
            throw GroupBuyError::NOT_FOUND->exception();
        }

        if ($entity->getSkuId() !== $skuId) {
            throw GroupBuyError::SKU_MISMATCH->exception();
        }

        if ($buyOriginal) {
            return $entity;
        }

        if (! $entity->canJoin()) {
            throw GroupBuyError::ACTIVITY_UNAVAILABLE->exception();
        }

        $remainingStock = $entity->getTotalQuantity() - $entity->getSoldQuantity();
        if ($remainingStock < $quantity) {
            throw GroupBuyError::ACTIVITY_STOCK_INSUFFICIENT->exception();
        }

        if ($this->hasMemberJoined($groupBuyId, $memberId)) {
            throw GroupBuyError::MEMBER_ALREADY_JOINED->exception();
        }

        if ($groupNo !== null) {
            $this->validateJoinGroup($groupNo, $groupBuyId, $entity->getMaxPeople());
        }

        return $entity;
    }

    /**
     * 创建团购订单.
     */
    public function createGroupBuyOrder(OrderEntity $orderEntity, GroupBuyEntity $entity): void
    {
        $this->groupBuyService->lock($entity->getId());
        $this->validateActivity($entity->getId(), $orderEntity->getItems()[0]->getSkuId(), $orderEntity->getItems()[0]->getQuantity(), $orderEntity->getMemberId(), $orderEntity->getExtra('group_no'));
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
     * 判断会员是否已加入该活动.
     */
    public function hasMemberJoined(int $groupBuyId, int $memberId): bool
    {
        return $this->groupBuyOrderRepository->hasJoined($groupBuyId, $memberId);
    }

    public function validateJoinGroup(string $groupNo, int $groupBuyId, int $maxPeople): void
    {
        $groupOrders = $this->groupBuyOrderRepository->findActiveByGroupNo($groupNo);
        if ($groupOrders === []) {
            throw GroupBuyError::GROUP_NOT_JOINABLE->exception();
        }

        if (\count($groupOrders) >= $maxPeople) {
            throw GroupBuyError::GROUP_NOT_JOINABLE->exception();
        }

        $leaderOrder = null;
        foreach ($groupOrders as $groupOrder) {
            if ((int) $groupOrder->group_buy_id !== $groupBuyId || $groupOrder->status === 'grouped') {
                throw GroupBuyError::GROUP_ACTIVITY_MISMATCH->exception();
            }
            if ($groupOrder->is_leader) {
                $leaderOrder = $groupOrder;
            }
        }

        if (! $leaderOrder || ($leaderOrder->expire_time && Carbon::now()->greaterThanOrEqualTo($leaderOrder->expire_time))) {
            throw GroupBuyError::GROUP_NOT_JOINABLE->exception();
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
