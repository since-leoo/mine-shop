import { fetchPerson, updateProfile, uploadImage } from '../../../services/usercenter/fetchPerson';
import { bindPhoneNumber } from '../../../services/usercenter/authorizeProfile';
import Toast from 'tdesign-miniprogram/toast/index';

Page({
  data: {
    personInfo: {
      avatarUrl: '',
      nickName: '',
      gender: 0,
      phoneNumber: '',
    },
    originalInfo: {},
    submitting: false,
    showNicknameDialog: false,
    nicknameInput: '',
    showPhoneDialog: false,
    phoneInput: '',
    showAvatarSheet: false,
    avatarActions: [
      { label: '从相册选择' },
      { label: '微信授权头像' },
    ],
    pickerOptions: [
      { name: '男', code: '1' },
      { name: '女', code: '2' },
    ],
    typeVisible: false,
    genderMap: ['', '男', '女'],
  },

  onLoad() {
    this.fetchData();
  },

  fetchData() {
    fetchPerson().then((personInfo) => {
      this.setData({
        personInfo,
        originalInfo: { ...personInfo },
      });
    });
  },

  // ========== 头像 ==========
  // 无头像 → 直接微信授权; 有头像 → 弹出选择面板
  onChooseAvatar() {
    if (!this.data.personInfo.avatarUrl) {
      this.authorizeWechatProfile();
    } else {
      this.setData({ showAvatarSheet: true });
    }
  },

  onAvatarSheetCancel() {
    this.setData({ showAvatarSheet: false });
  },

  onAvatarActionSelected(e) {
    this.setData({ showAvatarSheet: false });
    if (e.detail.index === 0) {
      this.chooseImageFromAlbum();
    } else {
      this.authorizeWechatProfile();
    }
  },

  chooseImageFromAlbum() {
    wx.chooseImage({
      count: 1,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const { path, size } = res.tempFiles[0];
        if (size > 5 * 1024 * 1024) {
          Toast({ context: this, selector: '#t-toast', message: '图片不能超过5MB', theme: 'error' });
          return;
        }
        Toast({ context: this, selector: '#t-toast', message: '上传中...', theme: 'loading', duration: 0 });
        uploadImage(path)
          .then((url) => {
            this.setData({ 'personInfo.avatarUrl': url });
            Toast({ context: this, selector: '#t-toast', message: '头像已更新', theme: 'success' });
          })
          .catch((err) => {
            Toast({ context: this, selector: '#t-toast', message: err.msg || '上传失败', theme: 'error' });
          });
      },
      fail: () => {},
    });
  },

  // 微信授权获取头像+昵称(一次性获取)
  authorizeWechatProfile() {
    wx.getUserProfile({
      desc: '用于完善个人资料',
      success: (res) => {
        const { avatarUrl, nickName } = res.userInfo || {};
        const updates = {};
        if (avatarUrl) updates['personInfo.avatarUrl'] = avatarUrl;
        if (nickName) updates['personInfo.nickName'] = nickName;
        if (Object.keys(updates).length) {
          this.setData(updates);
          Toast({ context: this, selector: '#t-toast', message: '授权成功', theme: 'success' });
        }
      },
      fail: () => {},
    });
  },

  // ========== 昵称 ==========
  // 无昵称 → 微信授权; 有昵称 → 弹编辑框
  onEditNickname() {
    if (!this.data.personInfo.nickName) {
      this.authorizeWechatProfile();
    } else {
      this.setData({
        showNicknameDialog: true,
        nicknameInput: this.data.personInfo.nickName,
      });
    }
  },

  onNicknameInput(e) {
    this.setData({ nicknameInput: e.detail.value });
  },

  onNicknameConfirm() {
    const name = (this.data.nicknameInput || '').trim();
    if (!name) {
      Toast({ context: this, selector: '#t-toast', message: '昵称不能为空', theme: 'warning' });
      return;
    }
    this.setData({ 'personInfo.nickName': name, showNicknameDialog: false });
  },

  onNicknameCancel() {
    this.setData({ showNicknameDialog: false });
  },

  // ========== 性别 ==========
  onClickCell({ currentTarget }) {
    if (currentTarget.dataset.type === 'gender') {
      this.setData({ typeVisible: true });
    }
  },

  onClose() {
    this.setData({ typeVisible: false });
  },

  onConfirm(e) {
    this.setData({ typeVisible: false, 'personInfo.gender': e.detail.value });
  },

  // ========== 手机号 ==========
  // 无手机号 → 微信授权获取手机号; 有手机号 → 弹编辑框
  onEditPhone() {
    if (!this.data.personInfo.phoneNumber) {
      this.authorizeWechatPhone();
    } else {
      this.setData({
        showPhoneDialog: true,
        phoneInput: this.data.personInfo.phoneNumber,
      });
    }
  },

  authorizeWechatPhone() {
    // 微信获取手机号需要 button open-type="getPhoneNumber"
    // 这里用 wx.showModal 引导用户
    wx.showModal({
      title: '绑定手机号',
      content: '请在个人中心使用微信授权绑定手机号',
      showCancel: false,
    });
  },

  onPhoneInput(e) {
    this.setData({ phoneInput: e.detail.value });
  },

  onPhoneConfirm() {
    const phone = (this.data.phoneInput || '').trim();
    if (phone && !/^1[3-9]\d{9}$/.test(phone)) {
      Toast({ context: this, selector: '#t-toast', message: '请输入正确的手机号', theme: 'warning' });
      return;
    }
    this.setData({ 'personInfo.phoneNumber': phone, showPhoneDialog: false });
  },

  onPhoneCancel() {
    this.setData({ showPhoneDialog: false });
  },

  // ========== 提交 ==========
  onSubmit() {
    const { personInfo, originalInfo } = this.data;
    const payload = {};

    if (personInfo.avatarUrl !== originalInfo.avatarUrl) {
      payload.avatarUrl = personInfo.avatarUrl;
    }
    if (personInfo.nickName !== originalInfo.nickName) {
      payload.nickname = personInfo.nickName;
    }
    if (personInfo.gender !== originalInfo.gender) {
      payload.gender = personInfo.gender;
    }
    if (personInfo.phoneNumber !== originalInfo.phoneNumber) {
      payload.phone = personInfo.phoneNumber;
    }

    if (Object.keys(payload).length === 0) {
      Toast({ context: this, selector: '#t-toast', message: '没有修改内容', theme: 'warning' });
      return;
    }

    this.setData({ submitting: true });
    updateProfile(payload)
      .then(() => {
        Toast({ context: this, selector: '#t-toast', message: '修改成功', theme: 'success' });
        this.setData({ originalInfo: { ...personInfo }, submitting: false });
        setTimeout(() => wx.navigateBack(), 800);
      })
      .catch((err) => {
        this.setData({ submitting: false });
        Toast({ context: this, selector: '#t-toast', message: err.msg || '修改失败', theme: 'error' });
      });
  },
});
