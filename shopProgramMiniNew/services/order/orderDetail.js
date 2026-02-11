import { request } from '../request';

/** 获取订单详情mock数据 */
function mockFetchOrderDetail(params) {
  const { delay } = require('../_utils/delay');
  const { genOrderDetail } = require('../../model/order/orderDetail');
  return delay().then(() => genOrderDetail(params));
}

/** 获取订单详情数据 */
export function fetchOrderDetail(params) {
  const orderNo = params?.orderNo || params;
  if (!orderNo) return mockFetchOrderDetail(params);
  return request({
    url: `/api/v1/order/detail/${orderNo}`,
    method: 'GET',
    needAuth: true,
  });
}

/** 获取客服数据 — 后端暂无此接口，使用mock */
export function fetchBusinessTime(params) {
  const { delay } = require('../_utils/delay');
  const { genBusinessTime } = require('../../model/order/orderDetail');
  return delay().then(() => genBusinessTime(params));
}
