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

namespace App\Application\Api\Logistics;

use App\Application\Api\AfterSale\AppApiAfterSaleQueryService;

use App\Application\Api\Order\AppApiOrderQueryService;

use App\Infrastructure\Error\CommerceError;
use App\Infrastructure\Error\LogisticsError;
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
        if (! \is_object($order)) {
            throw CommerceError::ORDER_NOT_FOUND->exception();
        }

        $packages = $order->packages ?? [];
        $package = \is_array($packages) ? ($packages[0] ?? null) : null;
        $company = \is_object($package) ? trim((string) ($package->express_company ?? '')) : '';
        $trackingNo = \is_object($package) ? trim((string) ($package->express_no ?? '')) : '';

        if ($company === '' || $trackingNo === '') {
            throw LogisticsError::ORDER_TRACKING_NOT_FOUND->exception();
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
            throw LogisticsError::RETURN_TRACKING_NOT_FOUND->exception();
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
            throw LogisticsError::RESHIP_TRACKING_NOT_FOUND->exception();
        }

        return $this->trackingService->track($company, $trackingNo)->toArray();
    }
}
