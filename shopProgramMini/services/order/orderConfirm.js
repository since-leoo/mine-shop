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
  const goods_request_list = Array.isArray(params.goodsRequestList)
    ? params.goodsRequestList
        .map((item) => ({
          sku_id: Number(item?.skuId || item?.sku_id || 0),
          quantity: Number(item?.quantity || 0),
        }))
        .filter((item) => item.sku_id > 0 && item.quantity > 0)
    : [];

  const store_info_list = Array.isArray(params.storeInfoList)
    ? params.storeInfoList.map((store) => ({
        remark: store?.remark || '',
      }))
    : [];

  const coupon_list = Array.isArray(params.couponList)
    ? params.couponList
        .map((coupon) => ({
          coupon_id: Number(coupon?.couponId || coupon?.coupon_id || 0),
        }))
        .filter((coupon) => coupon.coupon_id > 0)
    : [];

  const payload = {
    goods_request_list,
    store_info_list,
  };

  if (coupon_list.length > 0) {
    payload.coupon_list = coupon_list;
  }

  if (params.userAddressReq) {
    payload.user_address = normalizeAddressPayload(params.userAddressReq);
  }

  if (params.orderType) {
    payload.order_type = String(params.orderType);
  }

  return payload;
}

function buildPreviewPayload(params = {}) {
  return buildBaseOrderPayload(params);
}

function buildSubmitPayload(params = {}) {
  const base = buildBaseOrderPayload(params);
  base.order_type = base.order_type || 'normal';
  base.total_amount = Number(params.totalAmount || 0);
  base.user_name = params.userName || '';
  if (params.invoiceRequest) {
    base.invoice_request = params.invoiceRequest;
  }
  return base;
}

function normalizeAddressPayload(address = {}) {
  return {
    name: address.name || '',
    phone: address.phone || '',
    province: address.province || '',
    city: address.city || '',
    district: address.district || '',
    detail: address.detail || '',
  };
}

function transformPreviewResponse(data = {}) {
  return {
    settleType: data.settle_type ?? 0,
    userAddress: formatAddressResponse(data.user_address),
    storeName: data.store_name || '',
    goodsCount: data.goods_count ?? 0,
    items: Array.isArray(data.items)
      ? data.items.map(transformItem)
      : [],
    price: {
      goodsAmount: Number(data.price?.goods_amount ?? 0),
      discountAmount: Number(data.price?.discount_amount ?? 0),
      shippingFee: Number(data.price?.shipping_fee ?? 0),
      totalAmount: Number(data.price?.total_amount ?? 0),
      payAmount: Number(data.price?.pay_amount ?? 0),
    },
    couponAmount: Number(data.coupon_amount ?? 0),
    invoiceSupport: data.invoice_support ?? 0,
  };
}

function transformItem(item) {
  return {
    productId: item?.product_id || 0,
    skuId: item?.sku_id || 0,
    productName: item?.product_name || '',
    skuName: item?.sku_name || '',
    productImage: item?.product_image || '',
    specValues: Array.isArray(item?.spec_values) ? item.spec_values : [],
    unitPrice: Number(item?.unit_price ?? 0),
    quantity: item?.quantity ?? 0,
    totalPrice: Number(item?.total_price ?? 0),
    weight: item?.weight || 0,
  };
}

function formatAddressResponse(address) {
  if (!address) {
    return null;
  }
  return {
    name: address.name || '',
    phone: address.phone || '',
    province: address.province || '',
    city: address.city || '',
    district: address.district || '',
    detail: address.detail || address.detail_address || '',
    full_address: address.full_address || '',
    checked: address.checked ?? true,
    id: address.id || '',
  };
}

function transformSubmitResponse(data = {}) {
  return {
    isSuccess: !!data.is_success,
    tradeNo: data.trade_no || '',
    transactionId: data.transaction_id || '',
    channel: data.channel || '',
    payInfo: data.pay_info || null,
    payAmount: Number(data.pay_amount ?? 0),
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
    payAmount: Number(data.pay_amount ?? 0),
    payMethods: Array.isArray(data.pay_methods) ? data.pay_methods : [],
  };
}
