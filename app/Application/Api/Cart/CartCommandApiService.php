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

use App\Domain\Member\Service\MemberCartService;

final class CartCommandApiService
{
    public function __construct(
        private readonly MemberCartService $cartService,
        private readonly CartQueryApiService $queryService
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function addItem(int $memberId, array $payload): array
    {
        $skuId = (int) ($payload['sku_id'] ?? 0);
        $quantity = (int) ($payload['quantity'] ?? 1);
        $isSelected = (bool) $payload['is_selected'] ?? false;

        $this->cartService->addItem($memberId, $skuId, $quantity, $isSelected);

        return $this->queryService->overview($memberId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateItem(int $memberId, int $skuId, array $payload): array
    {
        $this->cartService->updateItem($memberId, $skuId, $payload);

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
