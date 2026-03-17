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
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Mapper\AfterSaleMapper;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use Hyperf\DbConnection\Annotation\Transactional;
use RuntimeException;

final class AppAfterSaleCommandService
{
    public function __construct(
        private readonly AfterSaleRepository $afterSaleRepository,
        private readonly AppAfterSaleQueryService $queryService,
    ) {}

    /**
     * ?????????
     *
     * @return array<string, mixed>
     */
    #[Transactional]
    public function approve(AfterSaleReviewInput $input): array
    {
        $entity = $this->getEntity($input->getId());
        if ($input->getApprovedRefundAmount() !== null) {
            $entity->setRefundAmount($input->getApprovedRefundAmount());
        }

        $entity->approve();
        $this->afterSaleRepository->updateFromEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * ?????????
     *
     * @return array<string, mixed>
     */
    #[Transactional]
    public function reject(AfterSaleReviewInput $input): array
    {
        $entity = $this->getEntity($input->getId());
        $entity->reject();
        $this->afterSaleRepository->updateFromEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * ???????????
     *
     * @return array<string, mixed>
     */
    #[Transactional]
    public function receive(AfterSaleActionInput $input): array
    {
        $entity = $this->getEntity($input->getId());
        $entity->markSellerReceived();
        $this->afterSaleRepository->updateFromEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * ???????
     *
     * @return array<string, mixed>
     */
    #[Transactional]
    public function refund(AfterSaleActionInput $input): array
    {
        $entity = $this->getEntity($input->getId());
        $entity->markRefunding();
        $entity->markRefunded();
        $this->afterSaleRepository->updateFromEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * ????????????
     *
     * @return array<string, mixed>
     */
    #[Transactional]
    public function reship(AfterSaleReshipInput $input): array
    {
        $entity = $this->getEntity($input->getId());
        $entity->markReshipped($input->getLogisticsCompany(), $input->getLogisticsNo());
        $this->afterSaleRepository->updateFromEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * ?????????????
     *
     * @return array<string, mixed>
     */
    #[Transactional]
    public function completeExchange(AfterSaleActionInput $input): array
    {
        $entity = $this->getEntity($input->getId());
        $entity->confirmExchangeReceived();
        $this->afterSaleRepository->updateFromEntity($entity);

        return $this->detail($entity->getId());
    }

    private function getEntity(int $id): AfterSaleEntity
    {
        $model = $this->afterSaleRepository->findById($id);
        if ($model === null) {
            throw new RuntimeException('??????');
        }

        return AfterSaleMapper::fromModel($model);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(int $id): array
    {
        return $this->queryService->detail($id) ?? [];
    }
}
