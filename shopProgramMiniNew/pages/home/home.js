import { fetchHome } from '../../services/home/home';
import { fetchGoodsList, fetchHotGoods } from '../../services/good/fetchGoods';
import { ensureMiniProgramLogin } from '../../common/auth';
import Toast from 'tdesign-miniprogram/toast/index';

Page({
  data: {
    imgSrcs: [],
    tabList: [],
    goodsList: [],
    goodsListLoadStatus: 0,
    pageLoading: false,
    current: 1,
    autoplay: true,
    duration: '500',
    interval: 5000,
    navigation: { type: 'dots' },
    swiperImageProps: { mode: 'scaleToFill' },
    categoryList: [],
    seckillList: [],
    seckillTitle: '限时秒杀',
    seckillCountdown: null,
    hotGoodsList: [],
    quickEntries: [
      { key: 'cart', name: '购物车', icon: 'cart', bg: '#f3e5f5', color: '#8e24aa' },
      { key: 'groupbuy', name: '拼团活动', icon: 'usergroup', bg: '#e0f2f1', color: '#00897b' },
      { key: 'order', name: '我的订单', icon: 'file-paste', bg: '#fff8e1', color: '#f9a825' },
      { key: 'coupon', name: '领券中心', icon: 'discount', bg: '#fbe9e7', color: '#d84315' },
      { key: 'category', name: '全部分类', icon: 'view-module', bg: '#e8eaf6', color: '#3949ab' },
    ],
  },

  goodListPagination: { index: 0, num: 20 },
  privateData: { tabIndex: 0, seckillEndTime: null, countdownTimer: null, seckillActivityId: null, seckillSessionId: null },

  onShow() {
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar && typeof tabBar.init === 'function') tabBar.init();
  },

  onLoad() { this.init(); },
  onUnload() { this.clearCountdown(); },
  onReachBottom() { if (this.data.goodsListLoadStatus === 0) this.loadGoodsList(); },
  onPullDownRefresh() { this.init(); },

  async init() {
    await this.trySilentAuthorize();
    this.loadHomePage();
  },

  async trySilentAuthorize() {
    try { await ensureMiniProgramLogin(); } catch (e) { console.warn('silent authorize failed', e); }
  },

  loadHomePage() {
    wx.stopPullDownRefresh();
    this.setData({ pageLoading: true });
    const that = this;

    fetchHome().then((homeData) => {
      const { swiper, seckillList, seckillEndTime, seckillTitle, seckillActivityId, seckillSessionId, categoryList } = homeData;

      const bannerList = (swiper && swiper.length > 0) ? swiper : [
        'https://tdesign.gtimg.com/miniprogram/template/retail/home/v2/banner2.png'
      ];

      // 按 spuId 去重，避免同一商品出现多次
      const seenSpuIds = new Set();
      const uniqueSeckillList = (seckillList || []).filter((item) => {
        if (seenSpuIds.has(item.spuId)) return false;
        seenSpuIds.add(item.spuId);
        return true;
      });

      that.setData({
        imgSrcs: bannerList,
        seckillList: uniqueSeckillList.slice(0, 6),
        seckillTitle: seckillTitle || '限时秒杀',
        categoryList: (categoryList || []).slice(0, 10),
        pageLoading: false,
      });

      if (!that.privateData) that.privateData = {};
      that.privateData.seckillActivityId = seckillActivityId || null;
      that.privateData.seckillSessionId = seckillSessionId || null;

      if (seckillEndTime) {
        that.privateData.seckillEndTime = new Date(seckillEndTime).getTime();
        that.startCountdown();
      }

      that.loadHotGoods();
      that.loadGoodsList(true);
    });
  },

  async loadHotGoods() {
    try {
      const list = await fetchHotGoods(6);
      this.setData({ hotGoodsList: list });
    } catch (e) { console.warn('loadHotGoods failed', e); }
  },

  onQuickEntryTap(e) {
    const { key } = e.currentTarget.dataset;
    const routes = {
      cart: '/pages/cart/index',
      groupbuy: '/pages/promotion/group-buy/index',
      order: '/pages/order/order-list/index',
      coupon: '/pages/coupon/coupon-list/index',
      category: '/pages/category/index',
    };
    if (routes[key]) {
      if (key === 'cart' || key === 'category') {
        wx.switchTab({ url: routes[key] });
      } else {
        wx.navigateTo({ url: routes[key] });
      }
    }
  },

  // ========== 倒计时 ==========
  startCountdown() {
    this.clearCountdown();
    this.tickCountdown();
    this.privateData.countdownTimer = setInterval(() => this.tickCountdown(), 1000);
  },

  tickCountdown() {
    const endTime = this.privateData.seckillEndTime;
    if (!endTime) return;
    const diff = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
    if (diff <= 0) { this.clearCountdown(); this.setData({ seckillCountdown: null }); return; }
    const days = Math.floor(diff / 86400);
    const hours = String(Math.floor((diff % 86400) / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    const seconds = String(diff % 60).padStart(2, '0');
    this.setData({ seckillCountdown: { days, hours, minutes, seconds } });
  },

  clearCountdown() {
    if (this.privateData.countdownTimer) { clearInterval(this.privateData.countdownTimer); this.privateData.countdownTimer = null; }
  },

  // ========== 商品列表 ==========
  onReTry() { this.loadGoodsList(); },

  async loadGoodsList(fresh = false) {
    if (fresh) wx.pageScrollTo({ scrollTop: 0 });
    this.setData({ goodsListLoadStatus: 1 });
    const pageSize = this.goodListPagination.num;
    let pageIndex = fresh ? 0 : this.goodListPagination.index + 1;
    try {
      const nextList = await fetchGoodsList(pageIndex, pageSize);
      this.setData({
        goodsList: fresh ? nextList : this.data.goodsList.concat(nextList),
        goodsListLoadStatus: nextList.length < pageSize ? 2 : 0,
      });
      this.goodListPagination.index = pageIndex;
    } catch (err) { this.setData({ goodsListLoadStatus: 3 }); }
  },

  // ========== 导航 ==========
  goodListClickHandle(e) {
    const { index } = e.detail;
    const { spuId } = this.data.goodsList[index];
    wx.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  },

  navToGoodsDetail(e) {
    const { spuId } = e.currentTarget.dataset;
    if (spuId) wx.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  },

  navToSeckillGoodsDetail(e) {
    const { spuId } = e.currentTarget.dataset;
    if (!spuId) return;
    const activityId = this.privateData.seckillActivityId || '';
    const sessionId = this.privateData.seckillSessionId || '';
    wx.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}&orderType=seckill&activityId=${activityId}&sessionId=${sessionId}` });
  },

  navToSeckill() {
    wx.navigateTo({ url: '/pages/promotion/promotion-detail/index?promotion_id=seckill' });
  },

  goodListAddCartHandle(e) {
    const { goods } = e.detail;
    if (!goods) return;
    if (!goods.defaultSkuId) {
      wx.navigateTo({ url: `/pages/goods/details/index?spuId=${goods.spuId}` });
      return;
    }
    const { addCartItem } = require('../../services/cart/cart');
    addCartItem({ skuId: Number(goods.defaultSkuId), quantity: 1 })
      .then(() => { Toast({ context: this, selector: '#t-toast', message: '已加入购物车', icon: 'check-circle' }); })
      .catch((err) => { Toast({ context: this, selector: '#t-toast', message: err.msg || '加入购物车失败', icon: 'close-circle' }); });
  },

  navToSearchPage() { wx.navigateTo({ url: '/pages/goods/search/index' }); },

  navToActivityDetail({ detail }) {
    const { index: promotionID = 0 } = detail || {};
    wx.navigateTo({ url: `/pages/promotion/promotion-detail/index?promotion_id=${promotionID}` });
  },

  tabChangeHandle(e) {
    this.privateData.tabIndex = e.detail;
    this.loadGoodsList(true);
  },
});
