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

namespace HyperfTests\Unit\Domain\Order;

use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Interface\Api\DTO\Order\OrderPreviewDto;
use PHPUnit\Framework\TestCase;

/**
 * Feature: group-buy-order, Property 15: initFromInput 存储 extras.
 *
 * For any OrderPreviewInput 包含 group_buy_id 和 group_no，调用 initFromInput() 后，
 * getExtra('group_buy_id') 应等于输入的 group_buy_id，
 * getExtra('group_no') 应等于输入的 group_no。
 *
 * **Validates: Requirements 3.8**
 *
 * @internal
 * @coversNothing
 */
final class OrderEntityExtrasTest extends TestCase
{
    /**
     * Property 15: initFromInput 存储 extras — both group_buy_id and group_no are stored.
     *
     * For any valid group_buy_id (positive int) and group_no (non-empty string),
     * after calling initFromInput(), getExtra('group_buy_id') should equal the input group_buy_id,
     * and getExtra('group_no') should equal the input group_no.
     *
     * **Validates: Requirements 3.8**
     *
     * @dataProvider provideInitFromInputStoresGroupBuyExtrasCases
     */
    public function testInitFromInputStoresGroupBuyExtras(int $groupBuyId, string $groupNo): void
    {
        $dto = new OrderPreviewDto();
        $dto->member_id = random_int(1, 100000);
        $dto->order_type = 'group_buy';
        $dto->goods_request_list = [['sku_id' => random_int(1, 9999), 'quantity' => 1]];
        $dto->group_buy_id = $groupBuyId;
        $dto->group_no = $groupNo;

        $entity = new OrderEntity();
        $entity->initFromInput($dto);

        self::assertSame($groupBuyId, $entity->getExtra('group_buy_id'));
        self::assertSame($groupNo, $entity->getExtra('group_no'));
    }

    /**
     * Generates at least 100 random group_buy_id + group_no pairs.
     *
     * @return iterable<string, array{int, string}>
     */
    public static function provideInitFromInputStoresGroupBuyExtrasCases(): iterable
    {
        for ($i = 0; $i < 100; ++$i) {
            $groupBuyId = random_int(1, 999_999);
            $groupNo = 'GB' . date('Ymd') . mb_str_pad((string) random_int(0, 99999999), 8, '0', \STR_PAD_LEFT);

            yield "iteration_{$i} (id={$groupBuyId}, no={$groupNo})" => [
                $groupBuyId,
                $groupNo,
            ];
        }
    }

    /**
     * Property 15b: initFromInput stores group_buy_id when group_no is null (开团场景).
     *
     * For any valid group_buy_id, when group_no is null (leader creating a new group),
     * getExtra('group_buy_id') should equal the input, and getExtra('group_no') should be null (default).
     *
     * **Validates: Requirements 3.8**
     *
     * @dataProvider provideInitFromInputStoresGroupBuyIdWhenGroupNoIsNullCases
     */
    public function testInitFromInputStoresGroupBuyIdWhenGroupNoIsNull(int $groupBuyId): void
    {
        $dto = new OrderPreviewDto();
        $dto->member_id = random_int(1, 100000);
        $dto->order_type = 'group_buy';
        $dto->goods_request_list = [['sku_id' => random_int(1, 9999), 'quantity' => 1]];
        $dto->group_buy_id = $groupBuyId;
        $dto->group_no = null;

        $entity = new OrderEntity();
        $entity->initFromInput($dto);

        self::assertSame($groupBuyId, $entity->getExtra('group_buy_id'));
        self::assertNull($entity->getExtra('group_no'));
    }

    /**
     * Generates at least 100 random group_buy_id values for leader (开团) scenario.
     *
     * @return iterable<string, array{int}>
     */
    public static function provideInitFromInputStoresGroupBuyIdWhenGroupNoIsNullCases(): iterable
    {
        for ($i = 0; $i < 100; ++$i) {
            $groupBuyId = random_int(1, 999_999);

            yield "iteration_{$i} (id={$groupBuyId})" => [
                $groupBuyId,
            ];
        }
    }

    /**
     * Property 15c: initFromInput does NOT store group_buy extras when both are null (非拼团场景).
     *
     * For any non-group-buy order, when group_buy_id and group_no are both null,
     * getExtra('group_buy_id') and getExtra('group_no') should return their defaults (null).
     *
     * **Validates: Requirements 3.8**
     */
    public function testInitFromInputDoesNotStoreExtrasWhenBothNull(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $dto = new OrderPreviewDto();
            $dto->member_id = random_int(1, 100000);
            $dto->order_type = 'normal';
            $dto->goods_request_list = [['sku_id' => random_int(1, 9999), 'quantity' => 1]];
            $dto->group_buy_id = null;
            $dto->group_no = null;

            $entity = new OrderEntity();
            $entity->initFromInput($dto);

            self::assertNull($entity->getExtra('group_buy_id'));
            self::assertNull($entity->getExtra('group_no'));
        }
    }
}
