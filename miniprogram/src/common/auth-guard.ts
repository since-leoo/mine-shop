import Taro from '@tarojs/taro';
import { isH5 } from './platform';

export const LOGIN_PAGE = '/pages/auth/login/index';
const LOGIN_REDIRECT_STORAGE_KEY = 'h5LoginRedirect';
const DEFAULT_AFTER_LOGIN_PAGE = '/pages/usercenter/index';
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

function normalizeRedirectTarget(url?: string): string {
  const cleanUrl = safeDecodeUrl((url || '').trim());
  if (!cleanUrl || cleanUrl === LOGIN_PAGE || cleanUrl.startsWith(`${LOGIN_PAGE}?`)) {
    return DEFAULT_AFTER_LOGIN_PAGE;
  }

  if (!cleanUrl.startsWith('/') || cleanUrl.startsWith('//')) {
    return DEFAULT_AFTER_LOGIN_PAGE;
  }

  return cleanUrl;
}

function buildCurrentRoute(): string {
  const pages = Taro.getCurrentPages();
  const current = pages[pages.length - 1] as any;
  const route = current?.route ? `/${current.route.replace(/^\/+/, '')}` : DEFAULT_AFTER_LOGIN_PAGE;
  const options = current?.options || {};
  const query = Object.keys(options)
    .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(options[key] ?? '')}`)
    .join('&');

  return query ? `${route}?${query}` : route;
}

export function redirectToLogin(redirect?: string) {
  if (!isH5()) return;

  const target = normalizeRedirectTarget(redirect || buildCurrentRoute());
  Taro.setStorageSync(LOGIN_REDIRECT_STORAGE_KEY, target);
  const url = `${LOGIN_PAGE}?redirect=${encodeURIComponent(target)}`;

  const pages = Taro.getCurrentPages();
  const current = pages[pages.length - 1] as any;
  const currentRoute = current?.route ? `/${current.route.replace(/^\/+/, '')}` : '';
  if (currentRoute === LOGIN_PAGE) return;

  Taro.navigateTo({ url }).catch(() => {
    Taro.redirectTo({ url }).catch(() => {
      Taro.reLaunch({ url });
    });
  });
}

export async function navigateAfterLogin(target?: string): Promise<void> {
  const cachedTarget = Taro.getStorageSync(LOGIN_REDIRECT_STORAGE_KEY) || '';
  const cleanUrl = normalizeRedirectTarget(target || cachedTarget || DEFAULT_AFTER_LOGIN_PAGE);
  const path = cleanUrl.split('?')[0];
  const isTabPage = TAB_PAGES.has(path);
  const url = isTabPage ? path : cleanUrl;

  try {
    if (isTabPage && isH5()) {
      // H5 的 Tab 缓存可能保留登录前的页面实例，登录后重新创建页面栈。
      await Taro.reLaunch({ url });
    } else if (isTabPage) {
      await Taro.switchTab({ url });
    } else {
      await Taro.redirectTo({ url });
    }
  } catch {
    await Taro.reLaunch({ url });
  }

  // 导航失败时保留目标，用户重试仍可回到原页面；调用方等待真正跳转完成。
  Taro.removeStorageSync(LOGIN_REDIRECT_STORAGE_KEY);
}
