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

namespace App\Application\Api\Cart;

use App\Domain\Member\Api\Command\DomainApiMemberCartCommandService;
use App\Domain\Member\Contract\CartItemInput;

final class AppApiCartCommandService
{
    public function __construct(
        private readonly DomainApiMemberCartCommandService $cartCommandService,
        private readonly AppApiCartQueryService $queryService
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function addItem(int $memberId, CartItemInput $input): array
    {
        $this->cartCommandService->addItem(
            $memberId,
            $input->getSkuId(),
            $input->getQuantity() ?? 1
        );

        return $this->queryService->listDetailed($memberId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function updateItem(int $memberId, int $skuId, CartItemInput $input): array
    {
        $payload = [];
        if ($input->getQuantity() !== null) {
            $payload['quantity'] = $input->getQuantity();
        }

        $this->cartCommandService->updateItem($memberId, $skuId, $payload);

        return $this->queryService->listDetailed($memberId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function deleteItem(int $memberId, int $skuId): array
    {
        $this->cartCommandService->removeItem($memberId, $skuId);

        return $this->queryService->listDetailed($memberId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function clearInvalid(int $memberId): array
    {
        $this->cartCommandService->clearInvalid($memberId);

        return $this->queryService->listDetailed($memberId);
    }
}
