<?php

declare(strict_types=1);

namespace App\Application\Api\Logistics;

use App\Application\Api\AfterSale\AppApiAfterSaleQueryService;
use App\Application\Api\Order\AppApiOrderQueryService;
use Plugin\Express\Contract\LogisticsTrackingInterface;

final class AppApiLogisticsTrackingQueryService
{
    public function __construct(
        private readonly AppApiOrderQueryService $orderQueryService,
        private readonly AppApiAfterSaleQueryService $afterSaleQueryService,
        private readonly LogisticsTrackingInterface $trackingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function trackOrder(int $memberId, string $orderNo): array
    {
        $order = $this->orderQueryService->getOrderDetail($memberId, $orderNo);
        if (! is_object($order)) {
            throw new \RuntimeException('订单不存在');
        }

        $packages = $order->packages ?? [];
        $package = is_array($packages) ? ($packages[0] ?? null) : null;
        $company = is_object($package) ? trim((string) ($package->express_company ?? '')) : '';
        $trackingNo = is_object($package) ? trim((string) ($package->express_no ?? '')) : '';

        if ($company === '' || $trackingNo === '') {
            throw new \RuntimeException('订单物流信息不存在');
        }

        return $this->trackingService->track($company, $trackingNo)->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function trackAfterSaleReturn(int $memberId, int $afterSaleId): array
    {
        $afterSale = $this->afterSaleQueryService->detail($memberId, $afterSaleId);
        $company = trim((string) ($afterSale->buyer_return_logistics_company ?? ''));
        $trackingNo = trim((string) ($afterSale->buyer_return_logistics_no ?? ''));

        if ($company === '' || $trackingNo === '') {
            throw new \RuntimeException('售后退货物流信息不存在');
        }

        return $this->trackingService->track($company, $trackingNo)->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function trackAfterSaleReship(int $memberId, int $afterSaleId): array
    {
        $afterSale = $this->afterSaleQueryService->detail($memberId, $afterSaleId);
        $company = trim((string) ($afterSale->reship_logistics_company ?? ''));
        $trackingNo = trim((string) ($afterSale->reship_logistics_no ?? ''));

        if ($company === '' || $trackingNo === '') {
            throw new \RuntimeException('售后补发物流信息不存在');
        }

        return $this->trackingService->track($company, $trackingNo)->toArray();
    }
}
