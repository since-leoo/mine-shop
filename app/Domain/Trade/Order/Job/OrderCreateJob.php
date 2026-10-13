<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Job;

use App\Application\Api\Order\AppOrderSubmissionService;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;

class OrderCreateJob extends Job
{
    public int $maxAttempts = 3;

    /** 旧字段保留以兼容部署前已经序列化的任务。 */
    public function __construct(
        protected string $tradeNo,
        protected array $entitySnapshot = [],
        protected array $itemsPayload = [],
        protected array $addressPayload = [],
        protected array $couponUserIds = [],
        protected string $orderType = 'normal',
        protected string $stockHashKey = 'product:stock',
    ) {}

    public function handle(): void
    {
        $legacy = $this->entitySnapshot === [] ? null : [
            'snapshot' => $this->entitySnapshot, 'items' => $this->itemsPayload,
            'address' => $this->addressPayload, 'coupon_user_ids' => $this->couponUserIds,
            'stock_key' => $this->stockHashKey,
        ];
        ApplicationContext::getContainer()->get(AppOrderSubmissionService::class)->process($this->tradeNo, $legacy);
    }

    public function fail(\Throwable $e): void
    {
        ApplicationContext::getContainer()->get(AppOrderSubmissionService::class)->fail($this->tradeNo, $e->getMessage());
    }
}
