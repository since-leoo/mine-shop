import { request } from '../request';
import { notifyAuthResult, prepareAuthPayload } from '../../common/plugin-hooks';

export async function miniProgramLogin({
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
  const result = await request({
    url: '/api/v1/login/miniApp',
    method: 'POST',
    data: await prepareAuthPayload('mini_program', {
      code,
      encrypted_data: encryptedData,
      iv,
      openid,
    }),
  });
  notifyAuthResult(result);
  return result;
}
