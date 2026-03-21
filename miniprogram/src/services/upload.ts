import Taro from '@tarojs/taro';
import { config } from '../config';

export function uploadImage(filePath: string) {
  const baseUrl = config.apiBaseUrl || '';
  const normalizedBase = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
  const storageKey = config.tokenStorageKey || 'accessToken';
  const token = Taro.getStorageSync(storageKey);

  return new Promise<string>((resolve, reject) => {
    Taro.uploadFile({
      url: `${normalizedBase}/api/v1/upload/image`,
      filePath,
      name: 'file',
      header: {
        Authorization: token ? `Bearer ${token}` : '',
      },
      success(res) {
        try {
          const body = JSON.parse(res.data);
          if (body.code === 200 && body.data?.url) {
            resolve(body.data.url);
            return;
          }
          reject({ msg: body.message || '上传失败' });
        }
        catch (_error) {
          reject({ msg: '上传响应解析失败' });
        }
      },
      fail(error) {
        reject({ msg: error.errMsg || '上传失败' });
      },
    });
  });
}
