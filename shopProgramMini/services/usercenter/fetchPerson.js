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
      province: address.province,
      province_code: address.province_code,
      city: address.city,
      city_code: address.city_code,
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
      avatarUrl: profile.avatar || '',
      nickName: profile.nickname || '',
      phoneNumber: profile.phone || '',
      gender: normalizeGender(profile.gender),
      levelName: profile.level_name || null,
      level: profile.level || null,
      balance: profile.balance || 0,
      points: profile.points || 0,
      authorizedProfile: Boolean(profile.authorized_profile),
    };
  });
}
