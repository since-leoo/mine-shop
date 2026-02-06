import { config } from '../../config/index';
import { request } from '../request';

const normalizeGender = (gender) => {
  if (typeof gender === 'number') {
    return gender;
  }
  if (gender === 'male') {
    return 1;
  }
  if (gender === 'female') {
    return 2;
  }
  return 0;
};

/** 获取个人中心信息 */
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
    const profile = data.member || {};
    return {
      avatarUrl: profile.avatarUrl || '',
      nickName: profile.nickName || '',
      phoneNumber: profile.phoneNumber || '',
      gender: normalizeGender(profile.gender),
    };
  });
}
