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
use Plugin\SupplyChain\Interfaces\CatalogInterface;
use Plugin\SupplyChain\Interfaces\Data\ProductQuery;

final class CatalogHandler implements CatalogInterface
{
    public function __construct(private readonly YunzhongClient $client) {}

    public function search(ProductQuery $query): array
    {
        $params = ['page' => $query->page, 'pageSize' => $query->pageSize];
        if ($query->keyword !== '') {
            $params['title'] = $query->keyword;
        }
        if ($query->supplierId !== null) {
            $params['supplier_id'] = PayloadMapper::id($query->supplierId);
        }
        if ($query->categoryId !== null) {
            $params['category_1_id'] = PayloadMapper::id($query->categoryId);
        }
        if ($query->source !== null) {
            $params['source'] = $query->source;
        }
        $data = $this->client->call('catalog.search', 'POST', '/app/product/storage/list', $params);
        return ['items' => array_map(fn (array $item): array => $this->product($item, false), PayloadMapper::list($data)),
            'total' => PayloadMapper::integer($data['total'] ?? null, 'total'), 'page' => $query->page, 'page_size' => $query->pageSize];
    }

    public function details(array $externalProductIds): array
    {
        $data = $this->client->call('catalog.details', 'POST', '/app/product/storage/detailList', ['ids' => PayloadMapper::ids($externalProductIds)]);
        return array_map(fn (array $item): array => $this->product($item, true), PayloadMapper::list($data));
    }

    public function stocks(array $externalProductIds): array
    {
        $data = $this->client->call('catalog.stocks', 'POST', '/app/product/storage/stockList', ['ids' => PayloadMapper::ids($externalProductIds, 100)]);
        $stocks = [];
        foreach (PayloadMapper::list($data) as $product) {
            foreach (PayloadMapper::list($product, 'skus') as $sku) {
                $stocks[] = ['external_product_id' => (string) PayloadMapper::id($product['id'] ?? ''),
                    'external_sku_id' => (string) PayloadMapper::id($sku['id'] ?? ''),
                    'stock' => PayloadMapper::integer($sku['stock'] ?? null, 'stock')];
            }
        }
        return $stocks;
    }

    public function suppliers(): array
    {
        $data = $this->client->call('catalog.suppliers', 'GET', '/app/supplier/getSuppliers');
        if (! \is_array($data)) {
            throw new \RuntimeException('供应商列表结构无效');
        }
        return $data;
    }

    public function categories(): array
    {
        $data = $this->client->call('catalog.categories', 'GET', '/app/category/tree');
        if (! \is_array($data)) {
            throw new \RuntimeException('供应链分类结构无效');
        }
        return $data;
    }

    public function supplierCategories(): array
    {
        // The scanned UI requests the whole supplier category list, without a supplier_id filter.
        $data = $this->client->call('catalog.supplier_categories', 'POST', '/app/product/storage/supplierCategoryAll');
        return PayloadMapper::list($data);
    }

    private function product(array $item, bool $detail): array
    {
        $skus = [];
        $skuItems = $detail ? PayloadMapper::list($item, 'skus') : ($item['skus'] ?? []);
        foreach ($skuItems as $sku) {
            $title = trim((string) ($sku['title'] ?? ''));
            // An unnamed SKU uses the product name for display, while its option remains the legacy default.
            $specification = $title === '' ? '默认' : $title;
            if ($title === '') {
                $title = trim((string) ($item['title'] ?? ''));
            }
            $skus[] = ['external_sku_id' => (string) PayloadMapper::id($sku['id'] ?? ''),
                'title' => $title, 'spec_values' => [['name' => '默认', 'value' => $specification]],
                'cost_price' => PayloadMapper::integer($sku['price'] ?? null, 'price'),
                'sale_price' => PayloadMapper::integer($sku['guide_price'] ?? null, 'guide_price'),
                'market_price' => PayloadMapper::integer($sku['origin_price'] ?? null, 'origin_price'),
                'stock' => PayloadMapper::integer($sku['stock'] ?? null, 'stock'),
                'weight' => (string) ($sku['weight'] ?? '0'), 'image' => (string) ($sku['image_url'] ?? '')];
        }
        $images = [];
        foreach ($item['gallery'] ?? [] as $image) {
            if ((int) ($image['type'] ?? 0) === 1 && ! empty($image['src'])) {
                $images[] = (string) $image['src'];
            }
        }
        if ($detail && ($skus === [] || trim((string) ($item['title'] ?? '')) === '')) {
            throw new \RuntimeException('供应链商品详情缺少标题或有效 SKU，禁止导入');
        }
        return ['external_product_id' => (string) PayloadMapper::id($item['id'] ?? ''),
            'name' => (string) ($item['title'] ?? ''), 'main_image' => (string) ($item['image_url'] ?? ''),
            'description' => (string) ($item['description'] ?? ''), 'images' => $images,
            'available' => (int) ($item['is_display'] ?? 0) === 1, 'skus' => $skus,
            'supplier_id' => (string) ($item['supplier_id'] ?? ''), 'source' => (string) ($item['source'] ?? ''),
            'raw_snapshot' => $item];
    }
}
