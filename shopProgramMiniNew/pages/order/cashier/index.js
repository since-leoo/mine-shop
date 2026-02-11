import Toast from 'tdesign-miniprogram/toast/index';
import { fetchSubmitResult, requestOrderPayment, fetchOrderPayInfo } from '../../../services/order/orderConfirm';

const MAX_POLL_ATTEMPTS = 15;
const POLL_INTERVAL = 2000;

Page({
  data: {
    tradeNo: '',
    payAmount: 0,
    payMethods: [],
    selectedMethod: '',
    creating: true,
    failed: false,
    failReason: '',
    paying: false,
  },

  _pollTimer: null,
  _pollAttempt: 0,

  onLoad(options) {
    const { tradeNo, payAmount, payMethods, status, mode } = options;

    this.setData({ tradeNo });

    if (mode === 'repay') {
      // 重新支付已有订单：直接拉取支付信息
      this.setData({ creating: true });
      this.fetchPayInfo(tradeNo);
      return;
    }

    // 新订单提交模式
    const methods = payMethods ? JSON.parse(decodeURIComponent(payMethods)) : [];
    this.setData({
      payAmount: Number(payAmount) || 0,
      payMethods: methods,
    });

    if (status === 'created' || status === 'pending') {
      this.onOrderReady(methods, Number(payAmount) || 0);
    } else {
      this.startPolling();
    }
  },

  onUnload() {
    this.clearPolling();
  },

  // ========== 重新支付：拉取订单支付信息 ==========
  fetchPayInfo(tradeNo) {
    fetchOrderPayInfo(tradeNo)
      .then((res) => {
        // res: { tradeNo, payAmount, totalAmount, payMethods }
        this.setData({ payAmount: res.payAmount || res.totalAmount || 0 });
        this.onOrderReady(res.payMethods || [], res.payAmount || 0);
      })
      .catch((err) => {
        this.setData({
          creating: false,
          failed: true,
          failReason: err.msg || '获取支付信息失败',
        });
      });
  },

  // ========== 轮询 ==========
  startPolling() {
    this._pollAttempt = 0;
    this.setData({ creating: true, failed: false });
    this.poll();
  },

  poll() {
    const { tradeNo } = this.data;

    fetchSubmitResult(tradeNo).then(
      (res) => {
        if (res.status === 'created' || res.status === 'pending') {
          this.onOrderReady(res.payMethods || this.data.payMethods, this.data.payAmount);
        } else if (res.status === 'failed') {
          this.clearPolling();
          this.setData({ creating: false, failed: true, failReason: res.failReason || '订单创建失败' });
        } else {
          this.scheduleNextPoll();
        }
      },
      () => {
        this.scheduleNextPoll();
      },
    );
  },

  scheduleNextPoll() {
    this._pollAttempt += 1;
    if (this._pollAttempt >= MAX_POLL_ATTEMPTS) {
      this.clearPolling();
      this.setData({ creating: false, failed: true, failReason: '订单创建超时，请在订单列表查看' });
      return;
    }
    this._pollTimer = setTimeout(() => this.poll(), POLL_INTERVAL);
  },

  clearPolling() {
    if (this._pollTimer) {
      clearTimeout(this._pollTimer);
      this._pollTimer = null;
    }
  },

  // ========== 订单就绪 ==========
  onOrderReady(methods, payAmount) {
    this.clearPolling();
    const payMethods = methods || [];
    const firstEnabled = payMethods.find((m) => m.enabled);

    this.setData({
      creating: false,
      failed: false,
      payMethods,
      payAmount: payAmount || this.data.payAmount,
      selectedMethod: firstEnabled ? firstEnabled.channel : '',
    });
  },

  // ========== 支付 ==========
  onSelectMethod(e) {
    const { channel } = e.currentTarget.dataset;
    const method = this.data.payMethods.find((m) => m.channel === channel);
    if (!method || !method.enabled) return;
    this.setData({ selectedMethod: channel });
  },

  onPay() {
    const { tradeNo, selectedMethod, paying } = this.data;
    if (paying) return;
    if (!selectedMethod) {
      Toast({ context: this, selector: '#t-toast', message: '请选择支付方式' });
      return;
    }

    this.setData({ paying: true });

    requestOrderPayment({ orderNo: tradeNo, payMethod: selectedMethod })
      .then((res) => {
        if (selectedMethod === 'wechat' && res.payInfo) {
          this.callWechatPay(res.payInfo);
        } else {
          this.onPaySuccess();
        }
      })
      .catch((err) => {
        this.setData({ paying: false });
        Toast({ context: this, selector: '#t-toast', message: err.msg || '支付失败', icon: 'close-circle' });
      });
  },

  callWechatPay(payInfo) {
    const info = typeof payInfo === 'string' ? JSON.parse(payInfo) : payInfo;
    wx.requestPayment({
      ...info,
      success: () => this.onPaySuccess(),
      fail: (err) => {
        this.setData({ paying: false });
        if (err.errMsg && err.errMsg.indexOf('cancel') > -1) {
          Toast({ context: this, selector: '#t-toast', message: '支付取消' });
        } else {
          Toast({ context: this, selector: '#t-toast', message: '支付失败', icon: 'close-circle' });
        }
      },
    });
  },

  onPaySuccess() {
    const { tradeNo, payAmount } = this.data;
    wx.redirectTo({
      url: `/pages/order/pay-result/index?totalPaid=${payAmount}&orderNo=${tradeNo}`,
    });
  },

  goOrderList() {
    wx.redirectTo({ url: '/pages/order/order-list/index' });
  },
});
