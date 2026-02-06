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

namespace App\Application\Mapper;

use App\Domain\Order\Entity\OrderCancelEntity;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Entity\OrderShipEntity;

final class OrderAssembler
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $operator
     */
    public static function toShipEntity(int $orderId, array $payload, array $operator): OrderShipEntity
    {
        $entity = new OrderShipEntity();
        $entity->setOrderId($orderId);
        $entity->setOperatorId((int) ($operator['id'] ?? 0));
        $entity->setOperatorName((string) ($operator['name'] ?? '管理员'));
        $entity->setPackages([
            [
                'shipping_company' => (string) ($payload['shipping_company'] ?? ''),
                'shipping_no' => (string) ($payload['shipping_no'] ?? ''),
                'remark' => (string) ($payload['remark'] ?? ''),
                'quantity' => (int) ($payload['quantity'] ?? 0),
                'weight' => (float) ($payload['weight'] ?? 0),
            ],
        ]);

        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $operator
     */
    public static function toCancelEntity(int $orderId, array $payload, array $operator): OrderCancelEntity
    {
        $entity = new OrderCancelEntity();
        $entity->setOrderId($orderId);
        $entity->setReason((string) ($payload['reason'] ?? ''));
        $entity->setOperatorId((int) ($operator['id'] ?? 0));
        $entity->setOperatorName((string) ($operator['name'] ?? '管理员'));
        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toSubmitCommand(array $payload): OrderEntity
    {
        $command = new OrderEntity();
        $command->setMemberId((int) ($payload['member_id'] ?? 0));
        $command->setOrderType((string) ($payload['order_type'] ?? 'normal'));
        $command->replaceItemsFromPayload(\is_array($payload['items'] ?? null) ? $payload['items'] : []);
        if (\is_array($payload['address'] ?? null)) {
            $command->useAddressPayload($payload['address']);
        }
        $command->setBuyerRemark((string) ($payload['remark'] ?? ''));
        return $command;
    }
}
