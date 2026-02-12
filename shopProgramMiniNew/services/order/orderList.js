import { config } from '../../config/index';
import { request } from '../request';

/** 后端状态(string) → 小程序 tab key(number) */
const STATUS_TO_TAB = {
  pending: 5,
  paid: 10,
  partial_shipped: 40,
  shipped: 40,
  completed: 50,
  cancelled: 80,
  refunded: 80,
};

/** 小程序 tab key(number) → 后端状态(string) */
const TAB_TO_STATUS = {
  '-1': 'all',
  5: 'pending',
  10: 'paid',
  40: 'shipped',
  50: 'completed',
};

/** 获取订单列表mock数据 */
function mockFetchOrders(params) {
  const { delay } = require('../_utils/delay');
  const { genOrders } = require('../../model/order/orderList');
  return delay(200).then(() => genOrders(params));
}

/** 将后端订单数据转换为小程序页面期望的格式 */
function transformOrder(order) {
  return {
    id: order.id,
    orderNo: order.orderNo,
    parentOrderNo: order.orderNo,
    storeId: '',
    storeName: '',
    status: STATUS_TO_TAB[order.status] || 0,
    statusDesc: order.orderStatusName,
    amount: order.payAmount,
    totalAmount: order.totalAmount,
    logisticsNo: '',
    createTime: order.createdAt,
    goodsList: (order.items || []).map((item) => ({
      id: item.id,
      thumb: item.productImage,
      title: item.productName,
      skuId: item.skuId,
      spuId: item.productId,
      specs: item.skuName ? [item.skuName] : [],
      price: item.unitPrice,
      num: item.quantity,
      titlePrefixTags: [],
    })),
    buttons: order.buttonVOs || [],
    groupInfoVo: null,
    freightFee: order.shippingFee,
  };
}

/** 获取订单列表数据 */
export function fetchOrders(params) {
  if (config.useMock) {
    return mockFetchOrders(params);
  }
  const { pageNum = 1, pageSize = 10, orderStatus } = params.parameter || {};
  const status = orderStatus !== undefined ? (TAB_TO_STATUS[orderStatus] || 'all') : 'all';

  return request({
    url: '/api/v1/order/list',
    method: 'GET',
    data: { status, page: pageNum, page_size: pageSize },
    needAuth: true,
  }).then((data) => {
    const orders = (data.list || []).map(transformOrder);
    return {
      data: {
        orders,
        pageNum: data.pagination?.currentPage || pageNum,
        pageSize: data.pagination?.perPage || pageSize,
        totalCount: data.pagination?.total || 0,
      },
    };
  });
}

/** 获取订单列表mock数据 */
function mockFetchOrdersCount(params) {
  const { delay } = require('../_utils/delay');
  const { genOrdersCount } = require('../../model/order/orderList');
  return delay().then(() => genOrdersCount(params));
}

/** 获取订单列表统计 */
export function fetchOrdersCount(params) {
  if (config.useMock) {
    return mockFetchOrdersCount(params);
  }
  return request({
    url: '/api/v1/order/statistics',
    method: 'GET',
    needAuth: true,
  }).then((data) => {
    const tabsCount = [
      { tabType: 5, orderNum: data.pendingCount || 0 },
      { tabType: 10, orderNum: data.paidCount || 0 },
      { tabType: 40, orderNum: data.shippedCount || 0 },
      { tabType: 50, orderNum: data.completedCount || 0 },
    ];
    return { data: tabsCount };
  });
}

/** 取消订单 */
export function cancelOrder(orderNo) {
  return request({
    url: '/api/v1/order/cancel',
    method: 'POST',
    data: { order_no: orderNo },
    needAuth: true,
  });
}

/** 确认收货 */
export function confirmReceipt(orderNo) {
  return request({
    url: '/api/v1/order/confirm-receipt',
    method: 'POST',
    data: { order_no: orderNo },
    needAuth: true,
  });
}
