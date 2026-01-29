<?php

declare(strict_types=1);

namespace HyperfTests\Feature\Domain\Product;

use App\Application\Product\Assembler\ProductAssembler;
use App\Domain\Product\Entity\ProductAttributeEntity;
use App\Domain\Product\Entity\ProductSkuEntity;
use App\Domain\Product\ValueObject\ProductStatus;
use PHPUnit\Framework\TestCase;

final class ProductAssemblerTest extends TestCase
{
    public function testToCreateEntityMapsPayload(): void
    {
        $payload = [
            'product_code' => 'P001',
            'category_id' => 5,
            'brand_id' => 9,
            'name' => '测试商品',
            'sub_title' => '副标题',
            'main_image' => '/main.jpg',
            'gallery_images' => ['/g1.jpg', '/g2.jpg'],
            'description' => '描述',
            'detail_content' => '<p>详情</p>',
            'attributes' => ['color' => 'red'],
            'min_price' => 100,
            'max_price' => 200,
            'virtual_sales' => 10,
            'real_sales' => 3,
            'is_recommend' => true,
            'is_hot' => false,
            'is_new' => true,
            'shipping_template_id' => 7,
            'sort' => 88,
            'status' => ProductStatus::ACTIVE->value,
            'skus' => [
                [
                    'id' => 1,
                    'sku_code' => 'SKU1',
                    'sku_name' => '红色',
                    'sale_price' => 199.9,
                    'stock' => 5,
                ],
            ],
            'product_attributes' => [
                ['id' => 30, 'attribute_name' => '材质', 'value' => '棉'],
            ],
            'gallery' => [
                ['url' => '/extra-1.jpg'],
            ],
        ];

        $entity = ProductAssembler::toCreateEntity($payload);

        self::assertSame('测试商品', $entity->getName());
        self::assertSame(ProductStatus::ACTIVE->value, $entity->getStatus());
        self::assertSame('/main.jpg', $entity->getMainImage());
        self::assertSame($payload['gallery'], $entity->getGallery());

        $skus = $entity->getSkus();
        self::assertIsArray($skus);
        self::assertCount(1, $skus);
        self::assertInstanceOf(ProductSkuEntity::class, $skus[0]);
        self::assertSame('SKU1', $skus[0]->getSkuCode());

        $attributes = $entity->getAttributes();
        self::assertIsArray($attributes);
        self::assertInstanceOf(ProductAttributeEntity::class, $attributes[0]);
        self::assertSame('材质', $attributes[0]->getAttributeName());
    }

    public function testToUpdateEntityKeepsPartialSections(): void
    {
        $payload = [
            'product_code' => 'UPDATED',
            'category_id' => 1,
            'brand_id' => null,
            'name' => '更新后的商品',
            'status' => ProductStatus::INACTIVE->value,
        ];

        $entity = ProductAssembler::toUpdateEntity(99, $payload);

        self::assertSame(99, $entity->getId());
        self::assertSame('更新后的商品', $entity->getName());
        self::assertSame(ProductStatus::INACTIVE->value, $entity->getStatus());
        self::assertNull($entity->getSkus());
        self::assertNull($entity->getAttributes());
        self::assertSame([], $entity->getGallery());
    }
}
