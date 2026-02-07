import Dialog from 'tdesign-miniprogram/dialog/index';
import Toast from 'tdesign-miniprogram/toast/index';

import { dispatchCommitPay } from '../../../services/order/orderConfirm';

// 真实的提交支付
export const commitPay = (params) => {
  return dispatchCommitPay({
    goodsRequestList: params.goodsRequestList, // 待结算的商品集合
    invoiceRequest: params.invoiceRequest, // 发票信息
    userAddressReq: params.userAddressReq, // 地址信息(用户在购物选择更换地址)
    orderType: params.orderType || 'normal', // 订单类型 normal=普通订单
    totalAmount: params.totalAmount, // 总的支付金额（分）
    userName: params.userName, // 用户名
    storeInfoList: params.storeInfoList, //备注信息列表
    couponList: params.couponList,
  });
};

export const paySuccess = (ctx, payOrderInfo) => {
  const { payAmt, tradeNo, groupId, promotionId } = payOrderInfo;
  // 支付成功
  Toast({
    context: ctx,
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

export const payFail = (ctx, payOrderInfo, resultMsg) => {
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
        context: ctx,
        selector: '#t-toast',
        message: '支付取消',
        duration: 2000,
        icon: 'close-circle',
      });
    }
  } else {
    Toast({
      context: ctx,
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
export const wechatPayOrder = (ctx, payOrderInfo) => {
  // const payInfo = JSON.parse(payOrderInfo.payInfo);
  // const { timeStamp, nonceStr, signType, paySign } = payInfo;
  return new Promise((resolve) => {
    // demo 中直接走支付成功
    paySuccess(ctx, payOrderInfo);
    resolve();
    /* wx.requestPayment({
      timeStamp,
      nonceStr,
      package: payInfo.package,
      signType,
      paySign,
      success: function () {
        paySuccess(ctx, payOrderInfo);
        resolve();
      },
      fail: function (err) {
        payFail(ctx, payOrderInfo, err.errMsg);
      },
    }); */
  });
};
