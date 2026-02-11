import { config } from '../../config/index';
import { request } from '../request';

/** 获取个人中心信息 (mock) */
function mockFetchPerson() {
  const { delay } = require('../_utils/delay');
  const { genSimpleUserInfo } = require('../../model/usercenter');
  const { genAddress } = require('../../model/address');
  const address = genAddress();
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
  }).then((data = {}) => {
    const member = data.member || data;
    return {
      avatarUrl: member.avatar || '',
      nickName: member.nickname || '',
      phoneNumber: member.phone || '',
      gender: member.gender || 0,
    };
  });
}
