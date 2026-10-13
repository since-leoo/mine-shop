import { request } from '../request';

/** 获取订单详情数据 */
export function fetchOrderDetail(params: any) {
  const orderNo = (typeof params === 'string' ? params : params?.orderNo)?.trim();
  if (!orderNo) return Promise.reject(new Error('缺少订单编号'));
  return request({
    url: `/api/v1/order/detail/${encodeURIComponent(orderNo)}`,
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
