/* eslint-disable no-param-reassign */
import { fetchDeliveryAddressList, deleteDeliveryAddress, createDeliveryAddress } from '../../../../services/address/fetchAddress';
import Toast from 'tdesign-miniprogram/toast/index';
import { resolveAddress, rejectAddress } from '../../../../services/address/list';
import { getAddressPromise } from '../../../../services/address/edit';

Page({
  data: {
    addressList: [],
    deleteID: '',
    showDeleteConfirm: false,
    isOrderSure: false,
    loading: false,
  },

  /** 选择模式 */
  selectMode: false,
  /** 是否已经选择地址，不置为true的话页面离开时会触发取消选择行为 */
  hasSelect: false,

  onLoad(query) {
    const { selectMode = '', isOrderSure = '', id = '' } = query;
    this.setData({
      isOrderSure: !!isOrderSure,
      id,
    });
    this.selectMode = !!selectMode;
    this.init();
  },

  init() {
    this.getAddressList();
  },
  onUnload() {
    if (this.selectMode && !this.hasSelect) {
      rejectAddress();
    }
  },
  getAddressList() {
    const { id } = this.data;
    this.setData({ loading: true });
    fetchDeliveryAddressList(50)
      .then((addressList) => {
        const nextList = (addressList || []).map((address) => ({
          ...address,
          checked: id ? address.id === id : Boolean(address.isDefault),
        }));
        this.setData({ addressList: nextList });
      })
      .catch((error) => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: error?.msg || error?.message || '获取地址列表失败',
          theme: 'error',
          duration: 1500,
        });
      })
      .finally(() => {
        this.setData({ loading: false });
      });
  },
  getWXAddressHandle() {
    wx.chooseAddress({
      success: (res) => {
        if (res.errMsg.indexOf('ok') === -1) {
          Toast({
            context: this,
            selector: '#t-toast',
            message: res.errMsg,
            icon: '',
            duration: 1000,
          });
          return;
        }
        this.createAddressFromWechat(res);
      },
      fail: (error) => {
        if (error?.errMsg && error.errMsg.indexOf('cancel') > -1) {
          return;
        }
        Toast({
          context: this,
          selector: '#t-toast',
          message: '微信地址读取失败',
          duration: 1500,
          theme: 'error',
        });
      },
    });
  },
  confirmDeleteHandle({ detail }) {
    const { id } = detail || {};
    if (id !== undefined) {
      this.setData({ deleteID: id, showDeleteConfirm: true });
      Toast({
        context: this,
        selector: '#t-toast',
        message: '地址删除成功',
        theme: 'success',
        duration: 1000,
      });
    } else {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '需要组件库发新版才能拿到地址ID',
        icon: '',
        duration: 1000,
      });
    }
  },
  deleteAddressHandle(e) {
    const datasetId = e.currentTarget?.dataset?.id;
    const eventId = e.detail?.address?.id;
    const targetId = datasetId || eventId;
    if (!targetId) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '未获取到地址ID',
        duration: 1500,
      });
      return;
    }
    deleteDeliveryAddress(targetId)
      .then(() => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '地址删除成功',
          theme: 'success',
          duration: 1200,
        });
        this.getAddressList();
      })
      .catch((error) => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: error?.msg || '删除地址失败',
          theme: 'error',
          duration: 1500,
        });
      });
  },
  editAddressHandle({ detail }) {
    this.waitForNewAddress();

    const { id } = detail || {};
    wx.navigateTo({ url: `/pages/user/address/edit/index?id=${id}` });
  },
  selectHandle({ detail }) {
    if (this.selectMode) {
      this.hasSelect = true;
      resolveAddress(detail);
      wx.navigateBack({ delta: 1 });
    } else {
      this.editAddressHandle({ detail });
    }
  },
  createHandle() {
    this.waitForNewAddress();
    wx.navigateTo({ url: '/pages/user/address/edit/index' });
  },

  waitForNewAddress() {
    getAddressPromise()
      .then(() => {
        this.setData({
          deleteID: '',
          showDeleteConfirm: false,
        });
        this.getAddressList();
      })
      .catch((e) => {
        if (e.message !== 'cancel') {
          Toast({
            context: this,
            selector: '#t-toast',
            message: '地址编辑发生错误',
            theme: 'error',
            duration: 1500,
          });
        }
      });
  },
  createAddressFromWechat(res) {
    const payload = {
      name: res.userName || '',
      phone: res.telNumber || '',
      provinceName: res.provinceName || '',
      cityName: res.cityName || '',
      districtName: res.countyName || res.countryName || '',
      detailAddress: res.detailInfo || '',
      addressTag: '微信地址',
      isDefault: this.data.addressList.length === 0,
    };
    createDeliveryAddress(payload)
      .then(() => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '添加成功',
          theme: 'success',
          duration: 1200,
        });
        this.getAddressList();
      })
      .catch((error) => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: error?.msg || '微信地址添加失败',
          theme: 'error',
          duration: 1500,
        });
      });
  },
});
