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

namespace App\Domain\Catalog\Product\Trait;

use App\Domain\Catalog\Product\Entity\ProductSkuEntity;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ContentSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ProductSetting;
use App\Infrastructure\Exception\BusinessException;
use App\Infrastructure\Model\Product\ProductSku;
use Mine\Support\ResultCode;

/**
 * 商品实体配置校验 Trait.
 */
trait ProductSettingsTrait
{
    public function applySettingConstraints(ProductSetting $productSetting, ContentSetting $contentSetting): void
    {
        $this->ensureGalleryLimit($productSetting->maxGallery());
        $this->hydrateSkuWarningStock($productSetting->stockWarning());
        $this->ensureSkuCodes($productSetting->autoGenerateSku());
        $this->assertContentCompliance($productSetting->contentFilter());
        $this->assertContentCompliance($contentSetting->prohibitedKeywords());
    }

    private function ensureGalleryLimit(int $max): void
    {
        if ($max <= 0) {
            return;
        }

        $images = $this->getGalleryImages() ?? [];
        if (\count($images) > $max) {
            throw new BusinessException(ResultCode::FAIL, \sprintf('商品主图最多支持 %d 张，请删除多余图片。', $max));
        }
    }

    private function hydrateSkuWarningStock(int $defaultWarning): void
    {
        if ($defaultWarning <= 0) {
            return;
        }

        $skus = $this->getSkus();
        if ($skus === null) {
            return;
        }

        foreach ($skus as $sku) {
            if (! $sku instanceof ProductSkuEntity) {
                continue;
            }
            if ($sku->getWarningStock() > 0) {
                continue;
            }
            $sku->setWarningStock($defaultWarning);
        }
    }

    private function ensureSkuCodes(bool $autoGenerate): void
    {
        $skus = $this->getSkus();
        if ($skus === null) {
            return;
        }

        foreach ($skus as $sku) {
            if (! $sku instanceof ProductSkuEntity) {
                continue;
            }

            $code = trim((string) $sku->getSkuCode());
            if ($code !== '') {
                continue;
            }

            if (! $autoGenerate) {
                throw new BusinessException(ResultCode::FAIL, 'SKU 编码为必填项，请为每个规格设置唯一编码。');
            }

            $sku->setSkuCode(ProductSku::generateSkuCode());
        }
    }

    /**
     * @param string[] $keywords
     */
    private function assertContentCompliance(array $keywords): void
    {
        if ($keywords === []) {
            return;
        }

        $fields = [
            '商品名称' => $this->getName(),
            '副标题' => $this->getSubTitle(),
            '商品描述' => $this->getDescription(),
            '详情内容' => $this->getDetailContent(),
        ];

        foreach ($keywords as $keyword) {
            $keyword = trim((string) $keyword);
            if ($keyword === '') {
                continue;
            }
            foreach ($fields as $label => $value) {
                $text = $value ?? '';
                if ($text === '') {
                    continue;
                }
                if (mb_stripos($text, $keyword) !== false) {
                    throw new BusinessException(ResultCode::FAIL, \sprintf('%s包含敏感词"%s"，请调整后再提交。', $label, $keyword));
                }
            }
        }
    }
}
