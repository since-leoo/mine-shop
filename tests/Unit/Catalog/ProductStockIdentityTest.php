<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Catalog;

use App\Domain\Catalog\Product\Entity\ProductEntity;
use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use App\Domain\Catalog\Product\Repository\ProductRepository;
use App\Infrastructure\Model\Product\Product;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\HasMany;
use Mockery;
use PHPUnit\Framework\TestCase;

final class ProductStockIdentityTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }
    public function testSavedSkuIdsPopulateStockEvent(): void
    {
        $entity = new ProductEntity();
        $sku = new ProductSkuEntity();
        $sku->setStock(10);
        $entity->setSkus([$sku]);
        $entity->setAttributes([]);
        $model = Mockery::mock(Product::class)->makePartial();
        $model->id = 77;
        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('createMany')->once()->andReturn(new Collection([(object) ['id' => 88, 'stock' => 10]]));
        $model->shouldReceive('skus')->once()->andReturn($relation);
        $attributes = Mockery::mock(HasMany::class);
        $attributes->shouldReceive('createMany')->once()->andReturn(new Collection());
        $model->shouldReceive('attributes')->once()->andReturn($attributes);
        $repo = Mockery::mock(ProductRepository::class)->makePartial();
        $repo->shouldReceive('create')->once()->andReturn($model);
        $repo->save($entity);
        self::assertSame(77, $entity->getId());
        self::assertSame([['sku_id' => 88, 'stock' => 10]], $entity->getStockData());
    }

}
