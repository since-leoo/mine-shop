import { request } from '../request';
import { notifyAuthResult, prepareAuthPayload } from '../../common/plugin-hooks';

export function fetchRegisterProtocols() {
  return request({
    url: '/api/v1/auth/register/protocols',
    method: 'GET',
  });
}

export async function passwordLogin(phone: string, password: string) {
  const result = await request({
    url: '/api/v1/login/h5Password',
    method: 'POST',
    data: await prepareAuthPayload('h5_login', { phone, password }),
  });
  notifyAuthResult(result);
  return result;
}

export function sendVerificationCode(phone: string, scene: 'register' | 'forgot_password' | 'login') {
  return request({
    url: '/api/v1/auth/captcha',
    method: 'POST',
    data: { phone, scene },
  });
}

export async function registerByPhone(data: {
  phone: string;
  password: string;
  passwordConfirmation: string;
  code: string;
}) {
  const result = await request({
    url: '/api/v1/auth/register',
    method: 'POST',
    data: await prepareAuthPayload('h5_register', data),
  });
  notifyAuthResult(result);
  return result;
}

export function resetPasswordByPhone(data: {
  phone: string;
  password: string;
  passwordConfirmation: string;
  code: string;
}) {
  return request({
    url: '/api/v1/auth/forgotPassword',
    method: 'POST',
    data,
  });
}

/** 验证码登录与密码登录共用会员和登录后的导航流程。 */
export async function smsLogin(phone: string, code: string) {
  const result = await request({ url: '/api/v1/login/h5Sms', method: 'POST', data: await prepareAuthPayload('h5_login', { phone, code }) });
  notifyAuthResult(result);
  return result;
}
