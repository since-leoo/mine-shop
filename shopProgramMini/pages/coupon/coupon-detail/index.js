import { fetchCouponDetail } from '../../../services/coupon/index';

Page({
  data: {
    detail: null,
    storeInfoList: [],
    storeInfoStr: '',
    showStoreInfoList: false,
  },

  id: '',

  onLoad(query) {
    const id = parseInt(query.id);
    this.id = id;
    this.getGoodsList(id);
  },

  getGoodsList(id) {
    fetchCouponDetail(id)
      .then((res) => {
        const detail = res && res.detail ? res.detail : res;

        // 转换 type 字段：API 返回的是字符串，组件需要数字
        if (detail) {
          const CouponType = {
            MJ_COUPON: 1,
            ZK_COUPON: 2,
            MJF_COUPON: 3,
            GIFT_COUPON: 4,
          };

          // 将字符串类型转换为数字类型
          if (detail.type === 'price') {
            detail.type = CouponType.MJ_COUPON;
          } else if (detail.type === 'discount' || detail.type === 'percent') {
            detail.type = CouponType.ZK_COUPON;
          }
        }

        this.setData({
          detail,
        });
      })
      .catch((err) => {
        console.error('fetchCouponDetail error:', err);
      });
  },

  navGoodListHandle() {
    wx.navigateTo({
      url: `/pages/coupon/coupon-activity-goods/index?id=${this.id}`,
    });
  },
});
