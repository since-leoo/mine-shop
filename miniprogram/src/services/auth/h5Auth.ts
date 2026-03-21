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

export function sendVerificationCode(phone: string, scene: 'register' | 'forgot_password') {
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
