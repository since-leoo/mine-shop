import { config } from '../config/index';
import { miniProgramLogin } from '../services/auth/login';

const TOKEN_STORAGE_KEY = config.tokenStorageKey || 'accessToken';
const REFRESH_TOKEN_STORAGE_KEY = 'refreshToken';
const MEMBER_PROFILE_KEY = 'memberProfile';

let sharedLoginPromise = null;

const persistAuthResponse = (response = {}) => {
  const { token, refreshToken, member } = response || {};
  if (token) {
    wx.setStorageSync(TOKEN_STORAGE_KEY, token);
  }
  if (refreshToken) {
    wx.setStorageSync(REFRESH_TOKEN_STORAGE_KEY, refreshToken);
  }
  if (member) {
    wx.setStorageSync(MEMBER_PROFILE_KEY, member);
  }
};

const runLoginRequest = ({ encryptedData = '', iv = '', openid = '' } = {}) => {
  return new Promise((resolve, reject) => {
    wx.login({
      timeout: 10000,
      success(loginRes) {
        const { code } = loginRes || {};
        if (!code) {
          reject(new Error('未获取到登录凭证 code'));
          return;
        }
        miniProgramLogin({ code, encryptedData, iv, openid })
          .then((response) => {
            persistAuthResponse(response);
            resolve(response);
          })
          .catch(reject);
      },
      fail(error) {
        reject(error);
      },
    });
  });
};

export const getStoredToken = () => wx.getStorageSync(TOKEN_STORAGE_KEY) || '';

export const clearAuthStorage = () => {
  wx.removeStorageSync(TOKEN_STORAGE_KEY);
  wx.removeStorageSync(REFRESH_TOKEN_STORAGE_KEY);
  wx.removeStorageSync(MEMBER_PROFILE_KEY);
};

export const getStoredMemberProfile = () => wx.getStorageSync(MEMBER_PROFILE_KEY) || null;

export const ensureMiniProgramLogin = (options = {}) => {
  const { force = false, encryptedData = '', iv = '', openid = '' } = options || {};
  if (!force) {
    const cachedToken = getStoredToken();
    if (cachedToken) {
      return Promise.resolve({ token: cachedToken });
    }
    if (!sharedLoginPromise) {
      sharedLoginPromise = runLoginRequest({ encryptedData, iv, openid }).finally(() => {
        sharedLoginPromise = null;
      });
    }
    return sharedLoginPromise;
  }
  return runLoginRequest({ encryptedData, iv, openid });
};
