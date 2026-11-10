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

namespace Plugin\SupplyChain\Handler\Yunzhong;

use Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong\YunzhongClient;
use Plugin\SupplyChain\Interfaces\Data\CheckoutCommand;
use Plugin\SupplyChain\Interfaces\QuoteInterface;

final class QuoteHandler implements QuoteInterface
{
    public function __construct(private readonly YunzhongClient $client) {}

    public function assertAvailable(CheckoutCommand $command): void
    {
        $this->client->call('quote.available', 'POST', '/app/order/availableCheck', PayloadMapper::checkout($command));
    }

    public function quote(CheckoutCommand $command): array
    {
        $data = $this->client->call('quote.before_check', 'POST', '/app/order/beforeCheck', PayloadMapper::checkout($command));
        return ['freight_cents' => PayloadMapper::integer($data['freight'] ?? null, 'freight'), 'checked_at' => gmdate('c')];
    }
}
