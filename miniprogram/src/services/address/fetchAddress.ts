import { config } from '../../config';
import { request } from '../request';

/** 获取收货地址详情 */
export function fetchDeliveryAddress(id: number | string = 0) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { genAddress } = require('../../model/address');
    return delay().then(() => genAddress(id));
  }
  return request({ url: `/api/v1/member/addresses/${id}`, method: 'GET', needAuth: true });
}

/** 获取收货地址列表 */
export function fetchDeliveryAddressList(len = 10) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { genAddressList } = require('../../model/address');
    return delay().then(() =>
      genAddressList(len).map((address: any) => ({
        ...address,
        phoneNumber: address.phone,
        address: `${address.provinceName}${address.cityName}${address.districtName}${address.detailAddress}`,
        tag: address.addressTag,
      })),
    );
  }
  return request({ url: '/api/v1/member/addresses', method: 'GET', needAuth: true }).then((data: any = {}) => {
    const list = Array.isArray(data) ? data : data.list || [];
    return list.map((addr: any) => ({
      ...addr,
      phoneNumber: addr.phone || addr.phoneNumber,
      address: addr.address || `${addr.provinceName || ''}${addr.cityName || ''}${addr.districtName || ''}${addr.detailAddress || ''}`,
      tag: addr.addressTag || '',
    }));
  });
}

/** 创建收货地址 */
export function createAddress(data: any) {
  return request({ url: '/api/v1/member/addresses', method: 'POST', data, needAuth: true });
}

/** 更新收货地址 */
export function updateAddress(id: number | string, data: any) {
  return request({ url: `/api/v1/member/addresses/${id}`, method: 'PUT', data, needAuth: true });
}

/** 删除收货地址 */
export function deleteAddress(id: number | string) {
  return request({ url: `/api/v1/member/addresses/${id}`, method: 'DELETE', needAuth: true });
}
