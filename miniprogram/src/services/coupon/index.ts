import { request } from '../request';
import { config } from '../../config';

/**
 * 获取会员优惠券列表
 * @param status TDesign状态码
 */
export function fetchCouponList(status = 'default') {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { getCouponList } = require('../../model/coupon');
    return delay().then(() => getCouponList(status));
  }
  return request({ url: '/api/v1/member/coupons', method: 'GET', data: { status }, needAuth: true }).then(
    (res: any) => {
      if (Array.isArray(res)) return res;
      if (Array.isArray(res?.list)) return res.list;
      if (Array.isArray(res?.records)) return res.records;
      if (Array.isArray(res?.data)) return res.data;
      if (Array.isArray(res?.data?.list)) return res.data.list;
      return [];
    },
  );
}

/**
 * 获取优惠券详情
 * @param id 优惠券ID（coupon表的ID）
 */
export function fetchCouponDetail(id: number | string) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { getCoupon } = require('../../model/coupon');
    const { genAddressList } = require('../../model/address');
    return delay().then(() => {
      const result = { detail: getCoupon(id), storeInfoList: genAddressList() };
      result.detail.useNotes = `1个订单限用1张，不能与其它类型的优惠券叠加使用（运费券除外）\n2.仅适用于各区域正常售卖商品，不支持团购、抢购、预售类商品`;
      result.detail.storeAdapt = '商城通用';
      return result;
    });
  }
  return request({ url: `/api/v1/coupons/${id}`, method: 'GET' }).then((res: any) => ({
    detail: res?.detail || {},
    storeInfoList: [],
  }));
}

/**
 * 领取优惠券
 */
export function receiveCoupon(couponId: number | string) {
  return request({
    url: '/api/v1/member/coupons/receive',
    method: 'POST',
    data: { couponId },
    needAuth: true,
  });
}

/**
 * 获取可领取的优惠券列表（领券中心）
 */
export function fetchAvailableCoupons(limit = 20) {
  return request({
    url: '/api/v1/coupons/available',
    method: 'GET',
    data: { limit },
    needAuth: true,
  }).then((res: any) => res?.list || []);
}
