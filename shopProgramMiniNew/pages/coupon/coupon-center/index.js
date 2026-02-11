import Toast from 'tdesign-miniprogram/toast/index';
import { fetchAvailableCoupons, receiveCoupon } from '../../../services/coupon/index';

// ui-coupon-card 组件内部 CouponType 枚举
const CouponTypeMap = { price: 1, discount: 2 };

Page({
  data: {
    loading: true,
    couponList: [],
  },

  onLoad() {
    this.loadList();
  },

  loadList() {
    this.setData({ loading: true });
    fetchAvailableCoupons()
      .then((list) => {
        // 后端 basePayload 经 toCamelCase 后的字段 → 映射为 ui-coupon-card 需要的格式
        const couponList = (list || []).map((item) => ({
          id: item.couponId,
          title: item.name || '',
          type: CouponTypeMap[item.type] || 1,
          value: item.discountValue || 0,
          tag: item.tag || '',
          desc: item.label || '',
          timeLimit: this.buildTimeLimit(item.startTime, item.endTime),
          currency: '¥',
          status: 'default',
          isReceivable: item.isReceivable !== false,
          availableQuantity: item.availableQuantity || 0,
        }));
        this.setData({ couponList, loading: false });
      })
      .catch(() => {
        this.setData({ couponList: [], loading: false });
        Toast({ context: this, selector: '#t-toast', message: '加载失败，请重试' });
      });
  },

  buildTimeLimit(startTime, endTime) {
    if (!startTime || !endTime) return '';
    const fmt = (t) => (t || '').substring(0, 10).replace(/-/g, '.');
    return fmt(startTime) + '-' + fmt(endTime);
  },

  onReceive(e) {
    const { id, index } = e.currentTarget.dataset;
    if (!id) return;
    receiveCoupon(id)
      .then(() => {
        Toast({ context: this, selector: '#t-toast', message: '领取成功', icon: 'check-circle' });
        // 更新当前项状态
        this.setData({ [`couponList[${index}].isReceivable`]: false });
      })
      .catch((err) => {
        Toast({ context: this, selector: '#t-toast', message: err.msg || '领取失败' });
      });
  },

  onPullDownRefresh() {
    this.loadList();
    wx.stopPullDownRefresh();
  },
});
