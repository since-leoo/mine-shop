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
        private readonly \App\Domain\Trade\Shared\Contract\TransactionManagerInterface $transactions,
        private readonly \App\Domain\Trade\Shared\Contract\TradeOutboxInterface $outbox,
    ) {}

    /**
     * 提交售后申请，并返回持久化后的售后模型。
     */
    public function apply(AfterSaleApplyInput $input): object
    {
        return $this->transactions->run(function () use ($input): object {
            $this->afterSaleService->lockOrder($input->getOrderId());
            $entity = $this->afterSaleService->apply($input);
            $this->recordChange($entity->getId(), 'apply');
            return $this->afterSaleRepository->findById($entity->getId());
        });
    }

    /**
     * 撤销当前会员自己的售后单。
     */
    public function cancel(int $memberId, int $id): void
    {
        $this->transactions->run(function () use ($id, $memberId): void {
            $this->afterSaleService->cancelForMember($memberId, $id);
            $this->recordChange($id, 'cancel');
        });
    }

    /**
     * 会员提交退货物流。
     */
    public function submitReturnShipment(AfterSaleReturnShipmentInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $this->afterSaleService->submitMemberReturn($input);
            $this->recordChange($input->getId(), 'return_shipment');
        });
    }

    private function recordChange(int $id, string $action): void
    {
        $this->outbox->append('after-sale:' . $id . ':' . $action, 'after_sale.changed', [
            'after_sale_id' => $id, 'action' => $action,
        ]);
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
