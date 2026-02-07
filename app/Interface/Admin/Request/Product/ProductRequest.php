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

namespace App\Interface\Admin\Request\Product;

use App\Domain\Product\Contract\ProductInput;
use App\Domain\Product\Enum\ProductStatus;
use App\Interface\Admin\Dto\Product\ProductDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

class ProductRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function storeRules(): array
    {
        $rules = $this->baseRules();
        $rules['product_code'][] = Rule::unique('products', 'product_code');
        $rules['name'][0] = 'required'; // 确保名称必填
        $rules['category_id'][0] = 'required'; // 确保分类必填
        $rules['skus'] = ['required', 'array', 'min:1']; // 确保至少有一个SKU
        if ($this->input('min_price') !== null) {
            $rules['min_price'][] = 'lte:max_price';
        }
        if ($this->input('max_price') !== null) {
            $rules['max_price'][] = 'gte:min_price';
        }
        return $rules;
    }

    public function updateRules(): array
    {
        $rules = $this->baseRules();
        $rules['product_code'][] = Rule::unique('products', 'product_code')
            ->ignore((int) $this->route('id'));
        $rules['category_id'][0] = 'sometimes';
        $rules['name'][0] = 'sometimes';
        $rules['skus'][0] = 'sometimes'; // 更新时 SKU 可选
        if ($this->input('min_price') !== null) {
            $rules['min_price'][] = 'lte:max_price';
        }
        if ($this->input('max_price') !== null) {
            $rules['max_price'][] = 'gte:min_price';
        }
        return $rules;
    }

    public function attributes(): array
    {
        return [
            'product_code' => '商品编码',
            'category_id' => '分类',
            'brand_id' => '品牌',
            'name' => '商品名称',
            'sub_title' => '副标题',
            'main_image' => '主图',
            'gallery_images' => '图片集',
            'description' => '简介',
            'detail_content' => '详情内容',
            'attributes' => '属性',
            'min_price' => '最低价',
            'max_price' => '最高价',
            'virtual_sales' => '虚拟销量',
            'real_sales' => '真实销量',
            'is_recommend' => '是否推荐',
            'is_hot' => '是否热销',
            'is_new' => '是否新品',
            'shipping_template_id' => '运费模板',
            'sort' => '排序',
            'status' => '状态',
        ];
    }

    /**
     * 转换为 DTO.
     */
    public function toDto(?int $id): ProductInput
    {
        $params = $this->validated();
        $params['id'] = $id;

        // 处理 attributes：前端可能传递完整对象，需要转换为简单格式
        if (isset($params['attributes']) && \is_array($params['attributes'])) {
            $params['product_attributes'] = array_map(static function ($attr) {
                return [
                    'id' => $attr['id'] ?? null,
                    'attribute_name' => $attr['attribute_name'] ?? '',
                    'value' => $attr['value'] ?? '',
                ];
            }, $params['attributes']);
            unset($params['attributes']);
        }

        // 处理 skus：前端可能传递完整对象，需要转换为简单格式
        if (isset($params['skus']) && \is_array($params['skus'])) {
            $params['skus'] = array_map(static function ($sku) {
                return [
                    'id' => $sku['id'] ?? null,
                    'sku_code' => $sku['sku_code'] ?? null,
                    'sku_name' => $sku['sku_name'] ?? '',
                    'spec_values' => $sku['spec_values'] ?? null,
                    'image' => $sku['image'] ?? null,
                    'cost_price' => $sku['cost_price'] ?? 0,
                    'market_price' => $sku['market_price'] ?? 0,
                    'sale_price' => $sku['sale_price'] ?? 0,
                    'stock' => $sku['stock'] ?? 0,
                    'warning_stock' => $sku['warning_stock'] ?? 0,
                    'weight' => $sku['weight'] ?? 0,
                    'status' => $sku['status'] ?? 'active',
                ];
            }, $params['skus']);
        }

        // 处理 gallery：前端可能传递完整对象
        if (isset($params['gallery']) && \is_array($params['gallery'])) {
            $params['gallery'] = array_map(static function ($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'image_url' => $item['image_url'] ?? '',
                    'alt_text' => $item['alt_text'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_primary' => $item['is_primary'] ?? false,
                ];
            }, $params['gallery']);
        }

        return Mapper::map($params, new ProductDto());
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRules(): array
    {
        return [
            'product_code' => ['nullable', 'string', 'max:100'],
            'category_id' => ['required', 'integer', 'min:1', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'min:1', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'sub_title' => ['nullable', 'string', 'max:255'],
            'main_image' => ['nullable', 'string', 'max:500'],
            'gallery_images' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
            'detail_content' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'virtual_sales' => ['nullable', 'integer', 'min:0'],
            'real_sales' => ['nullable', 'integer', 'min:0'],
            'is_recommend' => ['nullable', 'boolean'],
            'is_hot' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'shipping_template_id' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(ProductStatus::values())],
            'skus' => ['required', 'array', 'min:1'],
            'skus.*.id' => ['nullable', 'integer', 'min:1'],
            'skus.*.sku_code' => ['nullable', 'string', 'max:50'],
            'skus.*.sku_name' => ['required', 'string', 'max:100'],
            'skus.*.spec_values' => ['nullable', 'array'],
            'skus.*.image' => ['nullable', 'string', 'max:500'],
            'skus.*.cost_price' => ['required', 'numeric', 'min:0'],
            'skus.*.market_price' => ['required', 'numeric', 'min:0'],
            'skus.*.sale_price' => ['required', 'numeric', 'min:0'],
            'skus.*.stock' => ['required', 'integer', 'min:0'],
            'skus.*.warning_stock' => ['nullable', 'integer', 'min:0'],
            'skus.*.weight' => ['nullable', 'numeric', 'min:0'],
            'skus.*.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'product_attributes' => ['nullable', 'array'],
            'product_attributes.*.id' => ['nullable', 'integer', 'min:1'],
            'product_attributes.*.attribute_name' => ['required_with:product_attributes', 'string', 'max:100'],
            'product_attributes.*.value' => ['required_with:product_attributes', 'string', 'max:500'],
            'gallery' => ['nullable', 'array'],
            'gallery.*.id' => ['nullable', 'integer', 'min:1'],
            'gallery.*.image_url' => ['required_with:gallery', 'string', 'max:500'],
            'gallery.*.alt_text' => ['nullable', 'string', 'max:255'],
            'gallery.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'gallery.*.is_primary' => ['nullable', 'boolean'],
        ];
    }
}
