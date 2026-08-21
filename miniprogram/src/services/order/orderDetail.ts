import { request } from '../request';

/** 获取订单详情mock数据 */
function mockFetchOrderDetail(params: any) {
  const { delay } = require('../_utils/delay');
  const { genOrderDetail } = require('../../model/order/orderDetail');
  return delay().then(() => genOrderDetail(params));
}

/** 获取订单详情数据 */
export function fetchOrderDetail(params: any) {
  const orderNo = params?.orderNo || params;
  if (!orderNo) return mockFetchOrderDetail(params);
  return request({
    url: `/api/v1/order/detail/${orderNo}`,
    method: 'GET',
    needAuth: true,
  });
}

/** 获取客服配置，来源于会员中心后台动态配置。 */
export function fetchBusinessTime() {
  return request({
    url: '/api/v1/member/center',
    method: 'GET',
    needAuth: true,
  }).then((data: any) => data?.customerServiceInfo || {
    servicePhone: '',
    serviceTimeDuration: '',
  });
}
