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

namespace HyperfTests\Feature\Domain\Product;

use App\Domain\Catalog\Product\Entity\ProductAttributeEntity;
use App\Domain\Catalog\Product\Entity\ProductEntity;
use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use App\Domain\Catalog\Product\Enum\ProductStatus;
use App\Infrastructure\Model\Product\Product;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Relations\HasMany;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ProductEntityTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testToArrayComputesDiffIds(): void
    {
        $entity = new ProductEntity();
        $entity->setProductCode('CODE1');
        $entity->setName('测试商品');
        $entity->setGalleryImages(['/a.jpg']);
        $entity->setGallery([
            ['url' => '/extra.jpg'],
        ]);

        $entity->setSkus([
            $this->makeSku(2, 199.0),
        ]);

        $attribute = new ProductAttributeEntity();
        $attribute->setId(20);
        $attribute->setAttributeName('材质');
        $attribute->setValue('棉');
        $entity->setAttributes([
            $attribute,
        ]);

        $product = \Mockery::mock(Product::class);
        $skuRelation = \Mockery::mock(HasMany::class);
        $skuRelation->shouldReceive('pluck')->with('id')->andReturn(new Collection([1, 2]));
        $product->shouldReceive('skus')->andReturn($skuRelation);

        $attrRelation = \Mockery::mock(HasMany::class);
        $attrRelation->shouldReceive('pluck')->with('id')->andReturn(new Collection([10, 20]));
        $product->shouldReceive('attributes')->andReturn($attrRelation);

        $payload = $entity->toArray($product);

        self::assertSame('CODE1', $payload['product_code']);
        self::assertSame([['url' => '/extra.jpg']], $payload['gallery']);
        self::assertSame([1], $payload['delete_sku_ids']);
        self::assertSame([10], $payload['delete_attr_ids']);
    }

    public function testEnsureCanPersistRequiresNameAndCategory(): void
    {
        $entity = new ProductEntity();
        $entity->setCategoryId(1);
        $entity->setSkus([$this->makeSku(1, 99.0)]);

        $this->expectException(\DomainException::class);
        $entity->ensureCanPersist(true);
    }

    public function testEnsureCanPersistAllowsPartialUpdate(): void
    {
        $entity = new ProductEntity();
        $entity->setId(1);
        $entity->setName(null);
        $entity->setCategoryId(null);
        $entity->setSkus(null);

        $entity->ensureCanPersist();
        self::assertTrue(true);
    }

    public function testChangeStatusRejectsInvalidTransition(): void
    {
        $entity = new ProductEntity();
        $entity->changeStatus(ProductStatus::ACTIVE->value);

        $this->expectException(\DomainException::class);
        $entity->changeStatus(ProductStatus::DRAFT->value);
    }

    public function testSetSkusRejectsPlainArrays(): void
    {
        $entity = new ProductEntity();
        $this->expectException(\DomainException::class);
        /* @phpstan-ignore-next-line intentionally passing invalid data */
        $entity->setSkus([['id' => 1]]);
    }

    private function makeSku(int $id, float $price): ProductSkuEntity
    {
        $sku = new ProductSkuEntity();
        $sku->setId($id);
        $sku->setSkuCode('SKU-' . $id);
        $sku->setSkuName('规格' . $id);
        $sku->setSalePrice($price);
        $sku->setStock(10);
        return $sku;
    }
}
