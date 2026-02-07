import { fetchUserCenter } from '../../services/usercenter/fetchUsercenter';
import {
  ensureMiniProgramLogin,
  clearAuthStorage,
  getStoredMemberProfile,
} from '../../common/auth';
import Toast from 'tdesign-miniprogram/toast/index';

const toolsData = [
  { title: '收货地址', type: 'address', iconName: 'location' },
  { title: '优惠券', type: 'coupon', iconName: 'gift' },
  { title: '积分', type: 'point', iconName: 'star' },
  { title: '帮助中心', type: 'help-center', iconName: 'help-circle' },
  { title: '客服热线', type: 'service', iconName: 'service' },
];

const orderTagInfos = [
  { title: '待付款', iconName: 'wallet', orderNum: 0, tabType: 5, status: 1 },
  { title: '待发货', iconName: 'deliver', orderNum: 0, tabType: 10, status: 1 },
  { title: '待收货', iconName: 'package', orderNum: 0, tabType: 40, status: 1 },
  { title: '待评价', iconName: 'comment', orderNum: 0, tabType: 60, status: 1 },
  { title: '退款/售后', iconName: 'exchang', orderNum: 0, tabType: 0, status: 1 },
];

const AUTH_STEP = {
  UNLOGIN: 1,
  BASIC: 2,
  FULL: 3,
};

const getDefaultData = () => ({
  showMakePhone: false,
  userInfo: {
    avatarUrl: '',
    nickName: '正在登录...',
    phoneNumber: '',
  },
  toolsData,
  orderTagInfos,
  couponCount: 0,
  customerServiceInfo: {},
  currAuthStep: AUTH_STEP.UNLOGIN,
  showKefu: true,
  versionNo: '',
  authLoading: false,
  pageLoading: false,
});

Page({
  data: getDefaultData(),

  onLoad() {
    this.getVersionInfo();
  },

  onShow() {
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar && typeof tabBar.init === 'function') {
      tabBar.init();
    }
    this.init();
  },

  onPullDownRefresh() {
    this.init();
  },

  async init() {
    this.setData({ pageLoading: true });
    const hasLogin = await this.ensureLoginState();
    if (!hasLogin) {
      this.setData({ pageLoading: false });
      wx.stopPullDownRefresh();
      return;
    }
    this.fetchCenterData();
  },

  async ensureLoginState(force = false) {
    try {
      const storedProfile = getStoredMemberProfile();
      await ensureMiniProgramLogin({ force, openid: storedProfile?.openid || '' });
      this.setData({ currAuthStep: AUTH_STEP.BASIC });
      return true;
    } catch (error) {
      console.warn('mini program login failed', error);
      this.setData({
        currAuthStep: AUTH_STEP.UNLOGIN,
        userInfo: getDefaultData().userInfo,
      });
      return false;
    }
  },

  async fetchCenterData() {
    this.setData({ pageLoading: true });
    try {
      const {
        userInfo = {},
        countsData = [],
        orderTagInfos: orderInfo = [],
        customerServiceInfo = {},
      } = await fetchUserCenter();

      const couponMatch = countsData.find((c) => c.type === 'coupon');
      const couponCount = couponMatch ? couponMatch.num : 0;

      const info = orderTagInfos.map((v, index) => ({
        ...v,
        ...(orderInfo?.[index] || {}),
      }));

      const needUserProfile = !Boolean(userInfo.authorizedProfile);

      this.setData({
        userInfo,
        orderTagInfos: info,
        customerServiceInfo,
        couponCount,
        currAuthStep: needUserProfile ? AUTH_STEP.BASIC : AUTH_STEP.FULL,
        pageLoading: false,
      });

    } catch (error) {
      console.warn('fetch user center failed', error);
      if (error?.code === 401) {
        clearAuthStorage();
        this.setData({ currAuthStep: AUTH_STEP.UNLOGIN });
      } else {
        Toast({
          context: this,
          selector: '#t-toast',
          message: error?.msg || '获取个人中心信息失败',
          theme: 'error',
        });
      }
      this.setData({ pageLoading: false });
    } finally {
      wx.stopPullDownRefresh();
    }
  },

  async handleLoginTap() {
    if (this.data.authLoading) return;
    this.setData({ authLoading: true });
    const success = await this.ensureLoginState(true);
    if (success) {
      await this.fetchCenterData();
    } else {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '登录失败，请稍后重试',
        theme: 'error',
      });
    }
    this.setData({ authLoading: false });
  },

  onClickCell({ currentTarget }) {
    const { type } = currentTarget.dataset;
    switch (type) {
      case 'address':
        wx.navigateTo({ url: '/pages/user/address/list/index' });
        break;
      case 'service':
        this.openMakePhone();
        break;
      case 'help-center':
        Toast({ context: this, selector: '#t-toast', message: '你点击了帮助中心', icon: '', duration: 1000 });
        break;
      case 'point':
        Toast({ context: this, selector: '#t-toast', message: '你点击了积分', icon: '', duration: 1000 });
        break;
      case 'coupon':
        wx.navigateTo({ url: '/pages/coupon/coupon-list/index' });
        break;
      default:
        break;
    }
  },

  onTapPoints() {
    Toast({ context: this, selector: '#t-toast', message: '你点击了积分', icon: '', duration: 1000 });
  },

  onTapBalance() {
    Toast({ context: this, selector: '#t-toast', message: '你点击了余额', icon: '', duration: 1000 });
  },

  onTapCoupon() {
    wx.navigateTo({ url: '/pages/coupon/coupon-list/index' });
  },

  jumpNav(e) {
    const status = e.detail.tabType;
    if (status === 0) {
      wx.navigateTo({ url: '/pages/order/after-service-list/index' });
    } else {
      wx.navigateTo({ url: `/pages/order/order-list/index?status=${status}` });
    }
  },

  jumpAllOrder() {
    wx.navigateTo({ url: '/pages/order/order-list/index' });
  },

  openMakePhone() {
    this.setData({ showMakePhone: true });
  },

  closeMakePhone() {
    this.setData({ showMakePhone: false });
  },

  call() {
    wx.makePhoneCall({ phoneNumber: this.data.customerServiceInfo.servicePhone });
  },

  gotoUserEditPage() {
    const { currAuthStep } = this.data;
    if (currAuthStep === AUTH_STEP.UNLOGIN) {
      this.handleLoginTap();
      return;
    }
    wx.navigateTo({ url: '/pages/user/person-info/index' });
  },

  getVersionInfo() {
    const versionInfo = wx.getAccountInfoSync();
    const { version, envVersion = __wxConfig } = versionInfo.miniProgram;
    this.setData({
      versionNo: envVersion === 'release' ? version : envVersion,
    });
  },
});
