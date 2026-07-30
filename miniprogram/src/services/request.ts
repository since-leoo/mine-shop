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
import { AUTH_REQUIRED_MESSAGE, isUnauthorizedError, normalizeErrorMessage, showErrorToast } from '../common/error-feedback';
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
        return Promise.reject({ code: 401, msg: AUTH_REQUIRED_MESSAGE, __authError: true });
      }
      return token;
    })
    .catch((error) => Promise.reject({
      code: error?.code || 401,
      msg: AUTH_REQUIRED_MESSAGE,
      __authError: true,
    }));
}

function createRequestError(error: {
  code?: string | number;
  msg?: string;
  data?: any;
  statusCode?: number;
  __authError?: boolean;
  __redirectHandled?: boolean;
}) {
  return {
    code: error.code ?? error.statusCode ?? -1,
    msg: normalizeErrorMessage(error, '请求失败'),
    data: error.data,
    statusCode: error.statusCode,
    __authError: Boolean(error.__authError),
    __redirectHandled: Boolean(error.__redirectHandled),
  };
}

function handleUnauthorizedRedirect() {
  clearAuthStorage();
  showErrorToast(AUTH_REQUIRED_MESSAGE);
  if (isH5()) {
    redirectToLogin();
  }
}

function withUnauthorizedHandling(error: any) {
  if (error?.__redirectHandled) {
    return createRequestError(error);
  }

  handleUnauthorizedRedirect();
  return createRequestError({
    ...error,
    code: 401,
    msg: AUTH_REQUIRED_MESSAGE,
    __authError: true,
    __redirectHandled: true,
  });
}

function createRedirectAbortPromise<T = any>(): Promise<T> {
  return new Promise(() => {});
}

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
          reject(createRequestError({
            code: (body && (body.code ?? body.statusCode)) || statusCode,
            msg: (body && (body.message || body.msg)) || '',
            data: toCamelCase(body && body.data),
            statusCode,
            __authError: isUnauthorizedError({ code: (body && body.code) || statusCode }),
          }));
        },
        fail(error) {
          reject(createRequestError({
            code: -1,
            msg: (error && error.errMsg) || 'Network error',
          }));
        },
      });
    });

  if (!needAuth) return execRequest();

  const attemptAuthorizedRequest = (attempt = 0): Promise<any> =>
    ensureAuthToken(attempt > 0)
      .then(() => execRequest().catch((error) => {
        if (isUnauthorizedError(error)) {
          const authError = withUnauthorizedHandling(error);
          if (isH5()) {
            return createRedirectAbortPromise();
          }
          if (isMiniProgram() && attempt < 1) {
            return attemptAuthorizedRequest(attempt + 1);
          }
          return Promise.reject(authError);
        }
        return Promise.reject(error);
      }))
      .catch((error) => {
        if (isUnauthorizedError(error)) {
          const authError = withUnauthorizedHandling(error);
          if (isH5()) {
            return createRedirectAbortPromise();
          }
          if (isMiniProgram() && attempt < 1) {
            return attemptAuthorizedRequest(attempt + 1);
          }
          return Promise.reject(authError);
        }
        return Promise.reject(error);
      });

  return attemptAuthorizedRequest();
}
