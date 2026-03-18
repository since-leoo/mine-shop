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

namespace App\Domain\Trade\AfterSale\Service;

use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;

final class DomainAfterSaleQueryService
{
    public function __construct(
        private readonly AfterSaleRepository $afterSaleRepository,
        private readonly OrderPaymentRefundRepository $refundRepository,
    ) {}

    /**
     * åé¡µæ¥è¯¢åå°å®åååå§æ°æ®ã
     *
     * @param array<string, mixed> $filters
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function pageForAdmin(array $filters, int $page, int $pageSize): array
    {
        return $this->afterSaleRepository->pageForAdmin($filters, $page, $pageSize);
    }

    /**
     * æ¥è¯¢åå°å®åè¯¦æåå§æ°æ®ã
     *
     * @return array{after_sale: object, refund_record: object|null}|null
     */
    public function detailForAdmin(int $id): ?array
    {
        $afterSale = $this->afterSaleRepository->findDetailById($id);
        if ($afterSale === null) {
            return null;
        }

        return [
            'after_sale' => $afterSale,
            'refund_record' => $this->refundRepository->findLatestByAfterSaleId((int) $afterSale->id),
        ];
    }
}
