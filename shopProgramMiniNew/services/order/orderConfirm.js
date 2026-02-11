import { request } from '../request';
import { mockIp, mockReqId } from '../../utils/mock';

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
  return request({
    url: '/api/v1/order/preview',
    method: 'POST',
    data: params,
    needAuth: true,
  });
}

/** 提交订单 */
export function dispatchCommitPay(params) {
  return request({
    url: '/api/v1/order/submit',
    method: 'POST',
    data: params,
    needAuth: true,
  });
}

/** 开发票 — 后端暂无此接口，使用mock */
export function dispatchSupplementInvoice() {
  const { delay } = require('../_utils/delay');
  return delay();
}
