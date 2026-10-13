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

namespace App\Application\Admin\Trade;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\AfterSale\Contract\AfterSaleActionInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReshipInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReviewInput;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;
use Hyperf\DbConnection\Annotation\Transactional;

final class AppAfterSaleCommandService
{
    public function __construct(
        private readonly DomainAfterSaleService $afterSaleService,
        private readonly AppAfterSaleQueryService $queryService,
        private readonly AppAfterSaleRefundService $refundService,
        private readonly DomainAfterSaleRefundService $refundPreparation,
        private readonly \App\Domain\Trade\Shared\Contract\TransactionManagerInterface $transactions,
        private readonly DomainMallSettingService $mallSettingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function approve(AfterSaleReviewInput $input): array
    {
        $refund = $this->transactions->run(fn () => $this->refundPreparation->approve(
            $input, ! $this->mallSettingService->payment()->refundReview()
        ));
        if ($refund !== null) {
            $this->refundService->process($refund->refund_no);
        }
        return $this->detail($input->getId());
    }

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function reject(AfterSaleReviewInput $input): array
    {
        $entity = $this->afterSaleService->getEntity($input->getId());
        $entity->setRejectReason(trim($input->getRejectReason()));
        $entity->reject();
        $this->afterSaleService->saveEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function receive(AfterSaleActionInput $input): array
    {
        $entity = $this->afterSaleService->getEntity($input->getId());
        $entity->markSellerReceived();
        $this->afterSaleService->saveEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * @return array<string, mixed>
     */
    public function refund(AfterSaleActionInput $input): array
    {
        $this->refundService->refund($input->getId(), $input->getOperatorId(), $input->getOperatorName());
        return $this->detail($input->getId());
    }

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function reship(AfterSaleReshipInput $input): array
    {
        $entity = $this->afterSaleService->getEntity($input->getId());
        $entity->markReshipped($input->getLogisticsCompany(), $input->getLogisticsNo());
        $this->afterSaleService->saveEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function completeExchange(AfterSaleActionInput $input): array
    {
        $entity = $this->afterSaleService->getEntity($input->getId());
        $entity->confirmExchangeReceived();
        $this->afterSaleService->saveEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(int $id): array
    {
        return $this->queryService->detail($id) ?? [];
    }

}
