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

final class CartQueryApiService
{
    public function __construct(
        private readonly MemberCartService $cartService,
        private readonly CartTransformer $transformer
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(int $memberId): array
    {
        $items = $this->cartService->listDetailed($memberId);
        return $this->transformer->transform($items, $memberId);
    }
}
