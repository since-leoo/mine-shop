import { fetchGroupBuyList } from '../../../services/promotion/groupBuy';

function fmtPrice(cents) {
  const yuan = (cents / 100).toFixed(2);
  const [int, dec] = yuan.split('.');
  return { int, dec: dec === '00' ? '' : dec };
}

Page({
  data: {
    loading: true,
    list: [],
    statusTag: '',
    countdown: null,
  },

  _endTime: 0,
  _timer: null,

  onLoad() {
    this.loadData();
  },

  onUnload() {
    this.clearTimer();
  },

  onPullDownRefresh() {
    this.loadData();
    wx.stopPullDownRefresh();
  },

  loadData() {
    this.setData({ loading: true });
    fetchGroupBuyList(50)
      .then((res) => {
        const list = (res.list || []).map((item) => {
          const p = fmtPrice(item.price || 0);
          const op = ((item.originPrice || 0) / 100).toFixed(2);
          const sold = item.soldQuantity || 0;
          return {
            spuId: item.spuId,
            activityId: item.activityId,
            thumb: item.thumb || '',
            title: item.title || '',
            price: item.price || 0,
            priceInt: p.int,
            priceDec: p.dec,
            originPrice: item.originPrice || 0,
            originPriceFmt: op,
            tags: item.tags || [],
            soldText: sold > 9999 ? (sold / 10000).toFixed(1) + '万' : String(sold),
          };
        });
        this._endTime = Date.now() + (res.time || 0);
        this.setData({ list, statusTag: res.statusTag || '', loading: false });
        if (res.time > 0) this.startTimer();
      })
      .catch(() => {
        this.setData({ list: [], loading: false });
      });
  },

  startTimer() {
    this.clearTimer();
    this.tick();
    this._timer = setInterval(() => this.tick(), 1000);
  },

  tick() {
    const diff = Math.max(0, Math.floor((this._endTime - Date.now()) / 1000));
    if (diff <= 0) {
      this.clearTimer();
      this.setData({ countdown: null });
      return;
    }
    const days = Math.floor(diff / 86400);
    const h = String(Math.floor((diff % 86400) / 3600)).padStart(2, '0');
    const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    const s = String(diff % 60).padStart(2, '0');
    this.setData({ countdown: { d: days, h, m, s } });
  },

  clearTimer() {
    if (this._timer) {
      clearInterval(this._timer);
      this._timer = null;
    }
  },

  onItemTap(e) {
    const { spuId, activityId } = e.currentTarget.dataset;
    if (spuId) {
      wx.navigateTo({
        url: `/pages/goods/details/index?spuId=${spuId}&orderType=group_buy&groupBuyId=${activityId || ''}`,
      });
    }
  },
});
