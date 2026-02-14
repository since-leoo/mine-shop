import { fetchPerson, updateProfile, uploadImage } from '../../../services/usercenter/fetchPerson';
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
  onChooseAvatar() {
    if (!this.data.personInfo.avatarUrl) {
      this.chooseWechatAvatar();
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
      this.chooseWechatAvatar();
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

  // 微信头像选择（使用 chooseAvatar 能力）
  chooseWechatAvatar() {
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const tempFilePath = res.tempFiles[0].tempFilePath;
        Toast({ context: this, selector: '#t-toast', message: '上传中...', theme: 'loading', duration: 0 });
        uploadImage(tempFilePath)
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

  // ========== 昵称 ==========
  onEditNickname() {
    this.setData({
      showNicknameDialog: true,
      nicknameInput: this.data.personInfo.nickName || '',
    });
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
  onEditPhone() {
    this.setData({
      showPhoneDialog: true,
      phoneInput: this.data.personInfo.phoneNumber || '',
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
