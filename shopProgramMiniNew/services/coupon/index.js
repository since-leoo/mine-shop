import { request } from '../request';
import { config } from '../../config/index';

/**
 * 获取会员优惠券列表
 * @param {'default'|'useless'|'disabled'} status TDesign状态码
 */
export function fetchCouponList(status = 'default') {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { getCouponList } = require('../../model/coupon');
    return delay().then(() => getCouponList(status));
  }
  return request({ url: '/api/v1/member/coupons', method: 'GET', data: { status } }).then(
    (res) => res?.list || [],
  );
}

/**
 * 获取优惠券详情
 * @param {number|string} id 优惠券ID（coupon表的ID）
 */
export function fetchCouponDetail(id) {
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
  return request({ url: `/api/v1/coupons/${id}`, method: 'GET' }).then((res) => ({
    detail: res?.detail || {},
    storeInfoList: [],
  }));
}

/**
 * 领取优惠券
 * @param {number|string} couponId
 */
export function receiveCoupon(couponId) {
  return request({
    url: '/api/v1/member/coupons/receive',
    method: 'POST',
    data: { coupon_id: couponId },
  });
}
