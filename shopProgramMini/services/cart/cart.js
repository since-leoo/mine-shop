import { config } from '../../config/index';
import { request } from '../request';

/** 获取购物车mock数据 */
function mockFetchCartGroupData(params) {
  const { delay } = require('../_utils/delay');
  const { genCartGroupData } = require('../../model/cart');
  return delay().then(() => genCartGroupData(params));
}

/** 获取购物车数据 */
export function fetchCartGroupData(params) {
  if (config.useMock) {
    return mockFetchCartGroupData(params);
  }
  return request({ url: '/api/v1/cart', method: 'GET', needAuth: true }).then((data = {}) => {
    return { data: transformCartResponse(data) };
  });
}

/** 添加商品到购物车 */
export function addCartItem({ skuId, quantity }) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    return delay(200).then(() => ({ data: {} }));
  }
  return request({
    url: '/api/v1/cart/items',
    method: 'POST',
    data: { sku_id: skuId, quantity },
    needAuth: true,
  }).then((data = {}) => ({ data: transformCartResponse(data) }));
}

/** 更新购物车商品数量 */
export function updateCartItem(skuId, { quantity }) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    return delay(100).then(() => ({ data: {} }));
  }

  return request({
    url: `/api/v1/cart/items/${skuId}`,
    method: 'PUT',
    data: { quantity },
    needAuth: true,
  }).then((data = {}) => ({ data: transformCartResponse(data) }));
}

/** 删除购物车商品 */
export function deleteCartItem(skuId) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    return delay(100).then(() => ({ data: {} }));
  }
  return request({
    url: `/api/v1/cart/items/${skuId}`,
    method: 'DELETE',
    needAuth: true,
  }).then((data = {}) => ({ data: transformCartResponse(data) }));
}

/** 清空失效商品 */
export function clearInvalidCartItems() {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    return delay(100).then(() => ({ data: {} }));
  }
  return request({
    url: '/api/v1/cart/clear-invalid',
    method: 'POST',
    needAuth: true,
  }).then((data = {}) => ({ data: transformCartResponse(data) }));
}

// ========== 数据转换 ==========

function transformCartResponse(payload) {
  const storeGoods = Array.isArray(payload?.store_goods)
    ? payload.store_goods.map(transformStore)
    : [];
  const invalidGoodItems = Array.isArray(payload?.invalid_items)
    ? payload.invalid_items.map(transformGoods)
    : [];

  const result = {
    isNotEmpty: !!payload?.is_not_empty,
    storeGoods,
    invalidGoodItems,
  };

  return attachCartSummary(result);
}

function transformStore(store) {
  const promotionGoodsList = Array.isArray(store?.promotion_goods_list)
    ? store.promotion_goods_list.map(transformPromotion)
    : [];
  const shortageGoodsList = Array.isArray(store?.shortage_goods_list)
    ? store.shortage_goods_list.map(transformGoods)
    : [];

  return {
    storeId: store?.store_id || '1',
    storeName: store?.store_name || '',
    storeStatus: store?.store_status ?? 1,
    totalDiscountSalePrice: store?.total_discount_sale_price || '0',
    promotionGoodsList,
    shortageGoodsList,
  };
}

function transformPromotion(promotion) {
  return {
    title: promotion?.title || '',
    promotionCode: promotion?.promotion_code || '',
    promotionSubCode: promotion?.promotion_sub_code || '',
    promotionId: promotion?.promotion_id || null,
    tagText: promotion?.tag_text || [],
    promotionStatus: promotion?.promotion_status ?? 0,
    tag: promotion?.tag || '',
    description: promotion?.description || '',
    doorSillRemain: promotion?.door_sill_remain ?? null,
    isNeedAddOnShop: promotion?.is_need_add_on_shop ?? 0,
    goodsPromotionList: Array.isArray(promotion?.goods_list)
      ? promotion.goods_list.map(transformGoods)
      : [],
    lastJoinTime: promotion?.last_join_time || null,
  };
}

function transformGoods(goods) {
  const quantity = Number(goods?.quantity || 0);
  const stockQuantity = Number(goods?.stock_quantity || 0);
  const shortageStock = stockQuantity > 0 ? quantity > stockQuantity : true;

  return {
    cartId: goods?.cart_id || '',
    uid: goods?.uid || '',
    saasId: goods?.saas_id || '',
    storeId: goods?.store_id || '',
    storeName: goods?.store_name || '',
    spuId: goods?.spu_id || '',
    skuId: goods?.sku_id || '',
    isSelected: 1,
    thumb: goods?.thumb || goods?.primary_image || '',
    title: goods?.title || '',
    primaryImage: goods?.primary_image || goods?.thumb || '',
    quantity,
    stockStatus: !!goods?.stock_status,
    stockQuantity,
    price: goods?.price || '0',
    originPrice: goods?.origin_price || '0',
    tagPrice: goods?.tag_price || null,
    titlePrefixTags: goods?.title_prefix_tags || [],
    roomId: goods?.room_id || null,
    specInfo: normalizeSpecInfo(goods?.spec_info),
    joinCartTime: goods?.join_cart_time || null,
    available: goods?.available ?? 1,
    putOnSale: goods?.put_on_sale ?? 1,
    etitle: goods?.etitle || null,
    shortageStock,
  };
}

function normalizeSpecInfo(specInfo) {
  if (!Array.isArray(specInfo)) {
    return [];
  }
  return specInfo.map((spec) => ({
    specTitle: spec?.spec_title || spec?.specTitle || '',
    specValue: spec?.spec_value || spec?.specValue || '',
  }));
}

function attachCartSummary(cart) {
  const stores = cart.storeGoods || [];
  const goods = [];

  stores.forEach((store) => {
    (store.promotionGoodsList || []).forEach((promotion) => {
      (promotion.goodsPromotionList || []).forEach((item) => goods.push(item));
    });
  });

  const selectedGoods = goods.filter((item) => item.isSelected);
  const selectedGoodsCount = selectedGoods.reduce((sum, item) => {
    return sum + (item.quantity || 0);
  }, 0);

  const totalAmount = selectedGoods.reduce((sum, item) => {
    const price = parseInt(item.price || '0', 10) || 0;
    return sum + price * (item.quantity || 0);
  }, 0);

  cart.selectedGoodsCount = selectedGoodsCount;
  cart.totalAmount = String(totalAmount);
  cart.totalDiscountAmount = cart.totalDiscountAmount || '0';
  cart.isAllSelected = goods.length > 0 && selectedGoods.length === goods.length;

  return cart;
}
