import Toast from 'tdesign-miniprogram/toast/index';
import { fetchDeliveryAddress, createDeliveryAddress, updateDeliveryAddress } from '../../../../services/address/fetchAddress';
import { areaData } from '../../../../config/index';
import { resolveAddress, rejectAddress } from '../../../../services/address/edit';

const innerPhoneReg = '^1(?:3\\d|4[4-9]|5[0-35-9]|6[67]|7[0-8]|8\\d|9\\d)\\d{8}$';
const innerNameReg = '^[a-zA-Z\\d\\u4e00-\\u9fa5]+$';
const labelsOptions = [
  { id: 0, name: '家' },
  { id: 1, name: '公司' },
];

Page({
  options: {
    multipleSlots: true,
  },
  externalClasses: ['theme-wrapper-class'],
  data: {
    locationState: {
      labelIndex: null,
      addressId: '',
      addressTag: '',
      cityCode: '',
      cityName: '',
      countryCode: '',
      countryName: '',
      detailAddress: '',
      districtCode: '',
      districtName: '',
      isDefault: false,
      name: '',
      phone: '',
      provinceCode: '',
      provinceName: '',
      isEdit: false,
      isOrderDetail: false,
      isOrderSure: false,
    },
    areaData: areaData,
    labels: labelsOptions,
    areaPickerVisible: false,
    submitActive: false,
    visible: false,
    labelValue: '',
    columns: 3,
    saving: false,
  },
  privateData: {
    verifyTips: '',
  },
  onLoad(options) {
    const { id } = options;
    this.init(id);
  },

  onUnload() {
    if (!this.hasSave) {
      rejectAddress();
    }
  },

  hasSave: false,

  init(id) {
    if (id) {
      this.getAddressDetail(Number(id));
    }
  },
  getAddressDetail(id) {
    fetchDeliveryAddress(id)
      .then((detail) => {
        this.setData(
          {
            locationState: {
              ...this.data.locationState,
              ...detail,
              isDefault: Boolean(detail?.isDefault),
              labelIndex: detail?.addressTag ? this.findLabelIndex(detail.addressTag) : null,
            },
          },
          () => {
            const { isLegal, tips } = this.onVerifyInputLegal();
            this.setData({
              submitActive: isLegal,
            });
            this.privateData.verifyTips = tips;
          },
        );
      })
      .catch((error) => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: error?.msg || '获取地址详情失败',
          theme: 'error',
          duration: 1500,
        });
      });
  },
  findLabelIndex(tagName = '') {
    const { labels = [] } = this.data;
    const index = labels.findIndex((item) => item.name === tagName);
    return index > -1 ? index : null;
  },
  onInputValue(e) {
    const { item } = e.currentTarget.dataset;
    if (item === 'address') {
      const { selectedOptions = [] } = e.detail;
      this.setData(
        {
          'locationState.provinceCode': selectedOptions[0].value,
          'locationState.provinceName': selectedOptions[0].label,
          'locationState.cityName': selectedOptions[1].label,
          'locationState.cityCode': selectedOptions[1].value,
          'locationState.districtCode': selectedOptions[2].value,
          'locationState.districtName': selectedOptions[2].label,
          areaPickerVisible: false,
        },
        () => {
          const { isLegal, tips } = this.onVerifyInputLegal();
          this.setData({
            submitActive: isLegal,
          });
          this.privateData.verifyTips = tips;
        },
      );
    } else {
      const { value = '' } = e.detail;
      this.setData(
        {
          [`locationState.${item}`]: value,
        },
        () => {
          const { isLegal, tips } = this.onVerifyInputLegal();
          this.setData({
            submitActive: isLegal,
          });
          this.privateData.verifyTips = tips;
        },
      );
    }
  },
  onPickArea() {
    this.setData({ areaPickerVisible: true });
  },
  onPickLabels(e) {
    const { item } = e.currentTarget.dataset;
    const {
      locationState: { labelIndex = undefined },
      labels = [],
    } = this.data;
    let payload = {
      labelIndex: item,
      addressTag: labels[item].name,
    };
    if (item === labelIndex) {
      payload = { labelIndex: null, addressTag: '' };
    }
    this.setData({
      'locationState.labelIndex': payload.labelIndex,
      'locationState.addressTag': payload.addressTag,
    });
    this.triggerEvent('triggerUpdateValue', payload);
  },
  addLabels() {
    this.setData({
      visible: true,
    });
  },
  confirmHandle() {
    const { labels, labelValue } = this.data;
    this.setData({
      visible: false,
      labels: [...labels, { id: labels[labels.length - 1].id + 1, name: labelValue }],
      labelValue: '',
    });
  },
  cancelHandle() {
    this.setData({
      visible: false,
      labelValue: '',
    });
  },
  onCheckDefaultAddress({ detail }) {
    const { value } = detail;
    this.setData({
      'locationState.isDefault': !!value,
    });
  },

  onVerifyInputLegal() {
    const { name, phone, detailAddress, districtName } = this.data.locationState;
    const prefixPhoneReg = String(this.properties.phoneReg || innerPhoneReg);
    const prefixNameReg = String(this.properties.nameReg || innerNameReg);
    const nameRegExp = new RegExp(prefixNameReg);
    const phoneRegExp = new RegExp(prefixPhoneReg);

    if (!name || !name.trim()) {
      return {
        isLegal: false,
        tips: '请填写收货人',
      };
    }
    if (!nameRegExp.test(name)) {
      return {
        isLegal: false,
        tips: '收货人仅支持输入中文、英文（区分大小写）、数字',
      };
    }
    if (!phone || !phone.trim()) {
      return {
        isLegal: false,
        tips: '请填写手机号',
      };
    }
    if (!phoneRegExp.test(phone)) {
      return {
        isLegal: false,
        tips: '请填写正确的手机号',
      };
    }
    if (!districtName || !districtName.trim()) {
      return {
        isLegal: false,
        tips: '请选择省市区信息',
      };
    }
    if (!detailAddress || !detailAddress.trim()) {
      return {
        isLegal: false,
        tips: '请完善详细地址',
      };
    }
    if (detailAddress && detailAddress.trim().length > 50) {
      return {
        isLegal: false,
        tips: '详细地址不能超过50个字符',
      };
    }
    return {
      isLegal: true,
      tips: '添加成功',
    };
  },

  builtInSearch({ code, name }) {
    return new Promise((resolve, reject) => {
      wx.getSetting({
        success: (res) => {
          if (res.authSetting[code] === false) {
            wx.showModal({
              title: `获取${name}失败`,
              content: `获取${name}失败，请在【右上角】-小程序【设置】项中，将【${name}】开启。`,
              confirmText: '去设置',
              confirmColor: '#FA550F',
              cancelColor: '取消',
              success(res) {
                if (res.confirm) {
                  wx.openSetting({
                    success(settingRes) {
                      if (settingRes.authSetting[code] === true) {
                        resolve();
                      } else {
                        console.warn('用户未打开权限', name, code);
                        reject();
                      }
                    },
                  });
                } else {
                  reject();
                }
              },
              fail() {
                reject();
              },
            });
          } else {
            resolve();
          }
        },
        fail() {
          reject();
        },
      });
    });
  },

  onSearchAddress() {
    this.builtInSearch({ code: 'scope.userLocation', name: '地址位置' }).then(() => {
      wx.chooseLocation({
        success: (res) => {
          if (res.name) {
            this.triggerEvent('addressParse', {
              address: res.address,
              name: res.name,
              latitude: res.latitude,
              longitude: res.longitude,
            });
          } else {
            Toast({
              context: this,
              selector: '#t-toast',
              message: '地点为空，请重新选择',
              icon: '',
              duration: 1000,
            });
          }
        },
        fail: function (res) {
          console.warn(`wx.chooseLocation fail: ${JSON.stringify(res)}`);
          if (res.errMsg !== 'chooseLocation:fail cancel') {
            Toast({
              context: this,
              selector: '#t-toast',
              message: '地点错误，请重新选择',
              icon: '',
              duration: 1000,
            });
          }
        },
      });
    });
  },
  formSubmit() {
    const { submitActive } = this.data;
    if (!submitActive) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: this.privateData.verifyTips,
        icon: '',
        duration: 1000,
      });
      return;
    }
    const { locationState } = this.data;

    if (this.data.saving) {
      return;
    }

    this.setData({ saving: true });
    const payload = this.buildSubmitPayload(locationState);
    const request = locationState.addressId
      ? updateDeliveryAddress(locationState.addressId, payload)
      : createDeliveryAddress(payload);

    request
      .then((savedAddress) => {
        this.hasSave = true;
        Toast({
          context: this,
          selector: '#t-toast',
          message: locationState.addressId ? '更新成功' : '添加成功',
          theme: 'success',
          duration: 1200,
        });
        resolveAddress(savedAddress);
        setTimeout(() => {
          wx.navigateBack({ delta: 1 });
        }, 300);
      })
      .catch((error) => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: error?.msg || '保存地址失败',
          theme: 'error',
          duration: 1500,
        });
      })
      .finally(() => {
        this.setData({ saving: false });
      });
  },

  buildSubmitPayload(state = {}) {
    return {
      name: state.name || '',
      phone: state.phone || '',
      provinceName: state.provinceName || '',
      provinceCode: state.provinceCode || '',
      cityName: state.cityName || '',
      cityCode: state.cityCode || '',
      districtName: state.districtName || '',
      districtCode: state.districtCode || '',
      detailAddress: state.detailAddress || '',
      addressTag: state.addressTag || '',
      isDefault: Boolean(state.isDefault),
    };
  },

  getWeixinAddress(e) {
    const { locationState } = this.data;
    const weixinAddress = e.detail;
    this.setData(
      {
        locationState: { ...locationState, ...weixinAddress },
      },
      () => {
        const { isLegal, tips } = this.onVerifyInputLegal();
        this.setData({
          submitActive: isLegal,
        });
        this.privateData.verifyTips = tips;
      },
    );
  },
});
