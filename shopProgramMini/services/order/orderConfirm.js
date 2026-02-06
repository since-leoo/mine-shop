import { config } from '../../config/index';
import { mockIp, mockReqId } from '../../utils/mock';
import { request } from '../request';

const ORDER_API_PREFIX = '/api/v1/order';

/** 获取结算mock数据 */
function mockFetchSettleDetail(params) {
  const { delay } = require('../_utils/delay');
  const { genSettleDetail } = require('../../model/order/orderConfirm');

  return delay().then(() => genSettleDetail(params));
}

/** 提交mock订单 */
function mockDispatchCommitPay() {
  const { delay } = require('../_utils/delay');

  return delay().then(() => ({
    data: {
      isSuccess: true,
      tradeNo: '350930961469409099',
      payInfo: '{}',
      code: null,
      transactionId: 'E-200915180100299000',
      msg: null,
      interactId: '15145',
      channel: 'wechat',
      limitGoodsList: null,
    },
    code: 'Success',
    msg: null,
    requestId: mockReqId(),
    clientIp: mockIp(),
    rt: 891,
    success: true,
  }));
}

/** 获取结算数据 */
export function fetchSettleDetail(params) {
  if (config.useMock) {
    return mockFetchSettleDetail(params);
  }
  const payload = buildPreviewPayload(params);
  return request({
    url: ORDER_API_PREFIX + '/preview',
    method: 'POST',
    data: payload,
    needAuth: true,
  }).then((data) => ({ data: transformPreviewResponse(data) }));
}

/* 提交订单 */
export function dispatchCommitPay(params) {
  if (config.useMock) {
    return mockDispatchCommitPay(params);
  }
  const payload = buildSubmitPayload(params);
  return request({
    url: ORDER_API_PREFIX + '/submit',
    method: 'POST',
    data: payload,
    needAuth: true,
  }).then((data) => ({ code: 'Success', data: transformSubmitResponse(data) }));
}

export function requestOrderPayment(params) {
  if (config.useMock) {
    return Promise.resolve({
      tradeNo: params.orderNo,
      channel: params.payMethod,
      payInfo: '{}',
      isPaid: params.payMethod === 'balance',
      payAmount: '0',
      payMethods: [],
    });
  }

  const payload = {
    order_no: params.orderNo,
    pay_method: params.payMethod,
  };

  return request({
    url: ORDER_API_PREFIX + '/payment',
    method: 'POST',
    data: payload,
    needAuth: true,
  }).then((data) => transformPaymentResponse(data));
}

/** 开发票 */
export function dispatchSupplementInvoice() {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    return delay();
  }

  return new Promise((resolve) => {
    resolve('real api');
  });
}

function buildBaseOrderPayload(params = {}) {
  const goodsRequestList = Array.isArray(params.goodsRequestList)
    ? params.goodsRequestList
        .map((item) => ({
          skuId: Number(item?.skuId || item?.sku_id || 0),
          quantity: Number(item?.quantity || 0),
        }))
        .filter((item) => item.skuId > 0 && item.quantity > 0)
    : [];

  const storeInfoList = Array.isArray(params.storeInfoList)
    ? params.storeInfoList.map((store) => ({
        storeId: store?.storeId || store?.store_id || '',
        remark: store?.remark || '',
      }))
    : [];

  const couponList = Array.isArray(params.couponList)
    ? params.couponList
        .map((coupon) => ({
          couponId: coupon?.couponId || coupon?.coupon_id,
          storeId: coupon?.storeId || coupon?.store_id,
        }))
        .filter((coupon) => coupon.couponId)
    : [];

  const payload = {
    goodsRequestList,
    storeInfoList,
  };

  if (couponList.length > 0) {
    payload.couponList = couponList;
  }

  if (params.userAddressReq) {
    payload.userAddress = normalizeAddressPayload(params.userAddressReq);
  }

  if (params.orderType) {
    payload.orderType = params.orderType;
  }

  return payload;
}

function buildPreviewPayload(params = {}) {
  const base = buildBaseOrderPayload(params);
  return toSnakeCaseKeys(base);
}

function buildSubmitPayload(params = {}) {
  const base = buildBaseOrderPayload(params);
  base.totalAmount = Number(params.totalAmount || 0);
  base.userName = params.userName || '';
  if (params.invoiceRequest) {
    base.invoiceRequest = params.invoiceRequest;
  }
  return toSnakeCaseKeys(base);
}

function normalizeAddressPayload(address = {}) {
  const detail = address.detailAddress || address.detail || '';
  const province = address.province || address.provinceName || '';
  const city = address.city || address.cityName || '';
  const district = address.district || address.districtName || '';
  const fullAddress = address.fullAddress || address.full_address || '';

  return {
    name: address.name || '',
    phone: address.phone || '',
    province,
    city,
    district,
    detailAddress: detail,
    fullAddress,
    addressTag: address.addressTag,
    id: address.id || address.addressId,
  };
}

function toSnakeCaseKeys(input) {
  if (Array.isArray(input)) {
    return input.map((item) => toSnakeCaseKeys(item));
  }
  if (input && typeof input === 'object') {
    return Object.keys(input).reduce((acc, key) => {
      const value = input[key];
      if (value === undefined) {
        return acc;
      }
      acc[toSnakeCase(key)] = toSnakeCaseKeys(value);
      return acc;
    }, {});
  }
  return input;
}

function toSnakeCase(str) {
  if (typeof str !== 'string' || !str) {
    return '';
  }
  return str
    .replace(/([A-Z])/g, '_$1')
    .replace(/_{2,}/g, '_')
    .replace(/^_+/, '')
    .toLowerCase();
}

function transformPreviewResponse(data = {}) {
  return {
    settleType: data.settle_type ?? 0,
    userAddress: formatAddressResponse(data.user_address),
    totalGoodsCount: data.total_goods_count ?? 0,
    packageCount: data.package_count ?? 0,
    totalAmount: toCentString(data.total_amount),
    totalPayAmount: toCentString(data.total_pay_amount),
    totalDiscountAmount: toCentString(data.total_discount_amount),
    totalPromotionAmount: toCentString(data.total_promotion_amount),
    totalCouponAmount: toCentString(data.total_coupon_amount),
    totalSalePrice: toCentString(data.total_sale_price),
    totalGoodsAmount: toCentString(data.total_goods_amount),
    totalDeliveryFee: toCentString(data.total_delivery_fee),
    invoiceRequest: data.invoice_request || null,
    skuImages: data.sku_images || null,
    deliveryFeeList: data.delivery_fee_list || null,
    storeGoodsList: Array.isArray(data.store_goods_list)
      ? data.store_goods_list.map(transformStoreGoods)
      : [],
    inValidGoodsList: Array.isArray(data.invalid_goods_list) ? data.invalid_goods_list : [],
    outOfStockGoodsList: Array.isArray(data.out_of_stock_goods_list) ? data.out_of_stock_goods_list : [],
    limitGoodsList: Array.isArray(data.limit_goods_list) ? data.limit_goods_list : [],
    abnormalDeliveryGoodsList: Array.isArray(data.abnormal_delivery_goods_list)
      ? data.abnormal_delivery_goods_list
      : [],
    invoiceSupport: data.invoice_support ?? 0,
  };
}

function transformStoreGoods(store) {
  return {
    storeId: store?.store_id || '1',
    storeName: store?.store_name || '',
    remark: store?.remark || '',
    goodsCount: store?.goods_count ?? 0,
    deliveryFee: toCentString(store?.delivery_fee),
    deliveryWords: store?.delivery_words || null,
    storeTotalAmount: toCentString(store?.store_total_amount),
    storeTotalPayAmount: toCentString(store?.store_total_pay_amount),
    storeTotalDiscountAmount: toCentString(store?.store_total_discount_amount),
    storeTotalCouponAmount: toCentString(store?.store_total_coupon_amount),
    skuDetailVos: Array.isArray(store?.sku_detail_list)
      ? store.sku_detail_list.map(transformSkuDetail)
      : [],
    couponList: store?.coupon_list || [],
  };
}

function transformSkuDetail(detail) {
  return {
    storeId: detail?.store_id || '1',
    storeName: detail?.store_name || '',
    spuId: detail?.spu_id || '',
    skuId: detail?.sku_id || '',
    goodsName: detail?.goods_name || '',
    image: detail?.image || '',
    reminderStock: detail?.reminder_stock ?? 0,
    quantity: detail?.quantity ?? 0,
    payPrice: toCentString(detail?.pay_price),
    totalSkuPrice: toCentString(detail?.total_sku_price),
    discountSettlePrice: toCentString(detail?.discount_settle_price),
    realSettlePrice: toCentString(detail?.real_settle_price),
    settlePrice: toCentString(detail?.settle_price),
    originPrice: toCentString(detail?.origin_price),
    tagPrice: detail?.tag_price || null,
    tagText: detail?.tag_text || null,
    skuSpecLst: Array.isArray(detail?.sku_spec_list)
      ? detail.sku_spec_list.map((spec) => ({
          specTitle: spec?.spec_title || '',
          specValue: spec?.spec_value || '',
        }))
      : [],
    promotionIds: detail?.promotion_ids || null,
    weight: detail?.weight || 0,
    unit: detail?.unit || 'KG',
    roomId: detail?.room_id || null,
    egoodsName: detail?.egoods_name || null,
    uid: detail?.uid || '',
    saasId: detail?.saas_id || '',
  };
}

function formatAddressResponse(address) {
  if (!address) {
    return null;
  }
  return {
    name: address.name || '',
    phone: address.phone || '',
    provinceName: address.province || '',
    cityName: address.city || '',
    districtName: address.district || '',
    detailAddress: address.detail_address || '',
    fullAddress: address.full_address || '',
    addressTag: address.address_tag || '',
    checked: address.checked ?? true,
    id: address.id || '',
  };
}

function toCentString(value) {
  if (value === null || value === undefined) {
    return '0';
  }
  return String(value);
}

function transformSubmitResponse(data = {}) {
  return {
    isSuccess: !!data.is_success,
    tradeNo: data.trade_no || '',
    transactionId: data.transaction_id || '',
    channel: data.channel || '',
    payInfo: data.pay_info || null,
    payAmount: toCentString(data.pay_amount),
    limitGoodsList: data.limit_goods_list || null,
    payMethods: Array.isArray(data.pay_methods) ? data.pay_methods : [],
  };
}

function transformPaymentResponse(data = {}) {
  return {
    tradeNo: data.trade_no || '',
    channel: data.channel || '',
    payInfo: data.pay_info || null,
    isPaid: !!data.is_paid,
    payAmount: toCentString(data.pay_amount),
    payMethods: Array.isArray(data.pay_methods) ? data.pay_methods : [],
  };
}
