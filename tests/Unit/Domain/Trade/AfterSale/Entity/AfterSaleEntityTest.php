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

namespace HyperfTests\Unit\Domain\Trade\AfterSale\Entity;

use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Enum\AfterSaleRefundStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleReturnStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleType;
use DomainException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AfterSaleEntityTest extends TestCase
{
    public function testApplyCreatesRefundOnlyAfterSale(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::REFUND_ONLY));

        self::assertSame(1001, $entity->getOrderId());
        self::assertSame(2002, $entity->getOrderItemId());
        self::assertSame(3003, $entity->getMemberId());
        self::assertSame(AfterSaleType::REFUND_ONLY->value, $entity->getType());
        self::assertSame(AfterSaleStatus::PENDING_REVIEW->value, $entity->getStatus());
        self::assertSame(AfterSaleRefundStatus::PENDING->value, $entity->getRefundStatus());
        self::assertSame(AfterSaleReturnStatus::NOT_REQUIRED->value, $entity->getReturnStatus());
        self::assertSame(18800, $entity->getApplyAmount());
        self::assertSame(18800, $entity->getRefundAmount());
        self::assertSame('Need a refund', $entity->getReason());
        self::assertSame(['https://img.example/1.png'], $entity->getImages());
    }

    public function testApproveRefundOnlyMovesToWaitingRefund(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::REFUND_ONLY));

        $entity->approve();

        self::assertSame(AfterSaleStatus::WAITING_REFUND->value, $entity->getStatus());
        self::assertSame(AfterSaleRefundStatus::PENDING->value, $entity->getRefundStatus());
        self::assertSame(AfterSaleReturnStatus::NOT_REQUIRED->value, $entity->getReturnStatus());
    }

    public function testApproveReturnRefundMovesToWaitingBuyerReturn(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::RETURN_REFUND));

        $entity->approve();

        self::assertSame(AfterSaleStatus::WAITING_BUYER_RETURN->value, $entity->getStatus());
        self::assertSame(AfterSaleReturnStatus::PENDING->value, $entity->getReturnStatus());
    }

    public function testApproveExchangeMovesToWaitingBuyerReturn(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::EXCHANGE));

        $entity->approve();

        self::assertSame(AfterSaleStatus::WAITING_BUYER_RETURN->value, $entity->getStatus());
        self::assertSame(AfterSaleReturnStatus::PENDING->value, $entity->getReturnStatus());
    }

    public function testRejectClosesAfterSale(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput());

        $entity->reject();

        self::assertSame(AfterSaleStatus::CLOSED->value, $entity->getStatus());
    }

    public function testCancelOnlyAllowedWhilePendingReview(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput());

        $entity->cancel();

        self::assertSame(AfterSaleStatus::CLOSED->value, $entity->getStatus());
    }

    public function testCancelAfterApproveThrows(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput());
        $entity->approve();

        $this->expectException(DomainException::class);
        $entity->cancel();
    }

    public function testSubmitBuyerReturnMovesToWaitingSellerReceive(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::RETURN_REFUND));
        $entity->approve();

        $entity->submitBuyerReturn('SF', 'SF123456');

        self::assertSame(AfterSaleStatus::WAITING_SELLER_RECEIVE->value, $entity->getStatus());
        self::assertSame(AfterSaleReturnStatus::BUYER_SHIPPED->value, $entity->getReturnStatus());
        self::assertSame('SF', $entity->getReturnLogisticsCompany());
        self::assertSame('SF123456', $entity->getReturnLogisticsNo());
    }

    public function testMarkRefundingAndRefundedCompleteRefundFlow(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::REFUND_ONLY));
        $entity->approve();

        $entity->markRefunding();
        self::assertSame(AfterSaleStatus::REFUNDING->value, $entity->getStatus());
        self::assertSame(AfterSaleRefundStatus::PROCESSING->value, $entity->getRefundStatus());

        $entity->markRefunded();
        self::assertSame(AfterSaleStatus::COMPLETED->value, $entity->getStatus());
        self::assertSame(AfterSaleRefundStatus::REFUNDED->value, $entity->getRefundStatus());
    }

    public function testSellerReceivedExchangeMovesToWaitingReship(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::EXCHANGE));
        $entity->approve();
        $entity->submitBuyerReturn('SF', 'SF123456');

        $entity->markSellerReceived();

        self::assertSame(AfterSaleStatus::WAITING_RESHIP->value, $entity->getStatus());
        self::assertSame(AfterSaleReturnStatus::SELLER_RECEIVED->value, $entity->getReturnStatus());
    }

    public function testSellerReceivedReturnRefundMovesToWaitingRefund(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::RETURN_REFUND));
        $entity->approve();
        $entity->submitBuyerReturn('SF', 'SF123456');

        $entity->markSellerReceived();

        self::assertSame(AfterSaleStatus::WAITING_REFUND->value, $entity->getStatus());
        self::assertSame(AfterSaleReturnStatus::SELLER_RECEIVED->value, $entity->getReturnStatus());
    }

    public function testMarkReshippedMovesExchangeToReshipped(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::EXCHANGE));
        $entity->approve();
        $entity->submitBuyerReturn('SF', 'SF123456');
        $entity->markSellerReceived();

        $entity->markReshipped('YTO', 'YT0001');

        self::assertSame(AfterSaleStatus::RESHIPPED->value, $entity->getStatus());
        self::assertSame(AfterSaleReturnStatus::SELLER_RESHIPPED->value, $entity->getReturnStatus());
        self::assertSame('YTO', $entity->getReshipLogisticsCompany());
        self::assertSame('YT0001', $entity->getReshipLogisticsNo());
    }

    public function testConfirmExchangeReceivedCompletesExchange(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::EXCHANGE));
        $entity->approve();
        $entity->submitBuyerReturn('SF', 'SF123456');
        $entity->markSellerReceived();
        $entity->markReshipped('YTO', 'YT0001');

        $entity->confirmExchangeReceived();

        self::assertSame(AfterSaleStatus::COMPLETED->value, $entity->getStatus());
        self::assertSame(AfterSaleReturnStatus::BUYER_RECEIVED->value, $entity->getReturnStatus());
    }

    public function testInvalidTransitionThrowsDomainException(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::REFUND_ONLY));

        $this->expectException(DomainException::class);
        $entity->submitBuyerReturn('SF', 'SF123456');
    }

    public function testToArrayContainsPersistableFields(): void
    {
        $entity = AfterSaleEntity::apply($this->makeInput(type: AfterSaleType::EXCHANGE));
        $entity->setAfterSaleNo('AS202603160001');

        $payload = $entity->toArray();

        self::assertSame('AS202603160001', $payload['after_sale_no']);
        self::assertSame('exchange', $payload['type']);
        self::assertSame('pending_review', $payload['status']);
        self::assertSame('pending', $payload['refund_status']);
        self::assertSame('not_required', $payload['return_status']);
        self::assertSame(['https://img.example/1.png'], $payload['images']);
    }

    private function makeInput(AfterSaleType $type = AfterSaleType::REFUND_ONLY): AfterSaleApplyInput
    {
        return new class($type) implements AfterSaleApplyInput {
            public function __construct(private readonly AfterSaleType $type)
            {
            }

            public function getOrderId(): int
            {
                return 1001;
            }

            public function getOrderItemId(): int
            {
                return 2002;
            }

            public function getMemberId(): int
            {
                return 3003;
            }

            public function getType(): string
            {
                return $this->type->value;
            }

            public function getReason(): string
            {
                return 'Need a refund';
            }

            public function getDescription(): ?string
            {
                return 'Package arrived damaged';
            }

            public function getApplyAmount(): int
            {
                return 18800;
            }

            public function getQuantity(): int
            {
                return 1;
            }

            public function getImages(): ?array
            {
                return ['https://img.example/1.png'];
            }
        };
    }
}
