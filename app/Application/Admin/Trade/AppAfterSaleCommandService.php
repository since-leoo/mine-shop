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

use App\Domain\Trade\AfterSale\Contract\AfterSaleActionInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReshipInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReviewInput;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;
use Hyperf\DbConnection\Annotation\Transactional;

final class AppAfterSaleCommandService
{
    public function __construct(
        private readonly DomainAfterSaleService $afterSaleService,
        private readonly AppAfterSaleQueryService $queryService,
        private readonly DomainAfterSaleRefundService $refundService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[Transactional]
    public function approve(AfterSaleReviewInput $input): array
    {
        $entity = $this->afterSaleService->getEntity($input->getId());
        if ($input->getApprovedRefundAmount() !== null) {
            $entity->setRefundAmount($input->getApprovedRefundAmount());
        }

        $entity->approve();
        $this->afterSaleService->saveEntity($entity);

        return $this->detail($entity->getId());
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
    #[Transactional]
    public function refund(AfterSaleActionInput $input): array
    {
        $entity = $this->afterSaleService->getEntity($input->getId());
        $entity->markRefunding();
        $this->afterSaleService->saveEntity($entity);

        $this->refundService->refund($entity, $input->getOperatorId(), $input->getOperatorName());

        return $this->detail($entity->getId());
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
