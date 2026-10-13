import { config } from '../../config';
import { request } from '../request';

/** 获取个人中心信息 (mock) */
function mockFetchPerson() {
  const { delay } = require('../_utils/delay');
  const { genSimpleUserInfo } = require('../../model/usercenter');
  const address = {
    provinceName: '甘肃省',
    provinceCode: '620000',
    cityName: '甘南藏族自治州',
    cityCode: '623000',
  };
  return delay().then(() => ({
    ...genSimpleUserInfo(),
    address: {
      provinceName: address.provinceName,
      provinceCode: address.provinceCode,
      cityName: address.cityName,
      cityCode: address.cityCode,
    },
  }));
}

/** 获取个人中心信息 */
export function fetchPerson() {
  if (config.useMock) {
    return mockFetchPerson();
  }
  return request({
    url: '/api/v1/member/profile',
    method: 'GET',
    needAuth: true,
  }).then((data: any = {}) => {
    const member = data.member || data;
    return {
      avatarUrl: member.avatar || '',
      nickName: member.nickname || '',
      phoneNumber: member.phone || '',
      gender: member.gender ? ({ male: 1, female: 2 } as Record<string, number>)[member.gender] || 0 : 0,
    };
  });
}

/** 修改个人资料 */
export function updateProfile(data: any) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    return delay().then(() => ({}));
  }
  return request({
    url: '/api/v1/member/profile/update',
    method: 'POST',
    data,
    needAuth: true,
  });
}

/** 头像与售后、客服共用带 API 签名的上传入口。 */
export { uploadImage } from '../upload';
