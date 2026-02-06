import { request } from '../request';

export function bindPhoneNumber(code) {
  if (!code) {
    return Promise.reject(new Error('缺少手机号授权 code'));
  }

  return request({
    url: '/api/v1/member/phone/bind',
    method: 'POST',
    data: { code },
    needAuth: true,
  });
}
