import Toast from 'tdesign-miniprogram/toast/index';
import { fetchSettleDetail, requestOrderPayment } from '../../../services/order/orderConfirm';
import { commitPay, wechatPayOrder, paySuccess } from './pay';
import { getAddressPromise } from '../../../services/address/list';

const stripeImg = `https://tdesign.gtimg.com/miniprogram/template/retail/order/stripe.png`;

Page({
  data: {
    placeholder: '备注信息',
    stripeImg,
    loading: false,
    settleDetailData: {
      items: [],
      price: { goodsAmount: 0, discountAmount: 0, shippingFee: 0, totalAmount: 0, payAmount: 0 },
      couponAmount: 0,
      storeName: '',
    }, // 获取结算页详情 data
    goodsCardList: [], // 仅用于商品卡片展示
    couponsShow: false, // 显示优惠券的弹框
    invoiceData: {
      email: '', // 发票发送邮箱
      buyerTaxNo: '', // 税号
      invoiceType: null, // 开票类型  1：增值税专用发票； 2：增值税普通发票； 3：增值税电子发票；4：增值税卷式发票；5：区块链电子发票。
      buyerPhone: '', //手机号
      buyerName: '', //个人或公司名称
      titleType: '', // 发票抬头 1-公司 2-个人
      contentType: '', //发票内容 1-明细 2-类别
    },
    goodsRequestList: [],
    userAddressReq: null,
    popupShow: false, // 不在配送范围 失效 库存不足 商品展示弹框
    notesPosition: 'center',
    remark: '',
    submitCouponList: [],
    selectedCouponIds: [],
    userAddress: null,
    payMethods: [],
    showPaySheet: false,
    currentPayOrder: null,
  },

  payLock: false,
  tempRemark: '',
  onLoad(options) {
    this.setData({
      loading: true,
    });
    this.handleOptionsParams(options);
  },
  onShow() {
    const invoiceData = wx.getStorageSync('invoiceData');
    if (invoiceData) {
      //处理发票
      this.invoiceData = invoiceData;
      this.setData({
        invoiceData,
      });
      wx.removeStorageSync('invoiceData');
    }
  },

  init() {
    this.setData({
      loading: true,
    });
    const { goodsRequestList } = this;
    this.handleOptionsParams({ goodsRequestList });
  },
  // 处理不同情况下跳转到结算页时需要的参数
  handleOptionsParams(options, couponList) {
    let { goodsRequestList } = this;
    let { userAddressReq } = this;

    if (options.userAddressReq) {
      userAddressReq = options.userAddressReq;
    }
    if (options.type === 'cart') {
      const goodsRequestListJson = wx.getStorageSync('order.goodsRequestList');
      goodsRequestList = JSON.parse(goodsRequestListJson);
    } else if (typeof options.goodsRequestList === 'string') {
      goodsRequestList = this.safeParseGoodsList(options.goodsRequestList);
    }

    this.goodsRequestList = goodsRequestList;
    this.userAddressReq = userAddressReq || null;
    this.setData({ userAddressReq: this.userAddressReq });
    const params = {
      goodsRequestList,
      storeInfoList: [],
      userAddressReq,
      couponList,
    };
    fetchSettleDetail(params).then(
      (res) => {
        this.setData({
          loading: false,
        });
        this.initData(res.data);
      },
      (error) => {
        // 接口异常处理
        this.handleError(error);
      },
    );
  },
  safeParseGoodsList(goodsRequestListStr) {
    if (!goodsRequestListStr) {
      return [];
    }
    try {
      return JSON.parse(goodsRequestListStr);
    } catch (error) {
      try {
        return JSON.parse(decodeURIComponent(goodsRequestListStr));
      } catch (innerError) {
        console.warn('parse goodsRequestList failed', error, innerError);
        return [];
      }
    }
  },
  initData(resData) {
    // 转换商品卡片显示数据
    const data = this.handleResToGoodsCard(resData);
    this.userAddressReq = resData.userAddress || null;
    this.setData({ userAddressReq: this.userAddressReq });

    if (resData.userAddress) {
      this.setData({ userAddress: resData.userAddress });
    }
    this.setData({ settleDetailData: data });
    this.isInvalidOrder(data);
  },

  isInvalidOrder(data) {
    if (data.settleType === 0) {
      return true;
    }
    return false;
  },

  handleError(error) {
    Toast({
      context: this,
      selector: '#t-toast',
      message: (error && (error.msg || error.message)) || '结算异常, 请稍后重试',
      duration: 2000,
      icon: '',
    });

    setTimeout(() => {
      wx.navigateBack();
    }, 1500);
    this.setData({
      loading: false,
    });
  },
  handleResToGoodsCard(data) {
    const goodsCardList = (data.items || []).map((item, index) => ({
      id: index,
      thumb: item.productImage,
      title: item.productName,
      specs: Array.isArray(item.specValues) ? item.specValues.map((s) => s.value || s) : [],
      price: item.unitPrice || 0,
      num: item.quantity,
      skuId: item.skuId,
      spuId: item.productId,
    }));

    this.setData({ goodsCardList });
    return data;
  },
  onGotoAddress() {
    /** 获取一个Promise */
    getAddressPromise()
      .then((address) => {
        this.handleOptionsParams({
          userAddressReq: { ...address, checked: true },
        });
      })
      .catch(() => {});

    const { userAddressReq } = this; // 收货地址

    let id = '';

    if (userAddressReq?.id) {
      id = `&id=${userAddressReq.id}`;
    }

    wx.navigateTo({
      url: `/pages/user/address/list/index?selectMode=1&isOrderSure=1${id}`,
    });
  },
  onNotes() {
    // 添加备注信息
    this.setData({
      dialogShow: true,
    });
  },
  onInput(e) {
    this.tempRemark = e.detail.value;
  },
  onBlur() {
    this.setData({
      notesPosition: 'center',
    });
  },
  onFocus() {
    this.setData({
      notesPosition: 'self',
    });
  },
  onTap() {
    this.setData({
      placeholder: '',
    });
  },
  onNoteConfirm() {
    // 备注信息 确认按钮
    const remark = this.tempRemark ?? this.data.remark;
    this.setData({
      dialogShow: false,
      remark,
    });
  },
  onNoteCancel() {
    // 备注信息 取消按钮
    this.tempRemark = this.data.remark;
    this.setData({
      dialogShow: false,
    });
  },

  onSureCommit() {
    // 库存不足时重新结算，使用当前商品列表刷新
    this.handleOptionsParams({ goodsRequestList: this.goodsRequestList });
  },
  // 提交订单
  submitOrder() {
    const { settleDetailData, invoiceData, remark, submitCouponList } = this.data;
    const { goodsRequestList } = this;

    const addressPayload = settleDetailData.userAddress || this.data.userAddressReq || this.userAddressReq;

    if (!addressPayload) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '请添加收货地址',
        duration: 2000,
        icon: 'help-circle',
      });

      return;
    }
    const { price } = settleDetailData;
    if (this.payLock || !settleDetailData.settleType || !price.goodsAmount) {
      return;
    }
    this.payLock = true;
    const params = {
      userAddressReq: addressPayload,
      goodsRequestList: goodsRequestList,
      userName: addressPayload && addressPayload.name ? addressPayload.name : '',
      totalAmount: price.payAmount,
      invoiceRequest: null,
      storeInfoList: remark ? [{ remark }] : [],
      couponList: submitCouponList || [],
    };
    if (invoiceData && invoiceData.email) {
      params.invoiceRequest = invoiceData;
    }
    commitPay(params).then(
      (res) => {
        this.payLock = false;
        const { data } = res;
        // 提交出现 失效 不在配送范围 限购的商品 提示弹窗
        if (this.isInvalidOrder(data)) {
          return;
        }
        if (res.code === 'Success') {
          this.handlePay(data, settleDetailData);
        } else {
          Toast({
            context: this,
            selector: '#t-toast',
            message: res.msg || '提交订单超时，请稍后重试',
            duration: 2000,
            icon: '',
          });
          setTimeout(() => {
            // 提交支付失败   返回购物车
            wx.navigateBack();
          }, 2000);
        }
      },
      (err) => {
        this.payLock = false;
        if (err.code === 'CONTAINS_INSUFFICIENT_GOODS' || err.code === 'TOTAL_AMOUNT_DIFFERENT') {
          Toast({
            context: this,
            selector: '#t-toast',
            message: err.msg || '支付异常',
            duration: 2000,
            icon: '',
          });
          this.init();
        } else if (err.code === 'ORDER_PAY_FAIL') {
          Toast({
            context: this,
            selector: '#t-toast',
            message: '支付失败',
            duration: 2000,
            icon: 'close-circle',
          });
          setTimeout(() => {
            wx.redirectTo({ url: '/pages/order/order-list/index' });
          });
        } else if (err.code === 'ILLEGAL_CONFIG_PARAM') {
          Toast({
            context: this,
            selector: '#t-toast',
            message: '支付失败，微信支付商户号设置有误，请商家重新检查支付设置。',
            duration: 2000,
            icon: 'close-circle',
          });
          setTimeout(() => {
            wx.redirectTo({ url: '/pages/order/order-list/index' });
          });
        } else {
          Toast({
            context: this,
            selector: '#t-toast',
            message: err.msg || '提交支付超时，请稍后重试',
            duration: 2000,
            icon: '',
          });
          setTimeout(() => {
            // 提交支付失败  返回购物车
            wx.navigateBack();
          }, 2000);
        }
      },
    );
  },

  // 处理支付
  handlePay(data, settleDetailData) {
    const { tradeNo, payMethods = [], payAmount } = data;
    const { price } = settleDetailData;
    const normalizedPayMethods = Array.isArray(payMethods)
      ? payMethods.filter((method) => method && method.enabled !== false)
      : [];

    const payOrderInfo = {
      orderId: tradeNo,
      tradeNo,
      orderAmt: price.goodsAmount,
      payAmt: payAmount || price.payAmount,
    };

    this.setData({
      payMethods: normalizedPayMethods,
      currentPayOrder: payOrderInfo,
      showPaySheet: normalizedPayMethods.length > 0,
    });

    if (normalizedPayMethods.length === 0) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '暂无可用支付方式，请稍后再试',
        duration: 2000,
      });
    }
  },

  hide() {
    // 隐藏 popup
    this.setData({
      'settleDetailData.abnormalDeliveryGoodsList': [],
    });
  },
  onReceipt() {
    // 跳转 开发票
    const invoiceData = this.invoiceData || {};
    wx.navigateTo({
      url: `/pages/order/receipt/index?invoiceData=${JSON.stringify(invoiceData)}`,
    });
  },

  onCoupons(e) {
    const { goodsRequestList } = this;
    const { selectedList } = e.detail;
    // selectedList 是 [{couponId: xxx}, ...] 格式
    this.setData({ submitCouponList: selectedList || [] });
    // 确定选择优惠券，重新请求 preview
    this.handleOptionsParams({ goodsRequestList }, selectedList);
    this.setData({ couponsShow: false });
  },
  onOpenCoupons() {
    const { submitCouponList } = this.data;
    const selectedCouponIds = (submitCouponList || []).map((c) => String(c.couponId || c.coupon_id || ''));
    this.setData({
      couponsShow: true,
      selectedCouponIds,
    });
  },

  handleCouponList(couponList) {
    if (!couponList) return [];
    return couponList;
  },

  onSelectPayMethod(e) {
    const { index } = e.currentTarget?.dataset || {};
    const { currentPayOrder, payMethods } = this.data;
    if (index === undefined || !currentPayOrder) {
      this.setData({ showPaySheet: false });
      return;
    }
    const methodIndex = Number(index);
    const method = payMethods[methodIndex];
    if (!method) {
      this.setData({ showPaySheet: false });
      return;
    }
    const { channel, name } = method;
    const displayName = name || channel || '该支付方式';

    wx.showLoading({ title: '请求支付', mask: true });
    requestOrderPayment({
      orderNo: currentPayOrder.tradeNo,
      payMethod: channel,
    })
      .then((resp) => {
        if (channel === 'wechat') {
          if (!resp.payInfo) {
            Toast({
              context: this,
              selector: '#t-toast',
              message: '支付参数为空，请稍后再试',
              theme: 'warning',
              duration: 1800,
            });
            return;
          }
          this.setData({ showPaySheet: false });
          wechatPayOrder(this, {
            ...currentPayOrder,
            payInfo: resp.payInfo,
          });
          return;
        }

        if (channel === 'balance' && resp.isPaid) {
          this.setData({ showPaySheet: false });
          paySuccess(this, {
            ...currentPayOrder,
            payAmt: resp.payAmount || currentPayOrder.payAmt,
          });
          return;
        }

        Toast({
          context: this,
          selector: '#t-toast',
          message: `暂未接入${displayName}支付，请稍后重试`,
          theme: 'warning',
          duration: 1800,
        });
      })
      .catch((err) => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: err?.msg || err?.message || '支付失败，请稍后重试',
          theme: 'warning',
          duration: 2000,
        });
      })
      .finally(() => {
        wx.hideLoading();
      });
  },

  closePaySheet() {
    this.setData({ showPaySheet: false });
  },

  onGoodsNumChange(e) {
    const {
      detail: { value },
      currentTarget: {
        dataset: { goods },
      },
    } = e;
    const index = this.goodsRequestList.findIndex(
      ({ skuId }) => goods.skuId === skuId,
    );
    if (index >= 0) {
      const goodsRequestList = this.goodsRequestList.map((item, i) =>
        i === index ? { ...item, quantity: value } : item,
      );
      this.handleOptionsParams({ goodsRequestList });
    }
  },

  onPopupChange() {
    this.setData({
      popupShow: !this.data.popupShow,
    });
  },
});
