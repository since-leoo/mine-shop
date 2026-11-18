<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Application\Api\Seckill\AppApiSeckillProductQueryService;
use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Contract\FreightServiceInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Strategy\NormalOrderStrategy;
use App\Domain\Trade\Seckill\Contract\SeckillPromotionQueryInput;
use App\Infrastructure\Error\AfterSaleError;
use App\Infrastructure\Error\CouponError;
use App\Infrastructure\Error\SeckillError;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Api\Controller\V1\Seckill\SeckillProductController;
use App\Interface\Api\Request\V1\Seckill\SeckillPromotionRequest;
use App\Interface\Api\Transformer\Seckill\SeckillProductTransformer;
use App\Interface\Common\ResultCode;
use PHPUnit\Framework\TestCase;

final class ErrorCatalogTest extends TestCase
{
    public function testCouponThresholdPreservesAllDynamicArgumentsAndAddsBusinessCode(): void
    {
        $coupons = $this->createMock(CouponServiceInterface::class);
        $coupons->method('findUsableCoupon')->willReturn(['name' => '满减券', 'status' => 'active', 'min_amount' => 100]);
        $strategy = new NormalOrderStrategy($this->createMock(ProductSnapshotInterface::class), $coupons, $this->createMock(FreightServiceInterface::class));
        try {
            $strategy->applyCoupon(new OrderEntity(), 7);
            self::fail('Order below coupon threshold must be rejected.');
        } catch (\DomainException $error) {
            self::assertSame(CouponError::MINIMUM_AMOUNT_NOT_MET->value, $error->getCode());
            self::assertSame('优惠券 满减券 需满 100 分可用，当前商品金额 0 分', $error->getMessage());
        }
    }

    public function testAfterSaleStatusGuardIdentifiesTheRejectedOperation(): void
    {
        $afterSale = new AfterSaleEntity();
        $afterSale->approve();
        try {
            $afterSale->approve();
            self::fail('Repeated approval must be rejected.');
        } catch (\DomainException $error) {
            self::assertSame(AfterSaleError::APPROVAL_STATUS_INVALID->value, $error->getCode());
            self::assertSame('只有待审核的售后单才能审核通过', $error->getMessage());
        }
    }

    public function testSeckillControllerKeepsValidationResultAndUnderlyingBusinessCode(): void
    {
        $query = $this->createMock(AppApiSeckillProductQueryService::class);
        $query->method('getPromotionList')->willThrowException(SeckillError::SESSION_ACTIVITY_MISMATCH->exception());
        $request = $this->createMock(SeckillPromotionRequest::class);
        $request->method('toDto')->willReturn($this->createMock(SeckillPromotionQueryInput::class));
        $controller = new SeckillProductController($query, $this->createMock(SeckillProductTransformer::class));
        try {
            $controller->index($request);
            self::fail('Mismatched session must retain the validation response.');
        } catch (BusinessException $error) {
            self::assertSame(ResultCode::UNPROCESSABLE_ENTITY, $error->getResponse()->code);
            self::assertSame(SeckillError::SESSION_ACTIVITY_MISMATCH->value, $error->getResponse()->errorCode);
            self::assertSame('秒杀场次不属于当前活动', $error->getResponse()->message);
        }
    }
}
