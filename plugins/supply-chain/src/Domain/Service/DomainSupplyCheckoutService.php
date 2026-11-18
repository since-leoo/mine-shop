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

namespace Plugin\SupplyChain\Domain\Service;

use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\ValueObject\SupplyAddressVo;
use Plugin\SupplyChain\Interfaces\Data\CheckoutCommand;
use Plugin\SupplyChain\Interfaces\Data\PurchaseLine;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;

final class DomainSupplyCheckoutService
{
    public function __construct(private readonly SupplyRepository $repository, private readonly SupplyChainManagerInterface $clients) {}

    public function assertAvailable(string $provider, array $snapshot = []): void
    {
        if ($provider !== 'supply_chain') {
            throw SupplyChainError::FULFILLMENT_PROVIDER_NOT_REGISTERED->exception();
        }
        $connectionId = (int) ($snapshot['connection_id'] ?? 0);
        if ($connectionId < 1) {
            $mapping = $this->repository->mappingBySku((int) ($snapshot['sku_id'] ?? 0));
            if ($mapping === null || $mapping['link']['status'] !== 'active' || $mapping['product_link']['status'] !== 'active') {
                throw SupplyChainError::PRODUCT_MAPPING_UNAVAILABLE->exception();
            }
            $connectionId = (int) $mapping['link']['connection_id'];
        }
        $connection = $this->repository->require('connections', $connectionId);
        if (! $connection['enabled'] || $connection['mode'] !== 'active') {
            throw SupplyChainError::CONNECTION_ORDERS_PAUSED->exception();
        }
    }

    public function groups(array $items, array $address, string $type): array
    {
        if ($type !== 'normal') {
            throw SupplyChainError::PROMOTION_NOT_SUPPORTED->exception();
        }
        SupplyAddressVo::fromArray($address);
        $groups = [];
        $seen = [];
        foreach ($items as $item) {
            $skuId = (int) ($item['sku_id'] ?? $item['id'] ?? 0);
            $this->assertAvailable('supply_chain', ['sku_id' => $skuId]);
            if (isset($seen[$skuId])) {
                throw SupplyChainError::CHECKOUT_SKU_DUPLICATED->exception();
            }
            $seen[$skuId] = true;
            $mapping = $this->repository->mappingBySku($skuId);
            $qty = (int) $item['quantity'];
            if ($qty < 1 || $mapping['product']['status'] !== 'available') {
                throw SupplyChainError::PRODUCT_SUPPLY_STOPPED->exception();
            }
            if (strtotime($mapping['product']['fetched_at']) < time() - 1800) {
                throw SupplyChainError::INVENTORY_SNAPSHOT_EXPIRED->exception();
            }
            $connectionId = (int) $mapping['link']['connection_id'];
            $snapshot = ['schema_version' => 1, 'connection_id' => $connectionId, 'external_product_id' => $mapping['product']['external_product_id'], 'external_sku_id' => $mapping['sku']['external_sku_id'], 'binding_version' => (int) $mapping['link']['binding_version'], 'cost_price' => (int) $mapping['sku']['cost_price'], 'quantity' => $qty];
            $groups[$connectionId]['items'][$skuId] = ['provider' => 'supply_chain', 'snapshot' => $snapshot];
            $groups[$connectionId]['lines'][] = new PurchaseLine($snapshot['external_sku_id'], $qty);
        }
        return $groups;
    }

    public function quote(int $connectionId, array $group, array $address): array
    {
        $command = new CheckoutCommand($group['lines'], SupplyAddressVo::fromArray($address));
        $client = $this->clients->forConnection($connectionId);
        $client->quotes()->assertAvailable($command);
        $quote = $client->quotes()->quote($command);
        foreach ($group['items'] as &$item) {
            $item['snapshot']['quoted_at'] = time();
            $item['snapshot']['freight_cents'] = $quote['freight_cents'];
        } unset($item);
        return ['items' => $group['items'], 'shipping_fee' => (int) $quote['freight_cents']];
    }

    public function combine(array $groups): array
    {
        $items = [];
        $fee = 0;
        foreach ($groups as $group) {
            $items += $group['items'];
            $fee += $group['shipping_fee'];
        }
        return ['items' => $items, 'shipping_fee' => $fee];
    }
}
