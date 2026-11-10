<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Catalog\Product\Service\DomainProductService;
use App\Domain\Infrastructure\Plugin\Dto\CommerceProductData;
use App\Domain\Infrastructure\Plugin\Repository\CommerceProductRepository;

final class DomainCommerceProductService
{
    private const FIELDS = ['name', 'category_id', 'brand_id', 'sub_title', 'main_image', 'gallery_images', 'description', 'detail_content', 'attributes', 'status', 'product_attributes'];
    private const SKU_FIELDS = ['sku_name', 'spec_values', 'image', 'cost_price', 'market_price', 'sale_price', 'weight', 'status'];

    public function __construct(private readonly DomainProductService $products, private readonly CommerceProductRepository $repository) {}

    public function import(string $provider, string $idempotencyKey, array $product): array
    {
        self::validateProvider($provider);
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 512) {
            throw new \InvalidArgumentException('商品导入幂等键不能为空或超过512字节');
        }
        $existing = $this->repository->claim($provider, $idempotencyKey);
        if ($existing !== null) {
            $result = $this->repository->find($existing);
            if ($result === null) {
                throw new \DomainException('已导入商品已删除，不能以相同身份重复导入');
            }
            return $result + ['created' => false];
        }
        if (trim((string) ($product['name'] ?? '')) === '' || (int) ($product['category_id'] ?? 0) <= 0 || empty($product['skus'])) {
            throw new \InvalidArgumentException('导入商品必须包含名称、本地分类和SKU');
        }
        $data = array_intersect_key($product, array_flip(self::FIELDS));
        $data['product_code'] = 'PRD-' . substr(hash('sha256', $provider . ':' . $idempotencyKey), 0, 32);
        $data['product_attributes'] ??= [];
        $data['status'] = 'draft';
        $data['freight_type'] = 'free';
        $data['skus'] = [];
        foreach ($product['skus'] as $sku) {
            self::validateSku($sku);
            if (empty($sku['sku_code']) || strlen((string) $sku['sku_code']) > 64) {
                throw new \InvalidArgumentException('导入SKU需要有效的本地编码');
            }
            $row = array_intersect_key($sku, array_flip([...self::SKU_FIELDS, 'sku_code']));
            // 上游库存必须在映射提交后走版本化投影，导入时不能成为未映射的可售商品。
            $row['stock'] = 0;
            $data['skus'][] = $row;
        }
        if (count(array_unique(array_column($data['skus'], 'sku_code'))) !== count($data['skus'])) {
            throw new \InvalidArgumentException('导入SKU编码重复');
        }
        $entity = $this->products->create(new CommerceProductData($data));
        $this->repository->complete($provider, $idempotencyKey, $entity->getId());
        return $this->repository->find($entity->getId()) + ['created' => true];
    }

    public function find(int $productId): ?array { return $this->repository->find($productId); }

    public function appendSkus(int $productId, string $provider, array $skus): array
    {
        self::validateProvider($provider);
        $existing = $this->repository->find($productId, true);
        if ($existing === null || ($existing['fulfillment_provider'] ?? 'merchant') !== $provider) {
            throw new \DomainException('商品不属于当前履约能力');
        }
        $all = $existing['skus'];
        $known = array_fill_keys(array_column($all, 'sku_code'), true);
        foreach ($skus as $sku) {
            $code = $sku['sku_code'] ?? null;
            if (! is_string($code) || trim($code) === '' || strlen($code) > 64) {
                throw new \InvalidArgumentException('新增SKU需要有效的本地编码');
            }
            if (isset($known[$code])) { continue; }
            self::validateSku($sku);
            if (! isset($sku['sale_price'])) { throw new \InvalidArgumentException('新增SKU缺少销售价'); }
            if ($this->repository->skuProductByCode($code) !== null) {
                throw new \DomainException('SKU编码已属于其他商品，禁止跨商品复用');
            }
            $new = array_intersect_key($sku, array_flip([...self::SKU_FIELDS, 'sku_code']));
            $new['stock'] = 0;
            $new['status'] ??= 'active';
            $all[] = $new;
            $known[$code] = true;
        }
        if (count($all) !== count($existing['skus'])) {
            $this->products->update(new CommerceProductData(['id' => $productId, 'skus' => $all]));
        }
        return $this->repository->find($productId);
    }

    public function sync(int $productId, string $provider, array $changes): array
    {
        self::validateProvider($provider);
        $existing = $this->repository->find($productId, true);
        if ($existing === null || ($existing['fulfillment_provider'] ?? 'merchant') !== $provider) {
            throw new \DomainException('商品不属于当前履约能力');
        }
        $data = array_intersect_key($changes, array_flip(self::FIELDS));
        $data['id'] = $productId;
        if (isset($changes['skus'])) {
            $skus = array_column($existing['skus'], null, 'id');
            foreach ($changes['skus'] as $sku) {
                $id = (int) ($sku['id'] ?? $sku['sku_id'] ?? 0);
                if (! isset($skus[$id])) {
                    throw new \DomainException('同步SKU不属于当前商品，新增规格须使用受控增规接口');
                }
                self::validateSku($sku);
                $skus[$id] = array_replace($skus[$id], array_intersect_key($sku, array_flip(self::SKU_FIELDS)));
            }
            $data['skus'] = array_values($skus);
        }
        $this->products->update(new CommerceProductData($data));
        return $this->repository->find($productId);
    }

    public static function validateProvider(string $provider): void
    {
        if ($provider === 'merchant' || ! preg_match('/^[a-z][a-z0-9_]{1,47}$/D', $provider)) {
            throw new \InvalidArgumentException('无效的外部履约能力标识');
        }
    }

    private static function validateSku(array $sku): void
    {
        foreach (['sale_price', 'cost_price', 'market_price'] as $field) {
            if (isset($sku[$field]) && (! is_int($sku[$field]) || $sku[$field] < 0)) {
                throw new \InvalidArgumentException('SKU金额必须为非负整数分');
            }
        }
    }
}
