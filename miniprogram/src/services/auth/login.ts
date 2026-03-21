import { request } from '../request';

export function miniProgramLogin({
  code,
  encryptedData = '',
  iv = '',
  openid = '',
}: {
  code: string;
  encryptedData?: string;
  iv?: string;
  openid?: string;
}) {
  if (!code) {
    return Promise.reject(new Error('缺少登录凭证 code'));
  }
  return request({
    url: '/api/v1/login/miniApp',
    method: 'POST',
    data: {
      code,
      encrypted_data: encryptedData,
      iv,
      openid,
    },
  });
}
