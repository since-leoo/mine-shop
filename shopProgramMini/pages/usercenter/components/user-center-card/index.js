const AuthStepType = {
  ONE: 1,
  TWO: 2,
  THREE: 3,
};

Component({
  properties: {
    currAuthStep: {
      type: Number,
      value: AuthStepType.ONE,
    },
    userInfo: {
      type: Object,
      value: {},
    },
    couponCount: {
      type: Number,
      value: 0,
    },
  },
  data: {
    defaultAvatarUrl:
      'https://tdesign.gtimg.com/miniprogram/template/retail/usercenter/icon-user-center-avatar@2x.png',
    AuthStepType,
    statusBarHeight: 20,
  },
  lifetimes: {
    attached() {
      const sysInfo = wx.getSystemInfoSync();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    },
  },
  methods: {
    gotoUserEditPage() {
      this.triggerEvent('gotoUserEditPage');
    },
    onTapPoints() {
      this.triggerEvent('onTapPoints');
    },
    onTapBalance() {
      this.triggerEvent('onTapBalance');
    },
    onTapCoupon() {
      this.triggerEvent('onTapCoupon');
    },
  },
});
