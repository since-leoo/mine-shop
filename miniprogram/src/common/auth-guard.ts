import Taro from '@tarojs/taro';
import { isH5 } from './platform';

export const LOGIN_PAGE = '/pages/auth/login/index';
const TAB_PAGES = new Set([
  '/pages/home/index',
  '/pages/category/index',
  '/pages/cart/index',
  '/pages/usercenter/index',
]);

function safeDecodeUrl(url: string): string {
  try {
    return decodeURIComponent(url);
  } catch {
    return url;
  }
}

function buildCurrentRoute(): string {
  const pages = Taro.getCurrentPages();
  const current = pages[pages.length - 1] as any;
  const route = current?.route ? `/${current.route}` : '/pages/home/index';
  const options = current?.options || {};
  const query = Object.keys(options)
    .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(options[key] ?? '')}`)
    .join('&');

  return query ? `${route}?${query}` : route;
}

export function redirectToLogin(redirect?: string) {
  if (!isH5()) return;

  const target = redirect || buildCurrentRoute();
  const url = `${LOGIN_PAGE}?redirect=${encodeURIComponent(target)}`;

  const pages = Taro.getCurrentPages();
  const current = pages[pages.length - 1] as any;
  const currentRoute = current?.route ? `/${current.route}` : '';
  if (currentRoute === LOGIN_PAGE) return;

  Taro.navigateTo({ url }).catch(() => {
    Taro.redirectTo({ url }).catch(() => {
      Taro.reLaunch({ url });
    });
  });
}

export function navigateAfterLogin(target?: string) {
  const url = target || '/pages/usercenter/index';
  const cleanUrl = safeDecodeUrl(url);
  const path = cleanUrl.split('?')[0];

  if (TAB_PAGES.has(path)) {
    Taro.switchTab({ url: path }).catch(() => {
      Taro.reLaunch({ url: path });
    });
    return;
  }

  Taro.redirectTo({ url: cleanUrl }).catch(() => {
    Taro.reLaunch({ url: cleanUrl });
  });
}
