<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Order\Entity;

use App\Domain\Trade\Order\Contract\OrderPreviewInput;
use App\Domain\Trade\Order\Entity\OrderEntity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderEntityFromCartTest extends TestCase
{
    public function testInitFromInputStoresFromCartFlagInExtras(): void
    {
        $input = new class implements OrderPreviewInput {
            public function getMemberId(): int
            {
                return 1001;
            }

            public function getOrderType(): string
            {
                return 'normal';
            }

            public function getGoodsRequestList(): array
            {
                return [
                    ['sku_id' => 11, 'quantity' => 1],
                ];
            }

            public function getAddressId(): ?int
            {
                return null;
            }

            public function getUserAddress(): ?array
            {
                return null;
            }

            public function getCouponId(): ?int
            {
                return null;
            }

            public function getBuyerRemark(): string
            {
                return '';
            }

            public function getActivityId(): ?int
            {
                return null;
            }

            public function getSessionId(): ?int
            {
                return null;
            }

            public function getGroupBuyId(): ?int
            {
                return null;
            }

            public function getGroupNo(): ?string
            {
                return null;
            }

            public function getBuyOriginalPrice(): bool
            {
                return false;
            }

            public function getFromCart(): bool
            {
                return true;
            }
        };

        $entity = new OrderEntity();
        $entity->initFromInput($input);

        self::assertTrue($entity->getExtra('from_cart', false));
    }
}
