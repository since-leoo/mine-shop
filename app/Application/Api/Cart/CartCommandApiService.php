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

use App\Domain\Member\Contract\CartItemInput;
use App\Domain\Member\Service\MemberCartService;

final class CartCommandApiService
{
    public function __construct(
        private readonly MemberCartService $cartService,
        private readonly CartQueryApiService $queryService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function addItem(int $memberId, CartItemInput $input): array
    {
        $this->cartService->addItem(
            $memberId,
            $input->getSkuId(),
            $input->getQuantity(),
            $input->getIsSelected() ?? true
        );

        return $this->queryService->overview($memberId);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateItem(int $memberId, int $skuId, CartItemInput $input): array
    {
        $this->cartService->updateItem($memberId, $skuId, [
            'quantity' => $input->getQuantity(),
            'is_selected' => $input->getIsSelected(),
        ]);

        return $this->queryService->overview($memberId);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteItem(int $memberId, int $skuId): array
    {
        $this->cartService->removeItem($memberId, $skuId);

        return $this->queryService->overview($memberId);
    }

    /**
     * @return array<string, mixed>
     */
    public function clearInvalid(int $memberId): array
    {
        $this->cartService->clearInvalid($memberId);

        return $this->queryService->overview($memberId);
    }
}
