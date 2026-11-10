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

namespace Plugin\SupplyChain\Handler;

use Plugin\SupplyChain\Handler\Yunzhong\AfterSaleHandler;
use Plugin\SupplyChain\Handler\Yunzhong\CatalogHandler;
use Plugin\SupplyChain\Handler\Yunzhong\MessageHandler;
use Plugin\SupplyChain\Handler\Yunzhong\OrderHandler;
use Plugin\SupplyChain\Handler\Yunzhong\QuoteHandler;
use Plugin\SupplyChain\Handler\Yunzhong\SelectionHandler;
use Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong\YunzhongClient;
use Plugin\SupplyChain\Interfaces\AfterSaleInterface;
use Plugin\SupplyChain\Interfaces\CatalogInterface;
use Plugin\SupplyChain\Interfaces\MessageInterface;
use Plugin\SupplyChain\Interfaces\OrderInterface;
use Plugin\SupplyChain\Interfaces\QuoteInterface;
use Plugin\SupplyChain\Interfaces\SelectionInterface;
use Plugin\SupplyChain\Interfaces\SupplyChainClientInterface;

final class YunzhongClientHandler implements SupplyChainClientInterface
{
    public function __construct(private readonly YunzhongClient $client) {}

    public function catalog(): CatalogInterface
    {
        return new CatalogHandler($this->client);
    }

    public function selection(): SelectionInterface
    {
        return new SelectionHandler($this->client);
    }

    public function quotes(): QuoteInterface
    {
        return new QuoteHandler($this->client);
    }

    public function orders(): OrderInterface
    {
        return new OrderHandler($this->client);
    }

    public function afterSales(): AfterSaleInterface
    {
        return new AfterSaleHandler($this->client);
    }

    public function messages(): MessageInterface
    {
        return new MessageHandler($this->client);
    }
}
