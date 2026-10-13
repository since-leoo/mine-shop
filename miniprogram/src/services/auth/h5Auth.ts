import { request } from '../request';

export function fetchRegisterProtocols() {
  return request({
    url: '/api/v1/auth/register/protocols',
    method: 'GET',
  });
}

export function passwordLogin(phone: string, password: string) {
  return request({
    url: '/api/v1/login/h5Password',
    method: 'POST',
    data: { phone, password },
  });
}

export function sendVerificationCode(phone: string, scene: 'register' | 'forgot_password' | 'login') {
  return request({
    url: '/api/v1/auth/captcha',
    method: 'POST',
    data: { phone, scene },
  });
}

export function registerByPhone(data: {
  phone: string;
  password: string;
  passwordConfirmation: string;
  code: string;
}) {
  return request({
    url: '/api/v1/auth/register',
    method: 'POST',
    data,
  });
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
export function smsLogin(phone: string, code: string) {
  return request({ url: '/api/v1/login/h5Sms', method: 'POST', data: { phone, code } });
}
