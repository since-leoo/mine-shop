import Taro from '@tarojs/taro';
import { redirectToLogin } from './auth-guard';

export const AUTH_REQUIRED_MESSAGE = '请先登录';
const NETWORK_ERROR_PATTERN = /NetworkError|Failed to fetch|Load failed|ERR_CONNECTION|network error/i;

let lastToastMessage = '';
let lastToastAt = 0;

function readMessage(error: any): string {
  if (!error) return '';
  if (typeof error === 'string') return error;
  return error.msg || error.message || error.errMsg || '';
}

export function normalizeErrorMessage(error: any, fallback = '请求失败'): string {
  const message = readMessage(error).trim();
  if (!message) return fallback;
  if (NETWORK_ERROR_PATTERN.test(message)) {
    return '网络异常，请稍后重试';
  }
  return message;
}

export function isUnauthorizedError(error: any): boolean {
  if (!error) return false;
  if (error.__authError) return true;

  const code = error.code ?? error.statusCode ?? error.status;
  if (typeof code === 'number') {
    return code === 401 || code === 419;
  }

  if (typeof code === 'string') {
    const normalized = code.toUpperCase();
    return normalized === '401' || normalized === '419' || normalized === 'TOKEN_EXPIRED' || normalized === 'UNAUTHORIZED';
  }

  return false;
}

export function showErrorToast(message: string, duration = 2500) {
  const normalizedMessage = (message || '请求失败').trim();
  const now = Date.now();

  if (normalizedMessage === lastToastMessage && now - lastToastAt < 1500) {
    return;
  }

  lastToastMessage = normalizedMessage;
  lastToastAt = now;

  Taro.showToast({
    title: normalizedMessage,
    icon: 'none',
    duration,
  });
}

export function handleUnhandledAppError(error: any): boolean {
  const message = normalizeErrorMessage(error, '');
  const hasKnownMessage = !!message;
  const isNetworkError = NETWORK_ERROR_PATTERN.test(readMessage(error));

  if (isUnauthorizedError(error)) {
    showErrorToast(AUTH_REQUIRED_MESSAGE);
    redirectToLogin();
    return true;
  }

  if (hasKnownMessage || isNetworkError) {
    showErrorToast(message || '网络异常，请稍后重试');
    return true;
  }

  return false;
}
