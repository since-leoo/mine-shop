<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum CatalogError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case PRODUCT_NOT_FOUND = 120001;
    case PRODUCT_CREATE_FAILED = 120002;
    case CATEGORY_PARENT_INVALID = 120003;
    case CATEGORY_NAME_REQUIRED = 120004;
    case CATEGORY_LEVEL_INVALID = 120005;
    case CATEGORY_STATUS_INVALID = 120006;
    case CATEGORY_NOT_FOUND = 120007;
    case CATEGORY_HAS_CHILDREN = 120008;
    case CATEGORY_PARENT_NOT_FOUND = 120009;
    case CATEGORY_MOVE_TO_DESCENDANT = 120010;
    case BRAND_NAME_REQUIRED = 120011;
    case BRAND_STATUS_INVALID = 120012;
    case BRAND_NOT_FOUND = 120013;
    case BRAND_HAS_PRODUCTS = 120014;
    case MIN_PRICE_NEGATIVE = 120015;
    case MAX_PRICE_NEGATIVE = 120016;
    case PRICE_RANGE_INVALID = 120017;
    case EXTERNAL_SKU_DELETE_FORBIDDEN = 120018;
    case EXTERNAL_PRODUCT_DELETE_FORBIDDEN = 120019;
    case STOCK_CACHE_MISSING = 120020;
    case STOCK_DECREMENT_INVALID = 120021;
    case SKU_STOCK_INSUFFICIENT = 120022;
    case SKU_STOCK_NEGATIVE = 120023;
    case SKU_ENTITY_REQUIRED = 120024;
    case ATTRIBUTE_ENTITY_REQUIRED = 120025;
    case PRODUCT_STATUS_INVALID = 120026;
    case PRODUCT_TRANSITION_INVALID = 120027;
    case SKU_REQUIRED = 120028;
    case SKU_LIST_EMPTY = 120029;
    case IMAGE_LIMIT_EXCEEDED = 120030;
    case SKU_CODE_REQUIRED = 120031;
    case SENSITIVE_WORD = 120032;

    case SORT_DATA_REQUIRED = 120033;

    public function template(): string
    {
        return match ($this) {
            self::SORT_DATA_REQUIRED => '排序数据不能为空',
            self::PRODUCT_NOT_FOUND => '商品不存在',
            self::PRODUCT_CREATE_FAILED => '创建商品失败',
            self::CATEGORY_PARENT_INVALID => '父级分类无效',
            self::CATEGORY_NAME_REQUIRED => '分类名称不能为空',
            self::CATEGORY_LEVEL_INVALID => '分类层级必须大于0',
            self::CATEGORY_STATUS_INVALID => '分类状态值无效',
            self::CATEGORY_NOT_FOUND => '分类不存在',
            self::CATEGORY_HAS_CHILDREN => '该分类下还有子分类，无法删除',
            self::CATEGORY_PARENT_NOT_FOUND => '父级分类不存在',
            self::CATEGORY_MOVE_TO_DESCENDANT => '不能移动到子分类下',
            self::BRAND_NAME_REQUIRED => '品牌名称不能为空',
            self::BRAND_STATUS_INVALID => '品牌状态值无效',
            self::BRAND_NOT_FOUND => '品牌不存在',
            self::BRAND_HAS_PRODUCTS => '该品牌下还有商品，无法删除',
            self::MIN_PRICE_NEGATIVE => '最低价不能小于0',
            self::MAX_PRICE_NEGATIVE => '最高价不能小于0',
            self::PRICE_RANGE_INVALID => '最低价不能高于最高价',
            self::EXTERNAL_SKU_DELETE_FORBIDDEN => '外部履约SKU不可删除，请停用规格以保留历史映射',
            self::EXTERNAL_PRODUCT_DELETE_FORBIDDEN => '外部履约商品不可直接删除，请下架商品',
            self::STOCK_CACHE_MISSING => '商品库存缺失，保留库存调整事件等待恢复',
            self::STOCK_DECREMENT_INVALID => '扣减库存数量必须大于0',
            self::SKU_STOCK_INSUFFICIENT => 'SKU库存不足',
            self::SKU_STOCK_NEGATIVE => 'SKU库存不允许为负数',
            self::SKU_ENTITY_REQUIRED => 'SKU 数据必须通过实体传递',
            self::ATTRIBUTE_ENTITY_REQUIRED => '商品属性必须通过实体传递',
            self::PRODUCT_STATUS_INVALID => '无效的商品状态',
            self::PRODUCT_TRANSITION_INVALID => '商品状态不允许从 %s 变更为 %s',
            self::SKU_REQUIRED => '请至少添加一个SKU',
            self::SKU_LIST_EMPTY => 'SKU 列表不能为空',
            self::IMAGE_LIMIT_EXCEEDED => '商品主图最多支持 %d 张，请删除多余图片。',
            self::SKU_CODE_REQUIRED => 'SKU 编码为必填项，请为每个规格设置唯一编码。',
            self::SENSITIVE_WORD => '%s包含敏感词"%s"，请调整后再提交。',
        };
    }
}
