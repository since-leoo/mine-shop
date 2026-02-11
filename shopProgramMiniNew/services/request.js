import { config } from '../config/index';
import {
  ensureMiniProgramLogin,
  getStoredMemberProfile,
  getStoredToken,
  clearAuthStorage,
} from '../common/auth';

const DEFAULT_TIMEOUT = 15000;

// ========== camelCase <-> snake_case 转换 ==========

function camelToSnake(str) {
  return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
}

function snakeToCamel(str) {
  return str.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
}

function toSnakeCase(obj) {
  if (obj === null || obj === undefined) return obj;
  if (Array.isArray(obj)) return obj.map(toSnakeCase);
  if (typeof obj === 'object' && obj.constructor === Object) {
    const result = {};
    Object.keys(obj).forEach((key) => {
      const val = obj[key];
      if (val !== null && val !== undefined) {
        result[camelToSnake(key)] = toSnakeCase(val);
      }
    });
    return result;
  }
  return obj;
}

function toCamelCase(obj) {
  if (obj === null || obj === undefined) return obj;
  if (Array.isArray(obj)) return obj.map(toCamelCase);
  if (typeof obj === 'object' && obj.constructor === Object) {
    const result = {};
    Object.keys(obj).forEach((key) => {
      result[snakeToCamel(key)] = toCamelCase(obj[key]);
    });
    return result;
  }
  return obj;
}

function buildHeaders(extraHeaders = {}, needAuth = false) {
  const headers = {
    'Content-Type': 'application/json',
    ...extraHeaders,
  };
  if (needAuth) {
    const storageKey = config.tokenStorageKey || 'accessToken';
    const token = wx.getStorageSync(storageKey);
    if (token) {
      headers.Authorization = 'Bearer ' + token;
    }
  }
  return headers;
}

function getBaseUrl() {
  const base = config.apiBaseUrl || '';
  return base.endsWith('/') ? base.slice(0, -1) : base;
}

function ensureAuthToken(forceLogin = false) {
  if (!forceLogin) {
    const token = getStoredToken();
    if (token) return Promise.resolve(token);
  }
  const profile = getStoredMemberProfile();
  return ensureMiniProgramLogin({ force: forceLogin, openid: profile?.openid || '' })
    .then(() => {
      const token = getStoredToken();
      if (!token) {
        return Promise.reject({ code: 401, msg: '登录状态获取失败，请重试', __authError: true });
      }
      return token;
    })
    .catch((error) =>
      Promise.reject({
        code: error?.code || 401,
        msg: error?.message || error?.msg || '登录失败，请稍后重试',
        __authError: true,
      }),
    );
}

const isAuthError = (error) => {
  if (!error) return false;
  if (error.__authError) return true;
  const code = error.code;
  if (typeof code === 'number') return code === 401 || code === 419;
  if (typeof code === 'string') {
    const upper = code.toUpperCase();
    return upper === '401' || upper === 'TOKEN_EXPIRED' || upper === 'UNAUTHORIZED';
  }
  return false;
};

export function request({ url, method = 'GET', data = {}, header = {}, needAuth = false }) {
  const baseUrl = getBaseUrl();
  const normalizedPath = typeof url === 'string' && url.startsWith('/') ? url : `/${url || ''}`;
  const finalUrl = baseUrl + normalizedPath;
  const snakeData = toSnakeCase(data);

  const execRequest = () =>
    new Promise((resolve, reject) => {
      wx.request({
        url: finalUrl,
        method,
        data: snakeData,
        header: buildHeaders(header, needAuth),
        timeout: DEFAULT_TIMEOUT,
        success(res) {
          const { statusCode, data: body } = res;
          if (statusCode >= 200 && statusCode < 300 && body && body.code === 200) {
            resolve(toCamelCase(body.data));
            return;
          }
          reject({
            code: (body && body.code) || statusCode,
            msg: (body && body.message) || '接口请求失败',
            data: toCamelCase(body && body.data),
          });
        },
        fail(error) {
          reject({ code: -1, msg: (error && error.errMsg) || '网络异常' });
        },
      });
    });

  if (!needAuth) return execRequest();

  const attemptAuthorizedRequest = (attempt = 0) =>
    ensureAuthToken(attempt > 0)
      .then(() =>
        execRequest().catch((error) => {
          if (isAuthError(error) && attempt < 1) {
            clearAuthStorage();
            return attemptAuthorizedRequest(attempt + 1);
          }
          return Promise.reject(error);
        }),
      )
      .catch((error) => {
        if (isAuthError(error) && attempt < 1) {
          clearAuthStorage();
          return attemptAuthorizedRequest(attempt + 1);
        }
        return Promise.reject(error);
      });

  return attemptAuthorizedRequest();
}
