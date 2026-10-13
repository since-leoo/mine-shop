import Taro from '@tarojs/taro';
import { ensureAuthenticated } from '../../../../common/auth';
import { DiyLink } from './types';

const TAB_PAGES = new Set([
  '/pages/home/index',
  '/pages/category/index',
  '/pages/cart/index',
  '/pages/usercenter/index',
]);

function buildQuery(params?: Record<string, any>): string {
  if (!params) return '';
  const query = Object.keys(params)
    .filter((key) => params[key] !== undefined && params[key] !== null && params[key] !== '')
    .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(String(params[key]))}`)
    .join('&');

  return query ? `?${query}` : '';
}

export function resolveDiyLink(link?: DiyLink): string {
  if (!link || !link.type) return '';

  if (link.type === 'none') return '';

  if (link.type === 'page') {
    const path = link.path || link.url || '';
    const query = buildQuery(link.params);
    return path ? `${path}${path.includes('?') ? query.replace(/^\?/, '&') : query}` : '';
  }

  if (link.type === 'url') {
    return link.url || link.path || '';
  }

  if (link.type === 'product' && link.id) {
    return `/pages/goods/details/index?spuId=${encodeURIComponent(String(link.id))}`;
  }

  if (link.type === 'category' && link.id) {
    return `/pages/goods/list/index?categoryId=${encodeURIComponent(String(link.id))}`;
  }

  if (link.type === 'coupon') {
    return link.id ? `/pages/coupon/coupon-detail/index?id=${encodeURIComponent(String(link.id))}` : '/pages/coupon/coupon-center/index';
  }

  if (link.type === 'group_buy') {
    return link.id ? `/pages/promotion/group-buy/index?id=${encodeURIComponent(String(link.id))}` : '/pages/promotion/group-buy/index';
  }

  if (link.type === 'seckill') {
    return link.id ? `/pages/promotion/detail/index?activityId=${encodeURIComponent(String(link.id))}` : '/pages/promotion/detail/index';
  }

  return '';
}

interface NavigateDiyLinkOptions {
  needLogin?: boolean;
}

function runNavigate(url: string): void {
  const path = url.split('?')[0];
  if (TAB_PAGES.has(path)) {
    Taro.switchTab({ url: path });
    return;
  }

  if (/^https?:\/\//.test(url)) {
    const env = Taro.getEnv?.();
    if (env === Taro.ENV_TYPE?.WEB && typeof window !== 'undefined') {
      window.location.href = url;
    } else {
      Taro.showToast({ title: '请在浏览器中打开此链接', icon: 'none' });
    }
    return;
  }

  Taro.navigateTo({ url });
}

export function navigateDiyLink(link?: DiyLink, options: NavigateDiyLinkOptions = {}): void {
  const url = resolveDiyLink(link);
  if (!url) return;

  if (options.needLogin) {
    ensureAuthenticated()
      .then(() => runNavigate(url))
      .catch(() => undefined);
    return;
  }

  runNavigate(url);
}
