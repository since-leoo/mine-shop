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

use App\Domain\Product\Entity\ProductAttributeEntity;
use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Entity\ProductSkuEntity;
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
