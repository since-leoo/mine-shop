import { request } from '../request';

export function fetchOrderLogistics(orderNo: string) {
  return request({
    url: `/api/v1/order/logistics/${encodeURIComponent(orderNo)}`,
    method: 'GET',
    needAuth: true,
  });
}
