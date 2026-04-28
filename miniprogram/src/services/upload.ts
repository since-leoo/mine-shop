import Taro from '@tarojs/taro';
import { config } from '../config';
import { isMiniProgram } from '../common/platform';
import { buildSignatureHeaders } from './_utils/signature';

function buildUploadHeaders() {
  const storageKey = config.tokenStorageKey || 'accessToken';
  const token = Taro.getStorageSync(storageKey);
  const signatureClient = isMiniProgram()
    ? config.apiSignature.clients.miniapp
    : config.apiSignature.clients.h5;

  return {
    Authorization: token ? `Bearer ${token}` : '',
    ...buildSignatureHeaders({
      method: 'POST',
      path: '/api/v1/upload/image',
      queryString: '',
      bodyString: '',
      clientId: signatureClient.clientId,
      secret: signatureClient.secret,
    }),
  };
}

export function uploadImage(filePath: string) {
  const baseUrl = config.apiBaseUrl || '';
  const normalizedBase = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;

  return new Promise<string>((resolve, reject) => {
    Taro.uploadFile({
      url: `${normalizedBase}/api/v1/upload/image`,
      filePath,
      name: 'file',
      header: buildUploadHeaders(),
      success(res) {
        try {
          const body = JSON.parse(res.data);
          if (body.code === 200 && body.data?.url) {
            resolve(body.data.url);
            return;
          }
          reject({ msg: body.message || 'Upload failed' });
        } catch (_error) {
          reject({ msg: 'Upload response parse failed' });
        }
      },
      fail(error) {
        reject({ msg: error.errMsg || 'Upload failed' });
      },
    });
  });
}
