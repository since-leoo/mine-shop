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
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundService;
use App\Infrastructure\Model\AfterSale\AfterSale;
use Hyperf\DbConnection\Annotation\Transactional;
use RuntimeException;

final class AppAfterSaleCommandService
{
    public function __construct(
        private readonly AfterSaleRepository $afterSaleRepository,
        private readonly AppAfterSaleQueryService $queryService,
        private readonly DomainAfterSaleRefundService $refundService,
    ) {}

    /**
     * 审核通过售后申请.
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
     * 审核拒绝售后申请.
     *
     * @return array<string, mixed>
     */
    #[Transactional]
    public function reject(AfterSaleReviewInput $input): array
    {
        $entity = $this->getEntity($input->getId());
        $entity->setRejectReason(trim($input->getRejectReason()));
        $entity->reject();
        $this->afterSaleRepository->updateFromEntity($entity);

        return $this->detail($entity->getId());
    }

    /**
     * 确认收到买家尨回商品.
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
     * 发起售后退款.
     *
     * @return array<string, mixed>
     */
    #[Transactional]
    public function refund(AfterSaleActionInput $input): array
    {
        $entity = $this->getEntity($input->getId());
        $entity->markRefunding();
        $this->afterSaleRepository->updateFromEntity($entity);

        $this->refundService->refund($entity, $input->getOperatorId(), $input->getOperatorName());

        return $this->detail($entity->getId());
    }

    /**
     * 执行补发处理.
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
     * 确认换货完成.
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

    /**
     * 获取售后实体.
     */
    private function getEntity(int $id): AfterSaleEntity
    {
        /** @var AfterSale $model */
        $model = $this->afterSaleRepository->findById($id);
        if ($model === null) {
            throw new RuntimeException('售后单不存在');
        }

        return AfterSaleMapper::fromModel($model);
    }

    /**
     * 获取售后详情结果.
     *
     * @return array<string, mixed>
     */
    private function detail(int $id): array
    {
        return $this->queryService->detail($id) ?? [];
    }
}
