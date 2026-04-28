import Taro from '@tarojs/taro';
import { config } from '../config';
import {
  ensureAuthenticated,
  getStoredMemberProfile,
  getStoredToken,
  clearAuthStorage,
} from '../common/auth';
import { redirectToLogin } from '../common/auth-guard';
import { isH5, isMiniProgram } from '../common/platform';
import { buildCanonicalJson, buildQueryString, buildSignatureHeaders } from './_utils/signature';

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

function getSignatureClient() {
  return isMiniProgram()
    ? config.apiSignature.clients.miniapp
    : config.apiSignature.clients.h5;
}

function splitPathAndQuery(path: string): { path: string; queryString: string } {
  const [pathname, query = ''] = path.split('?');
  return { path: pathname || '/', queryString: query };
}

function buildSignedRequestPayload(
  url: string,
  method: 'GET' | 'POST' | 'PUT' | 'DELETE',
  data: Record<string, any>,
) {
  const normalizedPath = typeof url === 'string' && url.startsWith('/') ? url : `/${url || ''}`;
  const snakeData = toSnakeCase(data);
  const signatureClient = getSignatureClient();
  const { path, queryString: initialQueryString } = splitPathAndQuery(normalizedPath);
  const requestQueryString = (method === 'GET' || method === 'DELETE') ? buildQueryString(snakeData) : '';
  const finalQueryString = [initialQueryString, requestQueryString].filter(Boolean).join('&');
  const bodyString = (method === 'GET' || method === 'DELETE') ? '' : buildCanonicalJson(snakeData);

  return {
    finalUrl: `${getBaseUrl()}${path}${finalQueryString ? `?${finalQueryString}` : ''}`,
    requestData: (method === 'GET' || method === 'DELETE') ? undefined : bodyString,
    signatureHeaders: buildSignatureHeaders({
      method,
      path,
      queryString: finalQueryString,
      bodyString,
      clientId: signatureClient.clientId,
      secret: signatureClient.secret,
    }),
  };
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
        return Promise.reject({ code: 401, msg: 'Login state unavailable', __authError: true });
      }
      return token;
    })
    .catch((error) => Promise.reject({
      code: error?.code || 401,
      msg: error?.message || error?.msg || 'Login failed',
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
  const { finalUrl, requestData, signatureHeaders } = buildSignedRequestPayload(url, method, data);

  const execRequest = (): Promise<any> =>
    new Promise((resolve, reject) => {
      Taro.request({
        url: finalUrl,
        method,
        data: requestData,
        header: buildHeaders({ ...signatureHeaders, ...header }, needAuth),
        timeout: DEFAULT_TIMEOUT,
        success(res) {
          const { statusCode, data: body } = res;
          if (statusCode >= 200 && statusCode < 300 && body && body.code === 200) {
            resolve(toCamelCase(body.data));
            return;
          }
          reject({
            code: (body && body.code) || statusCode,
            msg: (body && body.message) || 'Request failed',
            data: toCamelCase(body && body.data),
          });
        },
        fail(error) {
          reject({ code: -1, msg: (error && error.errMsg) || 'Network error' });
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
