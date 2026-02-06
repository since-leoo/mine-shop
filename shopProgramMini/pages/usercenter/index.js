import { fetchUserCenter } from '../../services/usercenter/fetchUsercenter';
import { bindPhoneNumber } from '../../services/usercenter/bindPhone';
import { authorizeProfile } from '../../services/usercenter/authorizeProfile';
import {
  ensureMiniProgramLogin,
  authorizeUserProfile,
  clearAuthStorage,
  getStoredMemberProfile,
} from '../../common/auth';
import Toast from 'tdesign-miniprogram/toast/index';

const menuData = [
  [
    {
      title: '收货地址',
      tit: '',
      url: '',
      type: 'address',
    },
    {
      title: '优惠券',
      tit: '',
      url: '',
      type: 'coupon',
    },
    {
      title: '积分',
      tit: '',
      url: '',
      type: 'point',
    },
  ],
  [
    {
      title: '帮助中心',
      tit: '',
      url: '',
      type: 'help-center',
    },
    {
      title: '客服热线',
      tit: '',
      url: '',
      type: 'service',
      icon: 'service',
    },
  ],
];

const orderTagInfos = [
  {
    title: '待付款',
    iconName: 'wallet',
    orderNum: 0,
    tabType: 5,
    status: 1,
  },
  {
    title: '待发货',
    iconName: 'deliver',
    orderNum: 0,
    tabType: 10,
    status: 1,
  },
  {
    title: '待收货',
    iconName: 'package',
    orderNum: 0,
    tabType: 40,
    status: 1,
  },
  {
    title: '待评价',
    iconName: 'comment',
    orderNum: 0,
    tabType: 60,
    status: 1,
  },
  {
    title: '退款/售后',
    iconName: 'exchang',
    orderNum: 0,
    tabType: 0,
    status: 1,
  },
];

const AUTH_STEP = {
  UNLOGIN: 1,
  BASIC: 2,
  FULL: 3,
};

const normalizeGenderValue = (gender) => {
  if (typeof gender === 'number') {
    return [0, 1, 2].includes(gender) ? gender : 0;
  }
  if (typeof gender === 'string') {
    const lower = gender.toLowerCase();
    if (lower === 'male' || lower === '1') {
      return 1;
    }
    if (lower === 'female' || lower === '2') {
      return 2;
    }
  }
  return 0;
};

const getDefaultData = () => ({
  showMakePhone: false,
  userInfo: {
    avatarUrl: '',
    nickName: '正在登录...',
    phoneNumber: '',
    authorizedProfile: false,
  },
  menuData,
  orderTagInfos,
  customerServiceInfo: {},
  currAuthStep: AUTH_STEP.UNLOGIN,
  showKefu: true,
  versionNo: '',
  isNeedGetUserInfo: false,
  authLoading: false,
  pageLoading: false,
  showPhoneAuthorize: false,
  phoneAuthLoading: false,
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
    this.fetUseriInfoHandle();
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
        showPhoneAuthorize: false,
      });
      return false;
    }
  },

  async fetUseriInfoHandle() {
    this.setData({ pageLoading: true });
    try {
      const {
        userInfo = {},
        countsData = [],
        orderTagInfos: orderInfo = [],
        customerServiceInfo = {},
      } = await fetchUserCenter();
      const derivedMenu = menuData.map((group) => group.map((item) => ({ ...item })));
      (derivedMenu[0] || []).forEach((item) => {
        const match = countsData?.find((counts) => counts.type === item.type);
        if (match) {
          // eslint-disable-next-line no-param-reassign
          item.tit = match.num;
        }
      });

      const info = orderTagInfos.map((v, index) => ({
        ...v,
        ...(orderInfo?.[index] || {}),
      }));

      const normalizedUserInfo = userInfo || {};
      const hasAuthorizedFlag = Object.prototype.hasOwnProperty.call(normalizedUserInfo, 'authorizedProfile');
      const fallbackNeedUserProfile =
        !normalizedUserInfo.avatarUrl ||
        !normalizedUserInfo.nickName ||
        normalizedUserInfo.nickName === '微信用户';
      const needUserProfile = hasAuthorizedFlag ? !Boolean(normalizedUserInfo.authorizedProfile) : fallbackNeedUserProfile;
      const shouldShowPhoneAuthorize = !normalizedUserInfo.phoneNumber;

      this.setData({
        userInfo: normalizedUserInfo,
        menuData: derivedMenu,
        orderTagInfos: info,
        customerServiceInfo,
        currAuthStep: needUserProfile ? AUTH_STEP.BASIC : AUTH_STEP.FULL,
        isNeedGetUserInfo: needUserProfile,
        showPhoneAuthorize: shouldShowPhoneAuthorize,
        pageLoading: false,
      });
    } catch (error) {
      console.warn('fetch user center failed', error);
      if (error?.code === 401) {
        clearAuthStorage();
        this.setData({
          currAuthStep: AUTH_STEP.UNLOGIN,
          isNeedGetUserInfo: false,
        });
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
    if (this.data.authLoading) {
      return;
    }
    this.setData({ authLoading: true });
    const success = await this.ensureLoginState(true);
    if (success) {
      await this.fetUseriInfoHandle();
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

  async handleAuthorizeUserInfo() {
    if (this.data.authLoading) {
      return;
    }
    const supportUserProfile = typeof wx.getUserProfile === 'function';
    console.log('usercenter authorize profile -> support getUserProfile', supportUserProfile);
    if (!supportUserProfile) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '当前微信版本过低，无法拉起头像昵称授权',
      });
      return;
    }
    this.setData({ authLoading: true });
    try {
      const profile = await authorizeUserProfile('用于完善会员头像和昵称');
      const userProfile = profile?.userInfo || {};
      if (!userProfile.nickName || !userProfile.avatarUrl) {
        throw new Error('未获取到完整的用户信息');
      }
      await authorizeProfile({
        nickname: userProfile.nickName,
        avatarUrl: userProfile.avatarUrl,
        gender: normalizeGenderValue(userProfile.gender),
      });
      Toast({
        context: this,
        selector: '#t-toast',
        message: '授权成功',
        theme: 'success',
      });
      await this.fetUseriInfoHandle();
    } catch (error) {
      if (error?.errMsg && error.errMsg.indexOf('fail auth deny') > -1) {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '已取消授权',
        });
      } else {
        Toast({
          context: this,
          selector: '#t-toast',
          message: error?.msg || '授权失败，请稍后再试',
          theme: 'error',
        });
      }
    } finally {
      this.setData({ authLoading: false });
    }
  },

  async onGetPhoneNumber(event) {
    if (this.data.phoneAuthLoading) {
      return;
    }
    const { detail = {} } = event || {};
    if (!detail.code) {
      if (detail.errMsg && detail.errMsg.indexOf('fail') > -1) {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '手机号授权已取消',
        });
      }
      return;
    }
    this.setData({ phoneAuthLoading: true });
    try {
      await bindPhoneNumber(detail.code);
      Toast({
        context: this,
        selector: '#t-toast',
        message: '手机号授权成功',
        theme: 'success',
      });
      await this.fetUseriInfoHandle();
    } catch (error) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: error?.msg || '手机号授权失败，请重试',
        theme: 'error',
      });
    } finally {
      this.setData({ phoneAuthLoading: false });
    }
  },

  onClickCell({ currentTarget }) {
    const { type } = currentTarget.dataset;

    switch (type) {
      case 'address': {
        wx.navigateTo({ url: '/pages/user/address/list/index' });
        break;
      }
      case 'service': {
        this.openMakePhone();
        break;
      }
      case 'help-center': {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '你点击了帮助中心',
          icon: '',
          duration: 1000,
        });
        break;
      }
      case 'point': {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '你点击了积分菜单',
          icon: '',
          duration: 1000,
        });
        break;
      }
      case 'coupon': {
        wx.navigateTo({ url: '/pages/coupon/coupon-list/index' });
        break;
      }
      default: {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '未知跳转',
          icon: '',
          duration: 1000,
        });
        break;
      }
    }
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
    wx.makePhoneCall({
      phoneNumber: this.data.customerServiceInfo.servicePhone,
    });
  },

  gotoUserEditPage() {
    const { currAuthStep, isNeedGetUserInfo } = this.data;
    if (currAuthStep === AUTH_STEP.UNLOGIN) {
      this.handleLoginTap();
      return;
    }
    if (currAuthStep === AUTH_STEP.BASIC && isNeedGetUserInfo) {
      this.handleAuthorizeUserInfo();
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
