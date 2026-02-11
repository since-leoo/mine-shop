import Dialog from 'tdesign-miniprogram/dialog/index';
import Toast from 'tdesign-miniprogram/toast/index';

import { dispatchCommitPay } from '../../../services/order/orderConfirm';

// 真实的提交支付
export const commitPay = (params) => {
  return dispatchCommitPay(params);
};

export const paySuccess = (payOrderInfo) => {
  const { payAmt, tradeNo, groupId, promotionId } = payOrderInfo;
  // 支付成功
  Toast({
    context: this,
    selector: '#t-toast',
    message: '支付成功',
    duration: 2000,
    icon: 'check-circle',
  });

  const params = {
    totalPaid: payAmt,
    orderNo: tradeNo,
  };
  if (groupId) {
    params.groupId = groupId;
  }
  if (promotionId) {
    params.promotionId = promotionId;
  }
  const paramsStr = Object.keys(params)
    .map((k) => `${k}=${params[k]}`)
    .join('&');
  // 跳转支付结果页面
  wx.redirectTo({ url: `/pages/order/pay-result/index?${paramsStr}` });
};

export const payFail = (payOrderInfo, resultMsg) => {
  if (resultMsg === 'requestPayment:fail cancel') {
    if (payOrderInfo.dialogOnCancel) {
      //结算页，取消付款，dialog提示
      Dialog.confirm({
        title: '是否放弃付款',
        content: '商品可能很快就会被抢空哦，是否放弃付款？',
        confirmBtn: '放弃',
        cancelBtn: '继续付款',
      }).then(() => {
        wx.redirectTo({ url: '/pages/order/order-list/index' });
      });
    } else {
      //订单列表页，订单详情页，取消付款，toast提示
      Toast({
        context: this,
        selector: '#t-toast',
        message: '支付取消',
        duration: 2000,
        icon: 'close-circle',
      });
    }
  } else {
    Toast({
      context: this,
      selector: '#t-toast',
      message: `支付失败：${resultMsg}`,
      duration: 2000,
      icon: 'close-circle',
    });
    setTimeout(() => {
      wx.redirectTo({ url: '/pages/order/order-list/index' });
    }, 2000);
  }
};

// 微信支付方式
export const wechatPayOrder = (payOrderInfo) => {
  // const payInfo = JSON.parse(payOrderInfo.payInfo);
  // const { timeStamp, nonceStr, signType, paySign } = payInfo;
  return new Promise((resolve) => {
    // demo 中直接走支付成功
    paySuccess(payOrderInfo);
    resolve();
    /* wx.requestPayment({
      timeStamp,
      nonceStr,
      package: payInfo.package,
      signType,
      paySign,
      success: function () {
        paySuccess(payOrderInfo);
        resolve();
      },
      fail: function (err) {
        payFail(payOrderInfo, err.errMsg);
      },
    }); */
  });
};
