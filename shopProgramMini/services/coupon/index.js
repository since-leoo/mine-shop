import { config } from '../../config/index';
import { request } from '../request';

/** 获取优惠券列表 */
function mockFetchCoupon(status) {
  const { delay } = require('../_utils/delay');
  const { getCouponList } = require('../../model/coupon');
  return delay().then(() => getCouponList(status));
}

/** 获取优惠券列表 */
export function fetchCouponList(status = 'default') {
  if (config.useMock) {
    return mockFetchCoupon(status);
  }
  return new Promise((resolve) => {
    resolve('real api');
  });
}

/** 获取优惠券 详情 */
function mockFetchCouponDetail(id, status) {
  const { delay } = require('../_utils/delay');
  const { getCoupon } = require('../../model/coupon');
  const { genAddressList } = require('../../model/address');

  return delay().then(() => {
    const result = {
      detail: getCoupon(id, status),
      storeInfoList: genAddressList(),
    };

    result.detail.coupon_id = result.detail.key;
    result.detail.time_limit = result.detail.timeLimit;
    result.detail.use_notes = `1个订单限用1张，除运费券外，不能与其它类型的优惠券叠加使用（运费券除外）\n2.仅适用于各区域正常售卖商品，不支持团购、抢购、预售类商品`;
    result.detail.store_adapt = `商城通用`;

    if (result.detail.type === 'price') {
      result.detail.desc = `减免 ${result.detail.value / 100} 元`;

      if (result.detail.base) {
        result.detail.desc += `，满${result.detail.base / 100}元可用`;
      }

      result.detail.desc += '。';
    } else if (result.detail.type === 'discount') {
      result.detail.desc = `${result.detail.value}折`;

      if (result.detail.base) {
        result.detail.desc += `，满${result.detail.base / 100}元可用`;
      }

      result.detail.desc += '。';
    }

    return result;
  });
}

/** 获取优惠券 详情 */
export function fetchCouponDetail(id, status = 'default') {
  if (config.useMock) {
    return mockFetchCouponDetail(id, status);
  }
  return request({
    url: `/api/v1/coupons/${id}`,
    method: 'GET',
  });
}

function normalizeMockCoupon(item = {}) {
  const rawType = item.type;
  const type =
    rawType === 'discount' || rawType === 'percent' || rawType === 2 ? 'percent' : 'fixed';
  const tag = type === 'percent' ? '折扣' : '满减';
  const label = item.desc || `${tag}优惠`;
  const discountValue =
    type === 'percent' ? Math.round(Number(item.value || 0) * 10) : Number(item.value || 0);
  return {
    coupon_id: item.key,
    name: item.title,
    type,
    discount_value: discountValue,
    threshold_amount: item.base || 0,
    tag,
    label,
    description: label,
    start_time: '2024-01-01T00:00:00+08:00',
    end_time: '2024-12-31T23:59:59+08:00',
    available_quantity: 100,
    total_quantity: 100,
    per_user_limit: 1,
    received_quantity: 0,
    is_receivable: true,
  };
}

function mockFetchAvailableCoupons() {
  return mockFetchCoupon('default').then((list = []) => {
    const normalized = list.map((item) => normalizeMockCoupon(item));
    return {
      list: normalized,
      total: normalized.length,
    };
  });
}

export function fetchAvailableCoupons(params = {}) {
  if (config.useMock) {
    return mockFetchAvailableCoupons();
  }
  const data = {};
  if (params && params.spuId) {
    data.spu_id = params.spuId;
  }
  if (params && params.limit) {
    data.limit = params.limit;
  }
  return request({
    url: '/api/v1/coupons/available',
    method: 'GET',
    data,
  });
}
