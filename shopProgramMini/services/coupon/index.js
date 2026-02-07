import { config } from '../../config/index';
import { request } from '../request';
import { formatPrice } from '../../utils/price';

// ========== 我的优惠券列表（已领取） ==========

function mockFetchCoupon(status) {
  const { delay } = require('../_utils/delay');
  const { getCouponList } = require('../../model/coupon');
  return delay().then(() => getCouponList(status));
}

/** 获取我的优惠券列表 */
export function fetchCouponList(status = 'default') {
  if (config.useMock) {
    return mockFetchCoupon(status);
  }
  return request({
    url: '/api/v1/member/coupons',
    method: 'GET',
    data: { status },
    needAuth: true,
  }).then((data = {}) => {
    const list = Array.isArray(data.list) ? data.list : [];
    return list.map(transformCouponUser);
  });
}

// ========== 优惠券详情 ==========

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
    result.detail.use_notes =
      '1个订单限用1张，除运费券外，不能与其它类型的优惠券叠加使用（运费券除外）\n2.仅适用于各区域正常售卖商品，不支持团购、抢购、预售类商品';
    result.detail.store_adapt = '商城通用';

    if (result.detail.type === 'price') {
      result.detail.desc = `减免 ${formatPrice(result.detail.value)} 元`;
      if (result.detail.base) {
        result.detail.desc += `，满${formatPrice(result.detail.base)}元可用`;
      }
      result.detail.desc += '。';
    } else if (result.detail.type === 'discount') {
      result.detail.desc = `${result.detail.value}折`;
      if (result.detail.base) {
        result.detail.desc += `，满${formatPrice(result.detail.base)}元可用`;
      }
      result.detail.desc += '。';
    }
    return result;
  });
}

/** 获取优惠券详情 */
export function fetchCouponDetail(id, status = 'default') {
  if (config.useMock) {
    return mockFetchCouponDetail(id, status);
  }
  return request({
    url: `/api/v1/coupons/${id}`,
    method: 'GET',
  }).then((data = {}) => {
    // API 已经返回了完整的 detail 对象，直接使用
    const detail = data.detail || data;
    return {
      detail: detail,
      storeInfoList: [],
    };
  });
}

// ========== 可领取优惠券列表 ==========

function mockFetchAvailableCoupons() {
  return mockFetchCoupon('default').then((list = []) => {
    const normalized = list.map((item) => normalizeMockCoupon(item));
    return { list: normalized, total: normalized.length };
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
    needAuth: true,
  });
}

// ========== 领取优惠券 ==========

export function receiveCoupon(couponId) {
  return request({
    url: '/api/v1/member/coupons/receive',
    method: 'POST',
    data: { coupon_id: couponId },
    needAuth: true,
  });
}

// ========== 数据转换 ==========

/**
 * 将后端 coupon_user + coupon 关联数据转换为前端 coupon-card 组件所需格式
 * 后端字段: coupon_user.status, coupon.name, coupon.type, coupon.value, coupon.min_amount, coupon.start_time, coupon.end_time
 * 前端字段: key, status, type, value, tag, desc, title, timeLimit, currency, coupon_id
 */
function transformCouponUser(item = {}) {
  const coupon = item.coupon || {};
  const backendStatus = item.status || 'default';
  const status = mapCouponStatus(backendStatus);
  const type = mapCouponType(coupon.type);
  const value = parseCouponValue(coupon.type, coupon.value);
  const minAmount = Number(coupon.min_amount || 0);
  const tag = type === 2 ? '折扣' : '满减';
  const desc = buildCouponDesc(type, coupon.value, minAmount);
  const timeLimit = formatTimeRange(coupon.start_time, coupon.end_time);

  return {
    key: String(item.id || ''),
    coupon_id: String(item.coupon_id || ''),
    status,
    type,
    value,
    tag,
    desc,
    title: coupon.name || '优惠券',
    timeLimit,
    time_limit: timeLimit,
    currency: '¥',
    base: minAmount,
  };
}

function transformCouponDetail(data = {}) {
  const type = mapCouponType(data.type);
  const value = parseCouponValue(data.type, data.value);
  const minAmount = Number(data.min_amount || 0);
  const tag = type === 2 ? '折扣' : '满减';
  const desc = buildCouponDesc(type, data.value, minAmount);
  const timeLimit = formatTimeRange(data.start_time, data.end_time);

  return {
    key: String(data.id || ''),
    coupon_id: String(data.id || ''),
    status: 'default',
    type,
    value,
    tag,
    desc,
    title: data.name || '优惠券',
    timeLimit,
    time_limit: timeLimit,
    currency: '¥',
    base: minAmount,
    use_notes:
      '1个订单限用1张，不能与其它类型的优惠券叠加使用\n仅适用于商城正常售卖商品',
    store_adapt: '商城通用',
    description: data.description || '',
  };
}

/** 后端 status -> 前端 status */
function mapCouponStatus(status) {
  switch (status) {
    case 'unused':
    case 'default':
      return 'default';
    case 'used':
    case 'useless':
      return 'useless';
    case 'expired':
    case 'disabled':
      return 'disabled';
    default:
      return 'default';
  }
}

/** 后端 type -> 前端 type (数字，匹配 ui-coupon-card 的 CouponType) */
function mapCouponType(type) {
  if (type === 'percent' || type === 'discount') return 2; // ZK_COUPON
  return 1; // MJ_COUPON
}

/** 解析优惠券面值 */
function parseCouponValue(type, value) {
  const num = Number(value || 0);
  if (type === 'percent' || type === 'discount') {
    return num;
  }
  // fixed 类型，后端已返回分，直接使用
  return num;
}

/** 构建描述文案 */
function buildCouponDesc(type, value, minAmount) {
  const num = Number(value || 0);
  if (type === 'discount' || type === 2) {
    let desc = `${num}折`;
    if (minAmount > 0) desc += `，满${formatPrice(minAmount)}元可用`;
    return desc;
  }
  // fixed 类型，value 已经是分
  let desc = `减免${formatPrice(num)}元`;
  if (minAmount > 0) desc += `，满${formatPrice(minAmount)}元可用`;
  return desc;
}

/** 格式化时间范围 */
function formatTimeRange(start, end) {
  const fmt = (str) => {
    if (!str) return '';
    const d = new Date(str);
    if (isNaN(d.getTime())) return '';
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}.${m}.${day}`;
  };
  const s = fmt(start);
  const e = fmt(end);
  if (s && e) return `${s}-${e}`;
  if (s) return `${s}起`;
  if (e) return `至${e}`;
  return '';
}
