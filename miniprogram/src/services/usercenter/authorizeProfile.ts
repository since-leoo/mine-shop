import { config } from '../../config';
import { request } from '../request';

/** 授权头像昵称 */
export function authorizeProfile(data: any) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { genSimpleUserInfo } = require('../../model/usercenter');
    return delay().then(() => ({
      ...genSimpleUserInfo(),
      ...data,
    }));
  }
  return request({
    url: '/api/v1/member/profile/authorize',
    method: 'POST',
    data,
    needAuth: true,
  });
}

/** 绑定手机号 */
export function bindPhoneNumber(code: string) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    return delay().then(() => ({
      phoneNumber: '134****8888',
      purePhoneNumber: '13438358888',
      countryCode: '86',
    }));
  }
  return request({
    url: '/api/v1/member/phone/bind',
    method: 'POST',
    data: { code },
    needAuth: true,
  });
}
