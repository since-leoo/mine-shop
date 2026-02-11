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
    return { data: attachCartSummary(data) };
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
  }).then((data = {}) => ({ data: attachCartSummary(data) }));
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
  }).then((data = {}) => ({ data: attachCartSummary(data) }));
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
  }).then((data = {}) => ({ data: attachCartSummary(data) }));
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
  }).then((data = {}) => ({ data: attachCartSummary(data) }));
}

// ========== 购物车汇总计算 ==========

function attachCartSummary(cart) {
  if (!cart || typeof cart !== 'object') return cart;
  const stores = cart.storeGoods || [];
  const goods = [];

  stores.forEach((store) => {
    (store.promotionGoodsList || []).forEach((promotion) => {
      (promotion.goodsPromotionList || []).forEach((item) => goods.push(item));
    });
  });

  const selectedGoods = goods.filter((item) => item.isSelected);
  const selectedGoodsCount = selectedGoods.reduce((sum, item) => sum + (item.quantity || 0), 0);
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
