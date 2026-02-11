import { fetchCouponList } from '../../../services/coupon/index';

// ui-coupon-card 组件 CouponType 枚举: MJ_COUPON=1(满减/price), ZK_COUPON=2(折扣/discount)
const CouponTypeMap = { price: 1, discount: 2 };

Page({
  data: {
    pullDownRefreshing: false,
    status: 0,
    list: [
      { text: '可使用', key: 0 },
      { text: '已使用', key: 1 },
      { text: '已失效', key: 2 },
    ],
    couponList: [],
  },

  onLoad() {
    this.init();
  },

  init() {
    this.fetchList();
  },

  fetchList(status = this.data.status) {
    const statusMap = { 0: 'default', 1: 'useless', 2: 'disabled' };
    const statusInFetch = statusMap[Number(status)] || 'default';
    return fetchCouponList(statusInFetch).then((couponList) => {
      const mapped = (couponList || []).map((item) => ({
        ...item,
        type: CouponTypeMap[item.type] || 1,
      }));
      this.setData({ couponList: mapped });
    });
  },

  tabChange(e) {
    const { value } = e.detail;
    this.setData({ status: value });
    this.fetchList(value);
  },

  goCouponCenterHandle() {
    wx.navigateTo({ url: '/pages/coupon/coupon-center/index' });
  },

  onPullDownRefresh_() {
    this.setData({ couponList: [], pullDownRefreshing: true });
    this.fetchList()
      .then(() => this.setData({ pullDownRefreshing: false }))
      .catch(() => this.setData({ pullDownRefreshing: false }));
  },
});
