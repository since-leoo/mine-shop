import Toast from 'tdesign-miniprogram/toast/index';
import { fetchSettleDetail, dispatchCommitPay } from '../../../services/order/orderConfirm';
import { getAddressPromise } from '../../../services/address/list';

const stripeImg = `https://tdesign.gtimg.com/miniprogram/template/retail/order/stripe.png`;

Page({
  data: {
    placeholder: '备注信息',
    stripeImg,
    loading: false,
    settleDetailData: {
      storeGoodsList: [], //正常下单商品列表
      outOfStockGoodsList: [], //库存不足商品
      abnormalDeliveryGoodsList: [], // 不能正常配送商品
      inValidGoodsList: [], // 失效或者库存不足
      limitGoodsList: [], //限购商品
      couponList: [], //门店优惠券信息
    }, // 获取结算页详情 data
    orderCardList: [], // 仅用于商品卡片展示
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
    storeInfoList: [],
    storeNoteIndex: 0, //当前填写备注门店index
    promotionGoodsList: [], //当前门店商品列表(优惠券)
    couponList: [], //当前门店所选优惠券
    submitCouponList: [], //所有门店所选优惠券
    currentStoreId: '', //当前优惠券storeId
    userAddress: null,
  },

  payLock: false,
  noteInfo: [],
  tempNoteInfo: [],
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

    const storeInfoList = [];
    if (options.userAddressReq) {
      userAddressReq = options.userAddressReq;
    }
    if (options.type === 'cart') {
      const goodsRequestListJson = wx.getStorageSync('order.goodsRequestList');
      goodsRequestList = JSON.parse(goodsRequestListJson);
    } else if (typeof options.goodsRequestList === 'string') {
      goodsRequestList = JSON.parse(options.goodsRequestList);
    }

    const storeMap = {};
    goodsRequestList.forEach((goods) => {
      const storeId = goods.storeId || '1';
      if (!storeMap[storeId]) {
        storeInfoList.push({ storeId, storeName: goods.storeName || '商城' });
        storeMap[storeId] = true;
      }
    });
    this.goodsRequestList = goodsRequestList;
    this.storeInfoList = storeInfoList;

    // 从商品列表中提取订单类型和活动参数（秒杀/拼团从商品详情页带过来）
    const firstGoods = goodsRequestList[0] || {};
    const orderType = firstGoods.orderType || this._orderType || 'normal';
    const activityId = firstGoods.activityId || this._activityId || null;
    const sessionId = firstGoods.sessionId || this._sessionId || null;
    const groupBuyId = firstGoods.groupBuyId || this._groupBuyId || null;
    const groupNo = firstGoods.groupNo || this._groupNo || null;

    // 缓存到实例上，刷新预览时保留
    this._orderType = orderType;
    this._activityId = activityId;
    this._sessionId = sessionId;
    this._groupBuyId = groupBuyId;
    this._groupNo = groupNo;

    // 优惠券：后端接受单个 coupon_id，取第一个
    let couponId = null;
    if (couponList && couponList.length > 0) {
      const first = couponList[0];
      couponId = first.couponId || first.id || first;
    }

    const params = {
      goodsRequestList: goodsRequestList.map((g) => ({
        skuId: g.skuId,
        quantity: g.quantity,
      })),
      storeInfoList,
      userAddress: userAddressReq,
      addressId: userAddressReq?.id || null,
      couponId,
      orderType,
      activityId,
      sessionId,
      groupBuyId,
      groupNo,
    };

    fetchSettleDetail(params).then(
      (res) => {
        this.setData({ loading: false });
        // 后端返回的数据可能是 res.data 或直接 res
        const resData = res?.data || res;
        this.initData(resData);
      },
      () => { this.handleError(); },
    );
  },
  initData(resData) {
    // 后端返回的结构与前端模板期望的字段不同，这里做映射
    const price = resData.price || {};

    // 地址字段映射：后端 province/city/district → 前端 provinceName/cityName/districtName
    let userAddress = resData.userAddress;
    if (userAddress) {
      userAddress = {
        ...userAddress,
        provinceName: userAddress.provinceName || userAddress.province || '',
        cityName: userAddress.cityName || userAddress.city || '',
        districtName: userAddress.districtName || userAddress.district || '',
        detailAddress: userAddress.detailAddress || userAddress.detail || userAddress.fullAddress || '',
      };
    }

    const mapped = {
      settleType: resData.settleType,
      userAddress: userAddress,
      invoiceSupport: resData.invoiceSupport,
      totalSalePrice: price.goodsAmount || 0,
      totalDeliveryFee: price.shippingFee || 0,
      totalPromotionAmount: price.discountAmount || 0,
      totalCouponAmount: resData.couponAmount || 0,
      totalAmount: price.totalAmount || 0,
      totalPayAmount: price.payAmount || 0,
      totalGoodsCount: resData.goodsCount || 0,
      // 将 items 包装成 storeGoodsList 结构
      storeGoodsList: [{
        storeId: '1',
        storeName: resData.storeName || '商城',
        storeTotalPayAmount: price.payAmount || 0,
        couponList: [],
        skuDetailVos: (resData.items || []).map((item) => ({
          skuId: item.skuId,
          spuId: item.productId,
          storeId: '1',
          image: item.productImage,
          goodsName: item.productName,
          skuSpecLst: Array.isArray(item.specValues)
            ? item.specValues.map((sv) => ({
                specValue: typeof sv === 'string' ? sv : (sv.valueName || sv.specValue || ''),
              }))
            : [],
          settlePrice: item.unitPrice,
          tagPrice: null,
          tagText: null,
          quantity: item.quantity,
        })),
      }],
      // 这些列表后端暂未返回，给空数组兜底
      outOfStockGoodsList: resData.outOfStockGoodsList || [],
      abnormalDeliveryGoodsList: resData.abnormalDeliveryGoodsList || [],
      inValidGoodsList: resData.inValidGoodsList || [],
      limitGoodsList: resData.limitGoodsList || [],
    };

    this.userAddressReq = mapped.userAddress;
    if (mapped.userAddress) {
      this.setData({ userAddress: mapped.userAddress });
    }

    const data = this.handleResToGoodsCard(mapped);
    this.setData({ settleDetailData: data });
    this.isInvalidOrder(data);
  },

  isInvalidOrder(data) {
    // 失效 不在配送范围 限购的商品 提示弹窗
    if (
      (data.limitGoodsList && data.limitGoodsList.length > 0) ||
      (data.abnormalDeliveryGoodsList && data.abnormalDeliveryGoodsList.length > 0) ||
      (data.inValidGoodsList && data.inValidGoodsList.length > 0)
    ) {
      this.setData({ popupShow: true });
      return true;
    }
    this.setData({ popupShow: false });
    if (data.settleType === 0) {
      return true;
    }
    return false;
  },

  handleError() {
    Toast({
      context: this,
      selector: '#t-toast',
      message: '结算异常, 请稍后重试',
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
  getRequestGoodsList(storeGoodsList) {
    const filterStoreGoodsList = [];
    storeGoodsList &&
      storeGoodsList.forEach((store) => {
        const { storeName } = store;
        store.skuDetailVos &&
          store.skuDetailVos.forEach((goods) => {
            const data = goods;
            data.storeName = storeName;
            filterStoreGoodsList.push(data);
          });
      });
    return filterStoreGoodsList;
  },
  handleGoodsRequest(goods, isOutStock = false) {
    const { reminderStock, quantity, storeId, uid, saasId, spuId, goodsName, skuId, storeName, roomId } = goods;
    const resQuantity = isOutStock ? reminderStock : quantity;
    return {
      quantity: resQuantity,
      storeId,
      uid,
      saasId,
      spuId,
      goodsName,
      skuId,
      storeName,
      roomId,
    };
  },
  handleResToGoodsCard(data) {
    // 转换数据 符合 goods-card展示
    const orderCardList = []; // 订单卡片列表
    const storeInfoList = [];
    const submitCouponList = []; //使用优惠券列表;

    data.storeGoodsList &&
      data.storeGoodsList.forEach((ele) => {
        const orderCard = {
          id: ele.storeId,
          storeName: ele.storeName,
          status: 0,
          statusDesc: '',
          amount: ele.storeTotalPayAmount,
          goodsList: [],
        }; // 订单卡片
        ele.skuDetailVos.forEach((item, index) => {
          orderCard.goodsList.push({
            id: index,
            thumb: item.image,
            title: item.goodsName,
            specs: item.skuSpecLst.map((s) => s.specValue), // 规格列表 string[]
            price: item.tagPrice || item.settlePrice || '0', // 优先取限时活动价
            settlePrice: item.settlePrice,
            titlePrefixTags: item.tagText ? [{ text: item.tagText }] : [],
            num: item.quantity,
            skuId: item.skuId,
            spuId: item.spuId,
            storeId: item.storeId,
          });
        });

        storeInfoList.push({
          storeId: ele.storeId,
          storeName: ele.storeName,
          remark: '',
        });
        submitCouponList.push({
          storeId: ele.storeId,
          couponList: ele.couponList || [],
        });
        this.noteInfo.push('');
        this.tempNoteInfo.push('');
        orderCardList.push(orderCard);
      });

    this.setData({ orderCardList, storeInfoList, submitCouponList });
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
  onNotes(e) {
    const { storenoteindex: storeNoteIndex } = e.currentTarget.dataset;
    // 添加备注信息
    this.setData({
      dialogShow: true,
      storeNoteIndex,
    });
  },
  onInput(e) {
    const { storeNoteIndex } = this.data;
    this.noteInfo[storeNoteIndex] = e.detail.value;
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
    const { storeInfoList, storeNoteIndex } = this.data;
    this.tempNoteInfo[storeNoteIndex] = this.noteInfo[storeNoteIndex];
    storeInfoList[storeNoteIndex].remark = this.noteInfo[storeNoteIndex];

    this.setData({
      dialogShow: false,
      storeInfoList,
    });
  },
  onNoteCancel() {
    // 备注信息 取消按钮
    const { storeNoteIndex } = this.data;
    this.noteInfo[storeNoteIndex] = this.tempNoteInfo[storeNoteIndex];
    this.setData({
      dialogShow: false,
    });
  },

  onSureCommit() {
    // 商品库存不足继续结算
    const { settleDetailData } = this.data;
    const { outOfStockGoodsList, storeGoodsList, inValidGoodsList } = settleDetailData;
    if ((outOfStockGoodsList && outOfStockGoodsList.length > 0) || (inValidGoodsList && storeGoodsList)) {
      // 合并正常商品 和 库存 不足商品继续支付
      // 过滤不必要的参数
      const filterOutGoodsList = [];
      outOfStockGoodsList &&
        outOfStockGoodsList.forEach((outOfStockGoods) => {
          const { storeName } = outOfStockGoods;
          outOfStockGoods.unSettlementGoods.forEach((ele) => {
            const data = ele;
            data.quantity = ele.reminderStock;
            data.storeName = storeName;
            filterOutGoodsList.push(data);
          });
        });
      const filterStoreGoodsList = this.getRequestGoodsList(storeGoodsList);
      const goodsRequestList = filterOutGoodsList.concat(filterStoreGoodsList);
      this.handleOptionsParams({ goodsRequestList });
    }
  },
  // 提交订单
  submitOrder() {
    const { settleDetailData, invoiceData, storeInfoList, submitCouponList } = this.data;
    const { goodsRequestList } = this;
    const userAddress = settleDetailData.userAddress || this.userAddressReq;

    if (!userAddress) {
      Toast({ context: this, selector: '#t-toast', message: '请添加收货地址', duration: 2000, icon: 'help-circle' });
      return;
    }
    if (this.payLock || !settleDetailData.settleType || !settleDetailData.totalAmount) {
      return;
    }
    this.payLock = true;

    const resSubmitCouponList = this.handleCouponList(submitCouponList);
    let couponId = null;
    if (resSubmitCouponList && resSubmitCouponList.length > 0) {
      const first = resSubmitCouponList[0];
      couponId = first.couponId || first.id || first;
    }

    const params = {
      goodsRequestList: goodsRequestList.map((g) => ({ skuId: g.skuId, quantity: g.quantity })),
      userAddress,
      addressId: userAddress.id || null,
      userName: userAddress.name || '',
      totalAmount: settleDetailData.totalPayAmount,
      orderType: this._orderType || 'normal',
      activityId: this._activityId || null,
      sessionId: this._sessionId || null,
      groupBuyId: this._groupBuyId || null,
      groupNo: this._groupNo || null,
      storeInfoList,
      couponId,
      invoiceRequest: null,
    };
    if (invoiceData && invoiceData.email) {
      params.invoiceRequest = invoiceData;
    }

    dispatchCommitPay(params).then(
      (res) => {
        this.payLock = false;
        const tradeNo = res.tradeNo;
        if (!tradeNo) {
          Toast({ context: this, selector: '#t-toast', message: '订单提交失败', icon: 'close-circle' });
          return;
        }
        // 立刻跳转收银台，用 redirectTo 不可后退
        const payAmount = settleDetailData.totalPayAmount || 0;
        const methodsStr = encodeURIComponent(JSON.stringify(res.payMethods || []));
        const status = res.status || 'processing';
        wx.redirectTo({
          url: `/pages/order/cashier/index?tradeNo=${tradeNo}&payAmount=${payAmount}&payMethods=${methodsStr}&status=${status}`,
        });
      },
      (err) => {
        this.payLock = false;
        Toast({ context: this, selector: '#t-toast', message: err.msg || '提交订单失败', duration: 2000, icon: 'close-circle' });
      },
    );
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
    const { submitCouponList, currentStoreId } = this.data;
    const { goodsRequestList } = this;
    const { selectedList } = e.detail;
    const tempSubmitCouponList = submitCouponList.map((storeCoupon) => {
      return {
        storeId: storeCoupon.storeId,
        couponList: storeCoupon.storeId === currentStoreId ? selectedList : storeCoupon.couponList,
      };
    });
    const resSubmitCouponList = this.handleCouponList(tempSubmitCouponList);
    //确定选择优惠券，重新预览
    this.handleOptionsParams({ goodsRequestList }, resSubmitCouponList);
    this.setData({ couponsShow: false });
  },
  onOpenCoupons(e) {
    const { storeid } = e.currentTarget.dataset;
    this.setData({
      couponsShow: true,
      currentStoreId: storeid,
    });
  },

  handleCouponList(storeCouponList) {
    //处理门店优惠券   转换成接口需要
    if (!storeCouponList) return [];
    const resSubmitCouponList = [];
    storeCouponList.forEach((ele) => {
      resSubmitCouponList.push(...ele.couponList);
    });
    return resSubmitCouponList;
  },

  onGoodsNumChange(e) {
    const {
      detail: { value },
      currentTarget: {
        dataset: { goods },
      },
    } = e;
    const index = this.goodsRequestList.findIndex(
      ({ storeId, spuId, skuId }) => goods.storeId === storeId && goods.spuId === spuId && goods.skuId === skuId,
    );
    if (index >= 0) {
      // eslint-disable-next-line no-confusing-arrow
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
