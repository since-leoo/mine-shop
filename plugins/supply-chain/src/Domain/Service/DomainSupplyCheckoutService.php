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
            throw new \DomainException('未注册的外部履约提供方');
        }
        $connectionId = (int) ($snapshot['connection_id'] ?? 0);
        if ($connectionId < 1) {
            $mapping = $this->repository->mappingBySku((int) ($snapshot['sku_id'] ?? 0));
            if ($mapping === null || $mapping['link']['status'] !== 'active' || $mapping['product_link']['status'] !== 'active') {
                throw new \DomainException('供应链商品映射不可用');
            }
            $connectionId = (int) $mapping['link']['connection_id'];
        }
        $connection = $this->repository->require('connections', $connectionId);
        if (! $connection['enabled'] || $connection['mode'] !== 'active') {
            throw new \DomainException('供应链连接当前不接收新订单');
        }
    }

    public function groups(array $items, array $address, string $type): array
    {
        if ($type !== 'normal') {
            throw new \DomainException('供应链商品暂不支持秒杀或拼团');
        }
        SupplyAddressVo::fromArray($address);
        $groups = [];
        $seen = [];
        foreach ($items as $item) {
            $skuId = (int) ($item['sku_id'] ?? $item['id'] ?? 0);
            $this->assertAvailable('supply_chain', ['sku_id' => $skuId]);
            if (isset($seen[$skuId])) {
                throw new \DomainException('同一供应链SKU不能在结算明细重复出现');
            }
            $seen[$skuId] = true;
            $mapping = $this->repository->mappingBySku($skuId);
            $qty = (int) $item['quantity'];
            if ($qty < 1 || $mapping['product']['status'] !== 'available') {
                throw new \DomainException('供应链商品已停供');
            }
            if (strtotime($mapping['product']['fetched_at']) < time() - 1800) {
                throw new \DomainException('供应链库存快照已过期，请同步商品后重试');
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
