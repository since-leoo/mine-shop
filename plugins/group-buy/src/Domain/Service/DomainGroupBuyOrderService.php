<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Service;

use App\Domain\Trade\Order\Entity\OrderEntity;
use Carbon\Carbon;
use Plugin\Since\GroupBuy\Domain\Entity\GroupBuyEntity;
use Plugin\Since\GroupBuy\Infrastructure\Model\GroupBuyOrder;

final class DomainGroupBuyOrderService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
    ) {}

    public function validateActivity(int $groupBuyId, int $skuId, int $quantity, int $memberId, ?string $groupNo): GroupBuyEntity
    {
        try {
            $entity = $this->groupBuyService->getEntity($groupBuyId);
        } catch (\RuntimeException) {
            throw new \RuntimeException('拼团活动不存在');
        }
        if (! $entity->canJoin()) {
            throw new \RuntimeException('当前拼团活动不可参与');
        }
        if ($entity->getSkuId() !== $skuId) {
            throw new \RuntimeException('该商品不在当前拼团活动中');
        }
        $remainingStock = $entity->getTotalQuantity() - $entity->getSoldQuantity();
        if ($remainingStock < $quantity) {
            throw new \RuntimeException('拼团商品库存不足');
        }
        if ($this->hasMemberJoined($groupBuyId, $memberId)) {
            throw new \RuntimeException('每人每个活动限参一次');
        }
        if ($groupNo !== null) {
            $this->validateJoinGroup($groupNo, $entity->getMaxPeople());
        }
        return $entity;
    }

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

        GroupBuyOrder::create($record);
        $this->groupBuyService->increaseSoldQuantity($entity->getId(), $item->getQuantity());
        if ($isLeader) {
            $this->groupBuyService->increaseGroupCount($entity->getId());
        }
    }

    public function hasMemberJoined(int $groupBuyId, int $memberId): bool
    {
        return GroupBuyOrder::where('group_buy_id', $groupBuyId)
            ->where('member_id', $memberId)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->exists();
    }

    public function validateJoinGroup(string $groupNo, int $maxPeople): void
    {
        $groupOrders = GroupBuyOrder::where('group_no', $groupNo)
            ->whereNotIn('status', ['cancelled', 'failed'])->get();
        if ($groupOrders->isEmpty()) {
            throw new \RuntimeException('无法加入该团（团不存在、已满员或已过期）');
        }
        if ($groupOrders->count() >= $maxPeople) {
            throw new \RuntimeException('无法加入该团（团不存在、已满员或已过期）');
        }
        $leaderOrder = $groupOrders->firstWhere('is_leader', true);
        if ($leaderOrder && $leaderOrder->expire_time && Carbon::now()->greaterThan($leaderOrder->expire_time)) {
            throw new \RuntimeException('无法加入该团（团不存在、已满员或已过期）');
        }
    }

    private function findLeaderOrder(string $groupNo): ?GroupBuyOrder
    {
        return GroupBuyOrder::where('group_no', $groupNo)->where('is_leader', true)->first();
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
