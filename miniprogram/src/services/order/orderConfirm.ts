import { request } from '../request';

/** 获取结算数据 */
export function fetchSettleDetail(params: any) {
  return request({
    url: '/api/v1/order/preview',
    method: 'POST',
    data: params,
    needAuth: true,
  });
}

/** 提交订单 */
export function dispatchCommitPay(params: any) {
  return request({
    url: '/api/v1/order/submit',
    method: 'POST',
    data: params,
    needAuth: true,
  });
}

/** 轮询订单创建结果 */
export function fetchSubmitResult(tradeNo: string) {
  return request({
    url: `/api/v1/order/submit-result/${tradeNo}`,
    method: 'GET',
    needAuth: true,
  });
}

/** 发起支付 */
export function requestOrderPayment(params: any) {
  return request({
    url: '/api/v1/order/payment',
    method: 'POST',
    data: params,
    needAuth: true,
  });
}

/** 获取待支付订单支付信息（重新支付） */
export function fetchOrderPayInfo(orderNo: string) {
  return request({
    url: `/api/v1/order/pay-info/${orderNo}`,
    method: 'GET',
    needAuth: true,
  });
}

/** 开发票 -- 后端暂无此接口，使用mock */
export function dispatchSupplementInvoice() {
  const { delay } = require('../_utils/delay');
  return delay();
}
