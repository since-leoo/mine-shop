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

namespace HyperfTests\Unit\Domain\Product;

use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Entity\ProductSkuEntity;
use App\Domain\SystemSetting\ValueObject\ContentSetting;
use App\Domain\SystemSetting\ValueObject\ProductSetting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ProductSettingsTraitTest extends TestCase
{
    public function testApplySettingConstraintsHydratesSkuData(): void
    {
        $entity = new ProductEntity();
        $entity->setName('测试商品');
        $entity->setGalleryImages(['/main.jpg']);

        $sku = new ProductSkuEntity();
        $sku->setSkuName('默认规格');
        $sku->setStock(5);
        $sku->setWarningStock(0);
        $sku->setSkuCode(null);
        $entity->setSkus([$sku]);

        $productSetting = new ProductSetting(true, 5, 20, false, []);
        $contentSetting = new ContentSetting([], '', '', '');

        $entity->applySettingConstraints($productSetting, $contentSetting);

        self::assertSame(20, $sku->getWarningStock());
        self::assertNotEmpty($sku->getSkuCode());
    }

    public function testApplySettingConstraintsThrowsWhenGalleryExceedsLimit(): void
    {
        $entity = new ProductEntity();
        $entity->setName('测试商品');
        $entity->setGalleryImages(array_fill(0, 3, '/image.jpg'));

        $productSetting = new ProductSetting(true, 2, 10, false, []);
        $contentSetting = new ContentSetting([], '', '', '');

        $this->expectException(\DomainException::class);
        $entity->applySettingConstraints($productSetting, $contentSetting);
    }

    public function testApplySettingConstraintsDetectsSensitiveWord(): void
    {
        $entity = new ProductEntity();
        $entity->setName('仿品精选');
        $entity->setGalleryImages(['/main.jpg']);

        $productSetting = new ProductSetting(true, 5, 10, false, ['仿品']);
        $contentSetting = new ContentSetting([], '', '', '');

        $this->expectException(\DomainException::class);
        $entity->applySettingConstraints($productSetting, $contentSetting);
    }
}
