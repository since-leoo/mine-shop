import { config } from '../../config/index';
import { request } from '../request';

/** 获取收货地址（mock） */
function mockFetchDeliveryAddress(id) {
  const { delay } = require('../_utils/delay');
  const { genAddress } = require('../../model/address');

  return delay().then(() => genAddress(id));
}

/** 获取收货地址 */
export function fetchDeliveryAddress(id = 0) {
  if (config.useMock) {
    return mockFetchDeliveryAddress(id);
  }

  if (!id) {
    return Promise.reject(new Error('缺少地址ID'));
  }

  return request({
    url: `/api/v1/member/addresses/${id}`,
    method: 'GET',
    needAuth: true,
  });
}

/** 获取收货地址列表（mock） */
function mockFetchDeliveryAddressList(len = 0) {
  const { delay } = require('../_utils/delay');
  const { genAddressList } = require('../../model/address');

  return delay().then(() =>
    genAddressList(len).map((address) => ({
      ...address,
      phoneNumber: address.phone,
      address: `${address.province}${address.city}${address.district}${address.detail}`,
    })),
  );
}

/** 获取收货地址列表 */
export function fetchDeliveryAddressList(len = 10) {
  if (config.useMock) {
    return mockFetchDeliveryAddressList(len);
  }

  return request({
    url: '/api/v1/member/addresses',
    method: 'GET',
    needAuth: true,
    data: {
      limit: len,
    },
  }).then((data = {}) => {
    const list = Array.isArray(data.list) ? data.list : [];
    return list.map((item) => normalizeAddressItem(item));
  });
}

const normalizeBoolean = (value) => value === true || value === 1 || value === '1';

/**
 * 归一化地址列表项，补充前端 UI 组件所需的衍生字段.
 * 后端返回 snake_case，这里补充 UI 组件绑定所需的展示字段.
 */
const normalizeAddressItem = (address = {}) => ({
  ...address,
  id: address.id ? String(address.id) : '',
  phoneNumber: address.phone || '',
  address: address.full_address || buildFullAddress(address),
  isDefault: normalizeBoolean(address.is_default) ? 1 : 0,
});

const buildFullAddress = (address = {}) => {
  const parts = [
    address.province || '',
    address.city || '',
    address.district || '',
    address.detail || '',
  ];
  return parts.join('').trim();
};

/**
 * 构建提交到后端的 payload，字段名与后端 API 完全一致（snake_case）.
 */
const buildAddressPayload = (address = {}) => ({
  name: address.name || '',
  phone: address.phone || '',
  province: address.province || '',
  province_code: address.province_code || '',
  city: address.city || '',
  city_code: address.city_code || '',
  district: address.district || '',
  district_code: address.district_code || '',
  detail: address.detail || '',
  is_default: normalizeBoolean(address.is_default),
});

export function createDeliveryAddress(payload = {}) {
  if (config.useMock) {
    return Promise.resolve(normalizeAddressItem(payload));
  }
  return request({
    url: '/api/v1/member/addresses',
    method: 'POST',
    needAuth: true,
    data: buildAddressPayload(payload),
  });
}

export function updateDeliveryAddress(addressId, payload = {}) {
  if (!addressId) {
    return Promise.reject(new Error('缺少地址ID'));
  }
  if (config.useMock) {
    return Promise.resolve(normalizeAddressItem({ ...payload, id: addressId }));
  }
  return request({
    url: `/api/v1/member/addresses/${addressId}`,
    method: 'PUT',
    needAuth: true,
    data: buildAddressPayload(payload),
  });
}

export function deleteDeliveryAddress(addressId) {
  if (!addressId) {
    return Promise.reject(new Error('缺少地址ID'));
  }
  if (config.useMock) {
    return Promise.resolve(true);
  }
  return request({
    url: `/api/v1/member/addresses/${addressId}`,
    method: 'DELETE',
    needAuth: true,
  });
}

export function markDefaultDeliveryAddress(addressId) {
  if (!addressId) {
    return Promise.reject(new Error('缺少地址ID'));
  }
  if (config.useMock) {
    return Promise.resolve(true);
  }
  return request({
    url: `/api/v1/member/addresses/${addressId}/default`,
    method: 'POST',
    needAuth: true,
  });
}
