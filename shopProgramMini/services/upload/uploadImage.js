import { config } from '../../config/index';
import { getStoredToken } from '../../common/auth';

/**
 * 上传图片到后端，返回图片 URL
 * @param {string} filePath 本地临时文件路径
 * @returns {Promise<string>} 图片 URL
 */
export function uploadImage(filePath) {
  if (!filePath) {
    return Promise.reject(new Error('缺少文件路径'));
  }

  const baseUrl = (config.apiBaseUrl || '').replace(/\/$/, '');
  const token = getStoredToken();

  return new Promise((resolve, reject) => {
    wx.uploadFile({
      url: `${baseUrl}/api/v1/upload/image`,
      filePath,
      name: 'file',
      header: {
        Authorization: token ? `Bearer ${token}` : '',
      },
      success(res) {
        if (res.statusCode >= 200 && res.statusCode < 300) {
          try {
            const body = JSON.parse(res.data);
            if (body && body.code === 200 && body.data && body.data.url) {
              resolve(body.data.url);
              return;
            }
            reject({ code: body?.code, msg: body?.message || '上传失败' });
          } catch (e) {
            reject({ code: -1, msg: '解析上传响应失败' });
          }
        } else {
          reject({ code: res.statusCode, msg: '上传请求失败' });
        }
      },
      fail(error) {
        reject({ code: -1, msg: error?.errMsg || '网络异常' });
      },
    });
  });
}
