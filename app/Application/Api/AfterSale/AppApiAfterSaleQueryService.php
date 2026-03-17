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

namespace App\Application\Api\AfterSale;

use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;
use Hyperf\Contract\LengthAwarePaginatorInterface;

final class AppApiAfterSaleQueryService
{
    public function __construct(
        private readonly DomainAfterSaleService $afterSaleService,
        private readonly AfterSaleRepository $afterSaleRepository,
    ) {}

    /**
     * 查询订单商品项的售后资格。
     */
    public function eligibility(int $memberId, int $orderId, int $orderItemId): array
    {
        return $this->afterSaleService->eligibility($memberId, $orderId, $orderItemId);
    }

    /**
     * 分页查询当前会员的售后单列表。
     */
    public function paginateByMember(int $memberId, string $status = 'all', int $page = 1, int $pageSize = 10): LengthAwarePaginatorInterface
    {
        return $this->afterSaleRepository->paginateByMember($memberId, $status, $page, $pageSize);
    }

    /**
     * 查询当前会员的售后单详情。
     */
    public function detail(int $memberId, int $id): object
    {
        return $this->afterSaleRepository->findByIdAndMember($id, $memberId);
    }
}