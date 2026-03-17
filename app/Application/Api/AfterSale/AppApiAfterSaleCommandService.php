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

use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReturnShipmentInput;
use App\Domain\Trade\AfterSale\Mapper\AfterSaleMapper;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;

final class AppApiAfterSaleCommandService
{
    public function __construct(
        private readonly DomainAfterSaleService $afterSaleService,
        private readonly AfterSaleRepository $afterSaleRepository,
    ) {}

    /**
     * 提交售后申请，并返回持久化后的售后模型。
     */
    public function apply(AfterSaleApplyInput $input): object
    {
        $entity = $this->afterSaleService->apply($input);

        return $this->afterSaleRepository->findById($entity->getId());
    }

    /**
     * 撤销当前会员自己的售后单。
     */
    public function cancel(int $memberId, int $id): void
    {
        $model = $this->afterSaleRepository->findByIdAndMember($id, $memberId);
        $entity = AfterSaleMapper::fromModel($model);
        $entity->cancel();
        $this->afterSaleRepository->updateFromEntity($entity);
    }

    /**
     * 会员提交退货物流。
     */
    public function submitReturnShipment(AfterSaleReturnShipmentInput $input): void
    {
        $model = $this->afterSaleRepository->findByIdAndMember($input->getId(), $input->getMemberId());
        $entity = AfterSaleMapper::fromModel($model);
        $entity->submitBuyerReturn($input->getLogisticsCompany(), $input->getLogisticsNo());
        $this->afterSaleRepository->updateFromEntity($entity);
    }

    /**
     * 提交售后换货收货确认。
     */
    public function confirmExchangeReceived(int $memberId, int $id): void
    {
        $model = $this->afterSaleRepository->findByIdAndMember($id, $memberId);
        $entity = AfterSaleMapper::fromModel($model);
        $entity->confirmExchangeReceived();
        $this->afterSaleRepository->updateFromEntity($entity);
    }
}
