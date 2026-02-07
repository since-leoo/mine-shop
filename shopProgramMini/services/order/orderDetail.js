import { config } from '../../config/index';
import { request } from '../request';

/**
 * 后端状态(string) → 小程序状态码(number) 映射
 */
const STATUS_TO_TAB = {
  pending: 5,
  paid: 10,
  partial_shipped: 40,
  shipped: 40,
  completed: 50,
  cancelled: 80,
  refunded: 80,
};

/** 获取订单详情mock数据 */
function mockFetchOrderDetail(params) {
  const { delay } = require('../_utils/delay');
  const { genOrderDetail } = require('../../model/order/orderDetail');
  return delay().then(() => genOrderDetail(params));
}

/**
 * 将后端订单详情转换为小程序页面期望的格式
 */
function transformOrderDetail(order) {
  const address = order.address || {};
  return {
    orderId: order.id,
    orderNo: order.orderNo,
    parentOrderNo: order.orderNo,
    storeId: '',
    storeName: '',
    orderStatus: STATUS_TO_TAB[order.status] || 0,
    orderStatusName: order.orderStatusName,
    totalAmount: String(order.totalAmount),
    goodsAmount: String(order.goodsAmount),
    goodsAmountApp: String(order.goodsAmount),
    paymentAmount: String(order.payAmount),
    freightFee: String(order.shippingFee),
    discountAmount: String(order.discountAmount),
    remark: order.buyerRemark || '',
    createTime: order.createdAt,
    orderItemVOs: (order.items || []).map((item) => ({
      id: item.id,
      spuId: String(item.productId),
      skuId: String(item.skuId),
      goodsName: item.productName,
      goodsPictureUrl: item.productImage,
      specifications: item.skuName ? [{ specTitle: '规格', specValue: item.skuName }] : [],
      originPrice: String(item.unitPrice),
      actualPrice: String(item.unitPrice),
      buyQuantity: item.quantity,
      itemTotalAmount: String(item.totalPrice),
      buttonVOs: null,
    })),
    logisticsVO: {
      logisticsNo: '',
      logisticsCompanyName: '',
      receiverName: address.name || '',
      receiverPhone: address.phone || '',
      receiverProvince: address.province || '',
      receiverCity: address.city || '',
      receiverCountry: address.district || '',
      receiverArea: '',
      receiverAddress: address.detail || '',
    },
    paymentVO: {
      payStatus: order.payStatus === 'paid' ? 2 : 1,
      amount: String(order.payAmount),
      paySuccessTime: order.payTime || null,
    },
    buttonVOs: order.buttonVOs || [],
    autoCancelTime: order.expireTime
      ? new Date(order.expireTime).getTime() - Date.now()
      : null,
    couponAmount: String(order.discountAmount),
    trajectoryVos: [],
    invoiceStatus: null,
    invoiceDesc: null,
    invoiceVO: null,
  };
}

/** 获取订单详情数据 */
export function fetchOrderDetail(params) {
  if (config.useMock) {
    return mockFetchOrderDetail(params);
  }

  const orderNo = params.parameter;

  return request({
    url: `/api/v1/order/detail/${orderNo}`,
    method: 'GET',
    needAuth: true,
  }).then((data) => {
    return { data: transformOrderDetail(data) };
  });
}

/** 获取客服mock数据 */
function mockFetchBusinessTime(params) {
  const { delay } = require('../_utils/delay');
  const { genBusinessTime } = require('../../model/order/orderDetail');
  return delay().then(() => genBusinessTime(params));
}

/** 获取客服数据 */
export function fetchBusinessTime(params) {
  if (config.useMock) {
    return mockFetchBusinessTime(params);
  }

  // 客服数据暂无后端接口，返回默认值
  return Promise.resolve({
    data: {
      telphone: '',
      businessTime: [],
    },
  });
}
