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

namespace App\Application\Commad\Plugin;

use App\Application\Admin\Trade\AppAfterSaleCommandService;
use App\Domain\Infrastructure\Plugin\Contract\CommerceAfterSaleInterface;
use App\Domain\Infrastructure\Plugin\Dto\CommerceAfterSaleActionData;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceAfterSaleService;

/** 复用核心审核、状态机、退款幂等号及原支付通道，插件没有直接改支付表的权限。 */
final class AppCommerceAfterSaleService implements CommerceAfterSaleInterface
{
    public function __construct(private readonly DomainCommerceAfterSaleService $service, private readonly AppAfterSaleCommandService $commands) {}

    public function find(int $afterSaleId): ?array
    {
        return $this->service->find($afterSaleId);
    }

    public function approve(int $afterSaleId, string $provider, ?int $amount = null, string $remark = ''): array
    {
        $this->service->assertOwner($afterSaleId, $provider);
        return $this->commands->approve(new CommerceAfterSaleActionData($afterSaleId, $provider, $amount, remark: $remark));
    }

    public function reject(int $afterSaleId, string $provider, string $reason): array
    {
        $this->service->assertOwner($afterSaleId, $provider);
        return $this->commands->reject(new CommerceAfterSaleActionData($afterSaleId, $provider, reason: $reason));
    }

    public function receive(int $afterSaleId, string $provider): array
    {
        $this->service->assertOwner($afterSaleId, $provider);
        return $this->commands->receive(new CommerceAfterSaleActionData($afterSaleId, $provider));
    }

    public function refund(int $afterSaleId, string $provider): array
    {
        $this->service->assertOwner($afterSaleId, $provider);
        return $this->commands->refund(new CommerceAfterSaleActionData($afterSaleId, $provider));
    }
}
