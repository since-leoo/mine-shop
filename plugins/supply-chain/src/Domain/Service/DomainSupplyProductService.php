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

use App\Domain\Infrastructure\Plugin\Contract\CommerceInventoryInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceProductInterface;
use Plugin\SupplyChain\Domain\Contract\ImportInput;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\ValueObject\TaskSpecVo;

final class DomainSupplyProductService
{
    public function __construct(private readonly SupplyRepository $repository, private readonly DomainSupplyTaskService $tasks, private readonly CommerceProductInterface $catalog, private readonly CommerceInventoryInterface $inventory) {}

    public function requestImports(ImportInput $input, int $actor): array
    {
        $data = $input->toArray();
        $connection = $this->repository->require('connections', (int) $data['connection_id']);
        if (! $connection['enabled'] || $connection['mode'] !== 'active') {
            throw new \DomainException('该连接已暂停新增选品');
        }
        $ids = [];
        foreach ($data['external_product_ids'] as $externalId) {
            $ids[] = $this->tasks->enqueue(new TaskSpecVo((int) $data['connection_id'], 'import', 'import:' . $data['connection_id'] . ':' . hash('sha256', $externalId), ['external_product_id' => $externalId, 'category_id' => (int) $data['category_id'], 'price_multiplier_bps' => (int) round(($data['price_multiplier'] ?? 1) * 10000)], $actor));
        }
        return ['task_ids' => $ids];
    }

    public function requestSync(int $linkId, int $actor): int
    {
        $link = $this->repository->require('product_links', $linkId);
        $product = $this->repository->require('products', (int) $link['supply_product_id']);
        return $this->tasks->enqueue(new TaskSpecVo((int) $link['connection_id'], 'sync', 'sync:' . $linkId . ':' . date('YmdHi'), ['external_product_id' => $product['external_product_id'], 'link_id' => $linkId], $actor));
    }

    /** Transactional persistence. Stock projection happens afterwards through the core port. */
    public function persist(int $connectionId, array $product, array $options, int $version): array
    {
        $external = (string) $product['external_product_id'];
        $now = date('Y-m-d H:i:s');
        if (empty($product['skus'])) {
            throw new \DomainException('上游商品没有有效SKU');
        }
        $this->repository->query('products')->insertOrIgnore($this->repository->encode(['connection_id' => $connectionId, 'external_product_id' => $external, 'name' => $product['name'], 'snapshot' => $product, 'snapshot_hash' => hash('sha256', json_encode($product, \JSON_THROW_ON_ERROR)), 'status' => $product['available'] ? 'available' : 'unavailable', 'fetched_at' => $now, 'created_at' => $now, 'updated_at' => $now]));
        $remote = $this->repository->decode($this->repository->query('products')->where('connection_id', $connectionId)->where('external_product_id', $external)->lockForUpdate()->first());
        $link = $this->repository->decode($this->repository->query('product_links')->where('supply_product_id', $remote['id'])->first());
        if ((int) $remote['snapshot_version'] > $version && $link !== null) {
            return ['link_id' => (int) $link['id'], 'product_id' => (int) $link['product_id'], 'projections' => [], 'skipped' => 'stale_snapshot'];
        }
        if ((int) $remote['snapshot_version'] === $version && ! hash_equals((string) $remote['snapshot_hash'], hash('sha256', json_encode($product, \JSON_THROW_ON_ERROR)))) {
            throw new \DomainException('相同同步版本的商品内容不一致，拒绝覆盖');
        }
        $skus = [];
        $multiplier = (int) ($options['price_multiplier_bps'] ?? $link['sync_policy']['price_multiplier_bps'] ?? 10000);
        foreach ($product['skus'] as $sku) {
            $key = (string) $sku['external_sku_id'];
            if ($key === '' || $sku['cost_price'] < 0 || $sku['stock'] < 0) {
                throw new \DomainException('上游规格、价格或库存无效');
            }
            $skuName = (string) ($sku['title'] ?? '');
            if (trim($skuName) === '') {
                $skuName = (string) $product['name'];
            }
            $skus[] = ['sku_code' => 'SC' . $connectionId . '-' . mb_substr(hash('sha256', $key), 0, 24), 'sku_name' => $skuName, 'sale_price' => max((int) $sku['sale_price'], intdiv((int) $sku['cost_price'] * $multiplier + 9999, 10000)), 'cost_price' => (int) $sku['cost_price'], 'market_price' => (int) ($sku['market_price'] ?? $sku['sale_price']), 'stock' => 0, 'spec_values' => $sku['spec_values'] ?? [], 'weight' => (float) ($sku['weight'] ?? 0), 'image' => $sku['image'] ?? $product['main_image'], 'status' => 'active'];
        }
        if ($link === null) {
            if (empty($options['category_id'])) {
                throw new \DomainException('未导入商品需要选择本地分类');
            }
            $local = $this->catalog->import('supply_chain', 'supply:' . $connectionId . ':' . $external, ['name' => $product['name'], 'category_id' => (int) $options['category_id'], 'main_image' => $product['main_image'] ?? '', 'gallery_images' => $product['images'] ?? [], 'description' => mb_substr(strip_tags($product['description'] ?? ''), 0, 1000), 'detail_content' => $product['description'] ?? '', 'status' => 'draft', 'skus' => $skus]);
            $linkId = $this->repository->insert('product_links', ['connection_id' => $connectionId, 'supply_product_id' => $remote['id'], 'product_id' => $local['product_id'], 'binding_version' => 1, 'sync_policy' => ['fields' => [], 'price_multiplier_bps' => $multiplier], 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
            $link = $this->repository->require('product_links', $linkId);
        } else {
            $local = $this->catalog->find((int) $link['product_id']) ?? throw new \DomainException('本地商品已不存在，无法继续同步');
            $knownCodes = array_column($local['skus'] ?? [], 'sku_code');
            $added = array_values(array_filter($skus, static fn ($sku) => ! \in_array($sku['sku_code'], $knownCodes, true)));
            if ($added !== []) {
                $local = $this->catalog->appendSkus((int) $link['product_id'], 'supply_chain', $added);
            }
            // Operational text/prices are locked by default. Never replace SKU identities.
            if (! $product['available']) {
                $this->catalog->sync((int) $link['product_id'], 'supply_chain', ['status' => 'inactive']);
            }
        }
        $localByCode = [];
        foreach ($local['skus'] ?? [] as $sku) {
            $localByCode[$sku['sku_code']] = $sku['sku_id'] ?? $sku['id'];
        }
        $projections = [];
        $seen = [];
        foreach ($product['skus'] as $i => $sku) {
            $externalSku = (string) $sku['external_sku_id'];
            $seen[] = $externalSku;
            $this->repository->query('skus')->insertOrIgnore($this->repository->encode(['connection_id' => $connectionId, 'supply_product_id' => $remote['id'], 'external_sku_id' => $externalSku, 'cost_price' => $sku['cost_price'], 'upstream_stock' => $sku['stock'], 'stock_version' => 0, 'snapshot' => $sku, 'created_at' => $now, 'updated_at' => $now]));
            $stored = $this->repository->decode($this->repository->query('skus')->where('connection_id', $connectionId)->where('external_sku_id', $externalSku)->lockForUpdate()->first());
            if ((int) $stored['supply_product_id'] !== (int) $remote['id']) {
                throw new \DomainException('上游SKU已属于另一个商品，禁止自动换绑');
            }
            if ((int) $stored['stock_version'] > $version) {
                continue;
            }
            $skuLink = $this->repository->decode($this->repository->query('sku_links')->where('supply_sku_id', $stored['id'])->first());
            if ($skuLink === null) {
                $localId = $localByCode[$skus[$i]['sku_code']] ?? null;
                if ($localId === null) {
                    throw new \DomainException('上游新增了规格，请通过受控商品更新补充规格后再同步');
                }
                $this->repository->insert('sku_links', ['connection_id' => $connectionId, 'product_link_id' => $link['id'], 'supply_sku_id' => $stored['id'], 'sku_id' => $localId, 'binding_version' => 1, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
                $skuLink = ['sku_id' => $localId];
            }
            if (isset($skuLink['id'])) {
                $this->repository->update('sku_links', (int) $skuLink['id'], ['status' => $product['available'] ? 'active' : 'unavailable', 'updated_at' => $now]);
            }
            $this->repository->update('skus', (int) $stored['id'], ['cost_price' => $sku['cost_price'], 'upstream_stock' => $sku['stock'], 'stock_version' => $version, 'snapshot' => $sku, 'updated_at' => $now]);
            $projections[] = ['sku_id' => (int) $skuLink['sku_id'], 'stock' => $product['available'] ? (int) $sku['stock'] : 0, 'version' => $version];
        }
        foreach ($this->repository->query('skus')->where('supply_product_id', $remote['id'])->whereNotIn('external_sku_id', $seen)->get() as $removed) {
            $skuLink = $this->repository->decode($this->repository->query('sku_links')->where('supply_sku_id', $removed->id)->first());
            if ($skuLink !== null) {
                $this->repository->update('sku_links', (int) $skuLink['id'], ['status' => 'unavailable', 'updated_at' => $now]);
                $projections[] = ['sku_id' => (int) $skuLink['sku_id'], 'stock' => 0, 'version' => $version];
            }
        }
        $this->repository->update('products', (int) $remote['id'], ['name' => $product['name'], 'snapshot' => $product, 'snapshot_version' => $version, 'snapshot_hash' => hash('sha256', json_encode($product, \JSON_THROW_ON_ERROR)), 'status' => $product['available'] ? 'available' : 'unavailable', 'fetched_at' => $now, 'updated_at' => $now]);
        return ['link_id' => (int) $link['id'], 'product_id' => (int) $link['product_id'], 'projections' => $projections];
    }

    public function project(array $result): void
    {
        if (isset($result['skipped'])) {
            return;
        }
        foreach ($result['projections'] as $sku) {
            $this->inventory->project('supply_chain', $sku['sku_id'], $sku['stock'], $sku['version']);
        }
        $this->repository->update('product_links', $result['link_id'], ['last_synced_at' => date('Y-m-d H:i:s'), 'last_error' => null, 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
