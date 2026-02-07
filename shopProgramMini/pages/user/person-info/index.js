import { fetchPerson } from '../../../services/usercenter/fetchPerson';
import { authorizeProfile } from '../../../services/usercenter/authorizeProfile';
import { bindPhoneNumber } from '../../../services/usercenter/bindPhone';
import { uploadImage } from '../../../services/upload/uploadImage';
import { phoneEncryption } from '../../../utils/util';
import Toast from 'tdesign-miniprogram/toast/index';

const normalizeGenderValue = (gender) => {
  if (typeof gender === 'number') return gender;
  if (typeof gender === 'string') {
    const lower = gender.toLowerCase();
    if (lower === 'male' || lower === '1') return 1;
    if (lower === 'female' || lower === '2') return 2;
  }
  return 0;
};

Page({
  data: {
    personInfo: {
      avatarUrl: '',
      nickName: '',
      gender: 0,
      phoneNumber: '',
      levelName: '',
      balance: 0,
      points: 0,
      authorizedProfile: false,
    },
    defaultAvatarUrl: 'https://tdesign.gtimg.com/miniprogram/template/retail/usercenter/icon-user-center-avatar@2x.png',
    displayPhoneNumber: '',
    phoneAuthLoading: false,
    profileSaving: false,
    hasProfileChange: false,
    genderMap: ['未知', '男', '女'],
    typeVisible: false,
    pickerOptions: [
      { name: '男', code: '1' },
      { name: '女', code: '2' },
    ],
    pageLoading: false,
  },

  // 记录原始值，用于判断是否有变更
  _originalAvatar: '',
  _originalNickname: '',
  _originalGender: 0,

  onLoad() {
    this.fetchData();
  },

  onShow() {
    this.fetchData();
  },

  fetchData() {
    this.setData({ pageLoading: true });
    fetchPerson()
      .then((personInfo) => {
        this._originalAvatar = personInfo.avatarUrl || '';
        this._originalNickname = personInfo.nickName || '';
        this._originalGender = normalizeGenderValue(personInfo.gender);
        this.setData({
          personInfo: { ...personInfo, gender: this._originalGender },
          displayPhoneNumber: phoneEncryption(personInfo.phoneNumber),
          hasProfileChange: false,
          pageLoading: false,
        });
      })
      .catch((error) => {
        console.warn('fetch person info failed', error);
        this.setData({ pageLoading: false });
        Toast({
          context: this,
          selector: '#t-toast',
          message: error?.msg || '获取个人资料失败',
          theme: 'error',
        });
      });
  },

  /** 选择头像回调 */
  onChooseAvatar(e) {
    const { avatarUrl } = e.detail || {};
    if (!avatarUrl) return;
    this.setData({ 'personInfo.avatarUrl': avatarUrl });
    this.checkProfileChange();
  },

  /** 昵称输入 */
  onNicknameInput(e) {
    const value = (e.detail?.value || '').trim();
    this.setData({ 'personInfo.nickName': value });
    this.checkProfileChange();
  },

  /** 昵称失焦（微信 nickname 类型输入框在选择昵称后触发） */
  onNicknameBlur(e) {
    const value = (e.detail?.value || '').trim();
    if (value && value !== this.data.personInfo.nickName) {
      this.setData({ 'personInfo.nickName': value });
      this.checkProfileChange();
    }
  },

  /** 检查头像/昵称/性别是否有变更 */
  checkProfileChange() {
    const { avatarUrl, nickName, gender } = this.data.personInfo;
    const changed =
      avatarUrl !== this._originalAvatar ||
      nickName !== this._originalNickname ||
      gender !== this._originalGender;
    this.setData({ hasProfileChange: changed });
  },

  /** 保存头像昵称到后端 */
  async onSaveProfile() {
    if (this.data.profileSaving) return;
    let { avatarUrl, nickName, gender } = this.data.personInfo;
    if (!nickName) {
      Toast({ context: this, selector: '#t-toast', message: '请输入昵称' });
      return;
    }
    if (!avatarUrl) {
      Toast({ context: this, selector: '#t-toast', message: '请选择头像' });
      return;
    }
    this.setData({ profileSaving: true });
    try {
      // 如果头像是本地临时文件，先上传获取 URL
      if (avatarUrl.startsWith('wxfile://') || avatarUrl.startsWith('http://tmp/')) {
        avatarUrl = await uploadImage(avatarUrl);
        this.setData({ 'personInfo.avatarUrl': avatarUrl });
      }
      await authorizeProfile({ nickname: nickName, avatarUrl, gender });
      this._originalAvatar = avatarUrl;
      this._originalNickname = nickName;
      this._originalGender = gender;
      this.setData({ hasProfileChange: false, 'personInfo.authorizedProfile': true });
      Toast({
        context: this,
        selector: '#t-toast',
        message: '保存成功',
        theme: 'success',
      });
    } catch (error) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: error?.msg || '保存失败，请重试',
        theme: 'error',
      });
    } finally {
      this.setData({ profileSaving: false });
    }
  },

  onClickCell({ currentTarget }) {
    const { type } = currentTarget.dataset;
    if (type === 'gender') {
      this.setData({ typeVisible: true });
    }
  },

  onClose() {
    this.setData({ typeVisible: false });
  },

  onConfirm(e) {
    const { value } = e.detail;
    this.setData({ typeVisible: false, 'personInfo.gender': Number(value) }, () => {
      this.checkProfileChange();
    });
  },

  /** 授权手机号 */
  async onBindPhoneNumber(event) {
    if (this.data.phoneAuthLoading) return;
    const { detail = {} } = event || {};
    const code = detail.code || '';
    const errMsg = detail.errMsg || '';

    // 用户主动取消
    if (!code && errMsg.indexOf('deny') > -1) {
      Toast({ context: this, selector: '#t-toast', message: '已取消手机号授权' });
      return;
    }
    // 没有 code 且不是取消（如开发工具不支持等），静默忽略
    if (!code) {
      console.warn('getPhoneNumber: no code', detail);
      return;
    }
    this.setData({ phoneAuthLoading: true });
    try {
      const result = await bindPhoneNumber(detail.code);
      const phoneNumber = result?.phone_number || '';
      this.setData({
        'personInfo.phoneNumber': phoneNumber,
        displayPhoneNumber: phoneEncryption(phoneNumber),
      });
      Toast({
        context: this,
        selector: '#t-toast',
        message: '手机号授权成功',
        theme: 'success',
      });
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
});
