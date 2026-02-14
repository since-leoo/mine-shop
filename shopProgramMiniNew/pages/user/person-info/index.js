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
    // 头像
    showAvatarSheet: false,
    wxAvatarUrl: '', // 微信头像（用于弹窗预览和直接应用）
    // 昵称
    editingNickname: false,
    nicknameInput: '',
    showNicknameAuthDialog: false,
    // 手机号
    editingPhone: false,
    phoneInput: '',
    // 性别
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
        // 用当前已有头像作为微信头像预览（如果有的话）
        wxAvatarUrl: personInfo.avatarUrl || '',
      });
    });
  },

  /** 获取微信头像（仅在弹窗打开且无头像时触发） */
  fetchWxAvatar() {
    if (this.data.wxAvatarUrl) return;
    wx.getUserProfile({
      desc: '用于获取头像',
      success: (res) => {
        this.setData({ wxAvatarUrl: res.userInfo.avatarUrl || '' });
      },
      fail: () => {},
    });
  },

  // ========== 头像（底部弹窗：微信头像预览 + 直接应用 / 相册选择） ==========
  onChooseAvatar() {
    this.fetchWxAvatar();
    this.setData({ showAvatarSheet: true });
  },

  onAvatarSheetCancel() {
    this.setData({ showAvatarSheet: false });
  },

  /** 直接应用微信头像（已有URL，无需上传） */
  onUseWechatAvatar() {
    this.setData({ showAvatarSheet: false });
    const wxUrl = this.data.wxAvatarUrl;
    if (!wxUrl) {
      Toast({ context: this, selector: '#t-toast', message: '未获取到微信头像', theme: 'warning' });
      return;
    }
    this.setData({ 'personInfo.avatarUrl': wxUrl });
    Toast({ context: this, selector: '#t-toast', message: '头像已更新', theme: 'success' });
  },

  /** 从相册选择 */
  onChooseFromAlbum() {
    this.setData({ showAvatarSheet: false });
    this.chooseImageFromAlbum();
  },

  chooseImageFromAlbum() {
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['album'],
      success: (res) => {
        const tempFilePath = res.tempFiles[0].tempFilePath;
        const size = res.tempFiles[0].size;
        if (size > 5 * 1024 * 1024) {
          Toast({ context: this, selector: '#t-toast', message: '图片不能超过5MB', theme: 'error' });
          return;
        }
        this._uploadAvatar(tempFilePath);
      },
      fail: () => {},
    });
  },

  _uploadAvatar(filePath) {
    Toast({ context: this, selector: '#t-toast', message: '上传中...', theme: 'loading', duration: 0 });
    uploadImage(filePath)
      .then((url) => {
        this.setData({ 'personInfo.avatarUrl': url });
        Toast({ context: this, selector: '#t-toast', message: '头像已更新', theme: 'success' });
      })
      .catch((err) => {
        Toast({ context: this, selector: '#t-toast', message: err.msg || '上传失败', theme: 'error' });
      });
  },

  // ========== 昵称（点击进入编辑，为空时弹出授权选择） ==========
  onEditNickname() {
    if (this.data.editingNickname) return;
    const currentNick = this.data.personInfo.nickName || '';
    if (!currentNick) {
      // 昵称为空，弹出授权确认
      this.setData({ showNicknameAuthDialog: true });
    } else {
      // 已有昵称，直接进入编辑
      this.setData({ editingNickname: true, nicknameInput: currentNick });
    }
  },

  onUseWechatNickname() {
    this.setData({ showNicknameAuthDialog: false });
    // 使用 wx.getUserProfile 获取微信昵称
    wx.getUserProfile({
      desc: '用于完善个人资料',
      success: (res) => {
        const nickName = res.userInfo.nickName || '';
        this.setData({
          'personInfo.nickName': nickName,
          nicknameInput: nickName,
          editingNickname: true,
        });
      },
      fail: () => {
        // 授权失败，进入手动输入
        this.setData({ editingNickname: true, nicknameInput: '' });
      },
    });
  },

  onManualNickname() {
    this.setData({
      showNicknameAuthDialog: false,
      editingNickname: true,
      nicknameInput: '',
    });
  },

  onNicknameInput(e) {
    this.setData({ nicknameInput: e.detail.value });
  },

  onNicknameBlur() {
    const name = (this.data.nicknameInput || '').trim();
    this.setData({
      editingNickname: false,
      'personInfo.nickName': name || this.data.personInfo.nickName,
    });
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

  // ========== 手机号（点击进入编辑，未绑定时可授权） ==========
  onEditPhone() {
    if (this.data.editingPhone) return;
    this.setData({
      editingPhone: true,
      phoneInput: this.data.personInfo.phoneNumber || '',
    });
  },

  onGetPhoneNumber(e) {
    if (e.detail.errMsg !== 'getPhoneNumber:ok') return;
    const code = e.detail.code;
    if (!code) return;
    Toast({ context: this, selector: '#t-toast', message: '绑定中...', theme: 'loading', duration: 0 });
    bindPhoneNumber(code)
      .then((res) => {
        const phone = res.purePhoneNumber || res.phoneNumber || '';
        this.setData({
          'personInfo.phoneNumber': phone,
          phoneInput: phone,
          editingPhone: false,
        });
        Toast({ context: this, selector: '#t-toast', message: '手机号已绑定', theme: 'success' });
      })
      .catch((err) => {
        Toast({ context: this, selector: '#t-toast', message: err.msg || '绑定失败', theme: 'error' });
      });
  },

  onPhoneInput(e) {
    this.setData({ phoneInput: e.detail.value });
  },

  onPhoneBlur() {
    const phone = (this.data.phoneInput || '').trim();
    if (phone && !/^1[3-9]\d{9}$/.test(phone)) {
      Toast({ context: this, selector: '#t-toast', message: '请输入正确的手机号', theme: 'warning' });
      return;
    }
    this.setData({
      editingPhone: false,
      'personInfo.phoneNumber': phone || this.data.personInfo.phoneNumber,
    });
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
