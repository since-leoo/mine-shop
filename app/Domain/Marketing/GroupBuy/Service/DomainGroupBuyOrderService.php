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

namespace App\Domain\Marketing\GroupBuy\Service;

use App\Domain\Marketing\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use Carbon\Carbon;

/**
 * 拼团订单领域服务.
 *
 * 封装订单级别的拼团业务逻辑，策略类只做薄层编排调用。
 */
final class DomainGroupBuyOrderService
{
    public function __construct(
        private readonly DomainGroupBuyService $groupBuyService,
    ) {}

    /**
     * 校验拼团活动和参团资格，返回 GroupBuyEntity 供后续使用.
     *
     * @param int $groupBuyId 拼团活动 ID
     * @param int $skuId 商品 SKU ID
     * @param int $quantity 购买数量
     * @param int $memberId 会员 ID
     * @param null|string $groupNo 团号（参团时传入，开团时为 null）
     * @return GroupBuyEntity 拼团活动实体
     * @throws \RuntimeException 校验失败时抛出
     */
    public function validateActivity(int $groupBuyId, int $skuId, int $quantity, int $memberId, ?string $groupNo): GroupBuyEntity
    {
        // 1. 加载活动实体
        try {
            $entity = $this->groupBuyService->getEntity($groupBuyId);
        } catch (\RuntimeException) {
            throw new \RuntimeException('拼团活动不存在');
        }

        // 2. canJoin() 检查
        if (! $entity->canJoin()) {
            throw new \RuntimeException('当前拼团活动不可参与');
        }

        // 3. SKU 匹配检查
        if ($entity->getSkuId() !== $skuId) {
            throw new \RuntimeException('该商品不在当前拼团活动中');
        }

        // 4. 库存检查：totalQuantity - soldQuantity >= quantity
        $remainingStock = $entity->getTotalQuantity() - $entity->getSoldQuantity();
        if ($remainingStock < $quantity) {
            throw new \RuntimeException('拼团商品库存不足');
        }

        // 5. 重复参团检查
        if ($this->hasMemberJoined($groupBuyId, $memberId)) {
            throw new \RuntimeException('每人每个活动限参一次');
        }

        // 6. 参团模式：validateJoinGroup()
        if ($groupNo !== null) {
            $this->validateJoinGroup($groupNo, $entity->getMaxPeople());
        }

        // 7. 返回 GroupBuyEntity
        return $entity;
    }

    /**
     * 创建拼团订单记录，更新活动统计.
     *
     * @param OrderEntity $orderEntity 订单实体
     * @param GroupBuyEntity $entity 拼团活动实体
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
            // 开团：生成 group_no（GB + 日期 + 8位随机）、share_code、expire_time
            $record['group_no'] = $this->generateGroupNo();
            $record['share_code'] = $this->generateShareCode();
            $record['is_leader'] = true;
            $record['expire_time'] = $now->copy()->addHours($entity->getGroupTimeLimit());
        } else {
            // 参团：查询团长 order_id 作为 parent_order_id
            $leaderOrder = $this->findLeaderOrder($groupNo);

            $record['group_no'] = $groupNo;
            $record['is_leader'] = false;
            $record['parent_order_id'] = $leaderOrder?->order_id;
            $record['expire_time'] = $leaderOrder?->expire_time;
        }

        // 创建 GroupBuyOrder 记录
        $this->persistGroupBuyOrder($record);

        // 更新活动销量
        $this->groupBuyService->increaseSoldQuantity($entity->getId(), $item->getQuantity());

        // 开团时增加开团数
        if ($isLeader) {
            $this->groupBuyService->increaseGroupCount($entity->getId());
        }
    }

    /**
     * 检查会员是否已参加过该拼团活动（重复参团检查）.
     *
     * @param int $groupBuyId 拼团活动 ID
     * @param int $memberId 会员 ID
     * @return bool 是否已参团
     */
    public function hasMemberJoined(int $groupBuyId, int $memberId): bool
    {
        return GroupBuyOrder::where('group_buy_id', $groupBuyId)
            ->where('member_id', $memberId)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->exists();
    }

    /**
     * 校验参团条件：团存在性、满员、过期检查.
     *
     * @param string $groupNo 团号
     * @param int $maxPeople 最大成团人数
     * @throws \RuntimeException 校验失败时抛出
     */
    public function validateJoinGroup(string $groupNo, int $maxPeople): void
    {
        // 查询该团的所有有效订单
        $groupOrders = GroupBuyOrder::where('group_no', $groupNo)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->get();

        // 团不存在
        if ($groupOrders->isEmpty()) {
            throw new \RuntimeException('无法加入该团（团不存在、已满员或已过期）');
        }

        // 已满员
        if ($groupOrders->count() >= $maxPeople) {
            throw new \RuntimeException('无法加入该团（团不存在、已满员或已过期）');
        }

        // 已过期：检查团长的 expire_time
        $leaderOrder = $groupOrders->firstWhere('is_leader', true);
        if ($leaderOrder && $leaderOrder->expire_time && Carbon::now()->greaterThan($leaderOrder->expire_time)) {
            throw new \RuntimeException('无法加入该团（团不存在、已满员或已过期）');
        }
    }

    /**
     * 持久化拼团订单记录.
     *
     * @param array<string, mixed> $record 订单记录数据
     */
    private function persistGroupBuyOrder(array $record): void
    {
        GroupBuyOrder::create($record);
    }

    /**
     * 查询团长订单.
     *
     * @param string $groupNo 团号
     * @return null|GroupBuyOrder 团长订单记录
     */
    private function findLeaderOrder(string $groupNo): ?GroupBuyOrder
    {
        return GroupBuyOrder::where('group_no', $groupNo)
            ->where('is_leader', true)
            ->first();
    }

    /**
     * 生成团号：GB + 日期(Ymd) + 8位随机数字.
     */
    private function generateGroupNo(): string
    {
        return 'GB' . date('Ymd') . mb_str_pad((string) random_int(0, 99999999), 8, '0', \STR_PAD_LEFT);
    }

    /**
     * 生成分享码：16位随机字母数字.
     */
    private function generateShareCode(): string
    {
        return bin2hex(random_bytes(8));
    }
}
