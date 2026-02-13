import Toast from 'tdesign-miniprogram/toast/index';
import { request } from '../../../../services/request';

Page({
  data: {
    serviceRateValue: 1,
    goodRateValue: 1,
    conveyRateValue: 1,
    isAnonymous: false,
    uploadFiles: [],
    gridConfig: {
      width: 218,
      height: 218,
      column: 3,
    },
    isAllowedSubmit: false,
    imgUrl: '',
    title: '',
    goodsDetail: '',
    imageProps: {
      mode: 'aspectFit',
    },
    submitting: false,
  },

  // 订单相关参数
  orderId: null,
  orderItemId: null,
  productId: null,
  skuId: null,

  onLoad(options) {
    const { orderId, orderItemId, productId, skuId, productImage, productName, skuName } = options;
    this.orderId = orderId ? Number(orderId) : null;
    this.orderItemId = orderItemId ? Number(orderItemId) : null;
    this.productId = productId ? Number(productId) : null;
    this.skuId = skuId ? Number(skuId) : null;

    this.setData({
      imgUrl: productImage || options.imgUrl || '',
      title: productName || options.title || '',
      goodsDetail: skuName || options.specs || '',
    });
  },

  onRateChange(e) {
    const { value } = e?.detail;
    const item = e?.currentTarget?.dataset?.item;
    this.setData({ [item]: value }, () => {
      this.updateButtonStatus();
    });
  },

  onAnonymousChange(e) {
    const status = !!e?.detail?.checked;
    this.setData({ isAnonymous: status });
  },

  handleSuccess(e) {
    const { files } = e.detail;
    this.setData({ uploadFiles: files });
  },

  handleRemove(e) {
    const { index } = e.detail;
    const { uploadFiles } = this.data;
    uploadFiles.splice(index, 1);
    this.setData({ uploadFiles });
  },

  onTextAreaChange(e) {
    const value = e?.detail?.value;
    this.textAreaValue = value;
    this.updateButtonStatus();
  },

  updateButtonStatus() {
    const { serviceRateValue, goodRateValue, conveyRateValue, isAllowedSubmit } = this.data;
    const { textAreaValue } = this;
    const temp = serviceRateValue && goodRateValue && conveyRateValue && textAreaValue;
    if (temp !== isAllowedSubmit) this.setData({ isAllowedSubmit: temp });
  },

  onSubmitBtnClick() {
    const { isAllowedSubmit, goodRateValue, isAnonymous, uploadFiles, submitting } = this.data;
    if (!isAllowedSubmit || submitting) return;

    const images = uploadFiles
      .filter((file) => file.url)
      .map((file) => file.url);

    const reviewData = {
      rating: goodRateValue,
      content: this.textAreaValue,
      images: images.length > 0 ? images : [],
      isAnonymous: isAnonymous,
      orderId: this.orderId,
      orderItemId: this.orderItemId,
    };

    this.setData({ submitting: true });

    request({
      url: '/api/v1/review',
      method: 'POST',
      data: reviewData,
      needAuth: true,
    })
      .then(() => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '评价提交成功',
          icon: 'check-circle',
        });
        setTimeout(() => {
          wx.navigateBack();
        }, 1500);
      })
      .catch((err) => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: err.msg || '评价提交失败，请重试',
          icon: 'close-circle',
        });
      })
      .finally(() => {
        this.setData({ submitting: false });
      });
  },
});
