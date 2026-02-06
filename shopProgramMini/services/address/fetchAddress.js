import { config } from '../../config/index';
import { request } from '../request';

/** 获取收货地址 */
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
  }).then((data = {}) => normalizeAddressDetail(data));
}

/** 获取收货地址列表 */
function mockFetchDeliveryAddressList(len = 0) {
  const { delay } = require('../_utils/delay');
  const { genAddressList } = require('../../model/address');

  return delay().then(() =>
    genAddressList(len).map((address) => {
      return {
        ...address,
        phoneNumber: address.phone,
        address: `${address.provinceName}${address.cityName}${address.districtName}${address.detailAddress}`,
        tag: address.addressTag,
      };
    }),
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

const normalizeAddressDetail = (detail = {}) => {
  const normalized = {
    ...detail,
    id: detail.id ? String(detail.id) : detail.id,
    addressId: detail.addressId ? String(detail.addressId) : String(detail.id || ''),
    isDefault: normalizeBoolean(detail.isDefault),
  };
  normalized.phone = normalized.phone || normalized.phoneNumber || normalized.phone || '';
  normalized.phoneNumber = normalized.phoneNumber || normalized.phone || '';
  normalized.name = normalized.name || normalized.name || '';
  normalized.provinceName = normalized.provinceName || normalized.province || '';
  normalized.provinceCode = normalized.provinceCode || normalized.province_code || '';
  normalized.cityName = normalized.cityName || normalized.city || '';
  normalized.cityCode = normalized.cityCode || normalized.city_code || '';
  normalized.districtName = normalized.districtName || normalized.district || '';
  normalized.districtCode = normalized.districtCode || normalized.district_code || '';
  normalized.detailAddress = normalized.detailAddress || normalized.detail || '';
  normalized.addressTag = normalized.addressTag || normalized.tag || '';
  return normalized;
};

const buildFullAddress = (address = {}) => {
  if (address.fullAddress) {
    return address.fullAddress;
  }
  const parts = [
    address.provinceName || address.province || '',
    address.cityName || address.city || '',
    address.districtName || address.district || '',
    address.detailAddress || address.detail || '',
  ];
  return parts.join('').trim();
};

const normalizeAddressItem = (address = {}) => {
  const normalized = normalizeAddressDetail(address);
  return {
    ...normalized,
    phoneNumber: normalized.phone || normalized.phoneNumber || '',
    address: buildFullAddress(normalized),
    isDefault: normalizeBoolean(normalized.isDefault),
    tag: normalized.addressTag || normalized.tag || '',
  };
};

const buildAddressPayload = (address = {}) => ({
  name: address.name || '',
  phone: address.phone || address.phoneNumber || '',
  provinceName: address.provinceName || address.province || '',
  provinceCode: address.provinceCode || address.province_code || '',
  cityName: address.cityName || address.city || '',
  cityCode: address.cityCode || address.city_code || '',
  districtName: address.districtName || address.district || '',
  districtCode: address.districtCode || address.district_code || '',
  detailAddress: address.detailAddress || address.detail || '',
  addressTag: address.addressTag || address.tag || '',
  isDefault: normalizeBoolean(address.isDefault),
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
  }).then((data = {}) => normalizeAddressItem(data));
}

export function updateDeliveryAddress(addressId, payload = {}) {
  if (!addressId) {
    return Promise.reject(new Error('缺少地址ID'));
  }
  if (config.useMock) {
    return Promise.resolve(normalizeAddressItem({ ...payload, id: addressId, addressId }));
  }
  return request({
    url: `/api/v1/member/addresses/${addressId}`,
    method: 'PUT',
    needAuth: true,
    data: buildAddressPayload(payload),
  }).then((data = {}) => normalizeAddressItem(data));
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
