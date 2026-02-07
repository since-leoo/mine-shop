import Toast from 'tdesign-miniprogram/toast/index';
import { fetchPromotion } from '../../../services/promotion/detail';

Page({
  data: {
    list: [],
    banner: '',
    time: 0,
    showBannerDesc: false,
    statusTag: '',
  },

  onLoad(query) {
    const promotionID = parseInt(query.promotion_id);
    this.getGoodsList(promotionID);
  },

  getGoodsList(promotionID) {
    fetchPromotion(promotionID)
      .then((res) => {
        if (!res || typeof res !== 'object') return;
        const { list, banner, time, showBannerDesc, statusTag } = res;
        const goods = (Array.isArray(list) ? list : []).map((item) => ({
          ...item,
          tags: Array.isArray(item.tags) ? item.tags.map((v) => v.title) : [],
        }));
        this.setData({
          list: goods,
          banner: banner || '',
          time: time || 0,
          showBannerDesc: !!showBannerDesc,
          statusTag: statusTag || '',
        });
      })
      .catch((err) => {
        console.error('fetchPromotion error:', err);
      });
  },

  goodClickHandle(e) {
    const { index } = e.detail;
    const { spuId } = this.data.list[index];
    wx.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  },

  cardClickHandle() {
    Toast({
      context: this,
      selector: '#t-toast',
      message: '点击加购',
    });
  },

  bannerClickHandle() {
    Toast({
      context: this,
      selector: '#t-toast',
      message: '点击规则详情',
    });
  },
});
