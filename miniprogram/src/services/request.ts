import Taro from '@tarojs/taro';
import { config } from '../config';
import {
  ensureAuthenticated,
  getStoredMemberProfile,
  getStoredToken,
  clearAuthStorage,
} from '../common/auth';
import { redirectToLogin } from '../common/auth-guard';
import { isH5 } from '../common/platform';

const DEFAULT_TIMEOUT = 15000;

function camelToSnake(str: string): string {
  return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
}

function snakeToCamel(str: string): string {
  return str.replace(/_([a-z])/g, (_, letter: string) => letter.toUpperCase());
}

function toSnakeCase(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (Array.isArray(obj)) return obj.map(toSnakeCase);
  if (typeof obj === 'object' && obj.constructor === Object) {
    const result: Record<string, any> = {};
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

function toCamelCase(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (Array.isArray(obj)) return obj.map(toCamelCase);
  if (typeof obj === 'object' && obj.constructor === Object) {
    const result: Record<string, any> = {};
    Object.keys(obj).forEach((key) => {
      result[snakeToCamel(key)] = toCamelCase(obj[key]);
    });
    return result;
  }
  return obj;
}

function buildHeaders(extraHeaders: Record<string, string> = {}, needAuth = false) {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...extraHeaders,
  };

  if (needAuth) {
    const storageKey = config.tokenStorageKey || 'accessToken';
    const token = Taro.getStorageSync(storageKey);
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  return headers;
}

function getBaseUrl(): string {
  const base = config.apiBaseUrl || '';
  return base.endsWith('/') ? base.slice(0, -1) : base;
}

function ensureAuthToken(forceLogin = false): Promise<string> {
  if (!forceLogin) {
    const token = getStoredToken();
    if (token) return Promise.resolve(token);
  }

  const profile = getStoredMemberProfile();
  return ensureAuthenticated({ force: forceLogin, openid: profile?.openid || '', redirect: true })
    .then(() => {
      const token = getStoredToken();
      if (!token) {
        return Promise.reject({ code: 401, msg: 'µÇÂ¼×´Ì¬»ñÈ¡Ê§°Ü£¬ÇëÖØÊÔ', __authError: true });
      }
      return token;
    })
    .catch((error) => Promise.reject({
      code: error?.code || 401,
      msg: error?.message || error?.msg || 'µÇÂ¼Ê§°Ü£¬ÇëÉÔºóÖØÊÔ',
      __authError: true,
    }));
}

const isAuthError = (error: any): boolean => {
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

interface RequestOptions {
  url: string;
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  data?: Record<string, any>;
  header?: Record<string, string>;
  needAuth?: boolean;
}

export function request({ url, method = 'GET', data = {}, header = {}, needAuth = false }: RequestOptions): Promise<any> {
  const baseUrl = getBaseUrl();
  const normalizedPath = typeof url === 'string' && url.startsWith('/') ? url : `/${url || ''}`;
  const finalUrl = baseUrl + normalizedPath;
  const snakeData = toSnakeCase(data);

  const execRequest = (): Promise<any> =>
    new Promise((resolve, reject) => {
      Taro.request({
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
            msg: (body && body.message) || '½Ó¿ÚÇëÇóÊ§°Ü',
            data: toCamelCase(body && body.data),
          });
        },
        fail(error) {
          reject({ code: -1, msg: (error && error.errMsg) || 'ÍøÂçÒì³£' });
        },
      });
    });

  if (!needAuth) return execRequest();

  const attemptAuthorizedRequest = (attempt = 0): Promise<any> =>
    ensureAuthToken(attempt > 0)
      .then(() => execRequest().catch((error) => {
        if (isAuthError(error) && attempt < 1) {
          clearAuthStorage();
          if (isH5()) {
            redirectToLogin();
          }
          return attemptAuthorizedRequest(attempt + 1);
        }
        return Promise.reject(error);
      }))
      .catch((error) => {
        if (isAuthError(error) && attempt < 1) {
          clearAuthStorage();
          if (isH5()) {
            redirectToLogin();
          }
          return attemptAuthorizedRequest(attempt + 1);
        }
        return Promise.reject(error);
      });

  return attemptAuthorizedRequest();
}
