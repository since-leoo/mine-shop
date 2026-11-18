import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
  storage: new Map<string, string>(),
  getStorageSync: vi.fn(),
  removeStorageSync: vi.fn(),
  switchTab: vi.fn(),
  redirectTo: vi.fn(),
  reLaunch: vi.fn(),
  isH5: vi.fn(),
  getCurrentPages: vi.fn(),
  setStorageSync: vi.fn(),
  navigateTo: vi.fn(),
}));

vi.mock('@tarojs/taro', () => ({
  default: {
    getStorageSync: mocks.getStorageSync,
    removeStorageSync: mocks.removeStorageSync,
    switchTab: mocks.switchTab,
    redirectTo: mocks.redirectTo,
    reLaunch: mocks.reLaunch,
    getCurrentPages: mocks.getCurrentPages,
    setStorageSync: mocks.setStorageSync,
    navigateTo: mocks.navigateTo,
  },
}));
vi.mock('./platform', () => ({ isH5: mocks.isH5 }));

import { navigateAfterLogin, redirectToLogin } from './auth-guard';

const REDIRECT_KEY = 'h5LoginRedirect';

beforeEach(() => {
  vi.resetAllMocks();
  mocks.storage.clear();
  mocks.isH5.mockReturnValue(false);
  mocks.getCurrentPages.mockReturnValue([]);
  mocks.navigateTo.mockResolvedValue({});
  mocks.getStorageSync.mockImplementation((key: string) => mocks.storage.get(key) || '');
  mocks.removeStorageSync.mockImplementation((key: string) => mocks.storage.delete(key));
  mocks.switchTab.mockResolvedValue({});
  mocks.redirectTo.mockResolvedValue({});
  mocks.reLaunch.mockResolvedValue({});
});

describe('redirectToLogin', () => {
  it.each(['pages/cart/index', '/pages/cart/index'])('兼容当前页面路径 %s，不生成双斜线回跳地址', (route) => {
    mocks.isH5.mockReturnValue(true);
    mocks.getCurrentPages.mockReturnValue([{ route, options: {} }]);
    redirectToLogin();
    expect(mocks.setStorageSync).toHaveBeenCalledExactlyOnceWith(REDIRECT_KEY, '/pages/cart/index');
    expect(mocks.navigateTo).toHaveBeenCalledExactlyOnceWith({ url: '/pages/auth/login/index?redirect=%2Fpages%2Fcart%2Findex' });
  });

  it('H5 当前已经在登录页时不重复打开登录页', () => {
    mocks.isH5.mockReturnValue(true);
    mocks.getCurrentPages.mockReturnValue([{ route: '/pages/auth/login/index' }]);
    redirectToLogin('/pages/cart/index');
    expect(mocks.navigateTo).not.toHaveBeenCalled();
  });
});

describe('navigateAfterLogin', () => {
  it('拒绝双斜线回跳地址', async () => {
    await navigateAfterLogin('//example.com/path');
    expect(mocks.switchTab).toHaveBeenCalledExactlyOnceWith({ url: '/pages/usercenter/index' });
  });

  it('H5 登录后重新创建 Tab 页面，清除登录前的页面缓存', async () => {
    mocks.isH5.mockReturnValue(true);
    mocks.storage.set(REDIRECT_KEY, '/pages/cart/index?source=login');
    await navigateAfterLogin();
    expect(mocks.reLaunch).toHaveBeenCalledExactlyOnceWith({ url: '/pages/cart/index' });
    expect(mocks.switchTab).not.toHaveBeenCalled();
    expect(mocks.storage.has(REDIRECT_KEY)).toBe(false);
  });

  it('没有显式或缓存目标时进入个人中心', async () => {
    await expect(navigateAfterLogin()).resolves.toBeUndefined();
    expect(mocks.switchTab).toHaveBeenCalledExactlyOnceWith({ url: '/pages/usercenter/index' });
    expect(mocks.redirectTo).not.toHaveBeenCalled();
    expect(mocks.reLaunch).not.toHaveBeenCalled();
    expect(mocks.removeStorageSync).toHaveBeenCalledExactlyOnceWith(REDIRECT_KEY);
  });

  it('使用缓存的受保护页面并保留其查询参数', async () => {
    const target = '/pages/order/order-detail/index?orderNo=ORDER-2026&source=payment';
    mocks.storage.set(REDIRECT_KEY, target);
    await navigateAfterLogin();
    expect(mocks.redirectTo).toHaveBeenCalledExactlyOnceWith({ url: target });
    expect(mocks.switchTab).not.toHaveBeenCalled();
    expect(mocks.storage.has(REDIRECT_KEY)).toBe(false);
  });

  it('显式目标优先于缓存目标，Tab 导航仅使用页面路径', async () => {
    mocks.storage.set(REDIRECT_KEY, '/pages/order/order-list/index?status=paid');
    await navigateAfterLogin('/pages/cart/index?source=login');
    expect(mocks.switchTab).toHaveBeenCalledExactlyOnceWith({ url: '/pages/cart/index' });
    expect(mocks.redirectTo).not.toHaveBeenCalled();
    expect(mocks.storage.has(REDIRECT_KEY)).toBe(false);
  });

  it('解码登录页携带的完整目标，保留非 Tab 页内已编码的查询值', async () => {
    const target = '/pages/goods/details/index?id=42&sku=red%2Flarge&note=a%26b';
    await navigateAfterLogin(encodeURIComponent(target));
    expect(mocks.redirectTo).toHaveBeenCalledExactlyOnceWith({ url: target });
    expect(mocks.switchTab).not.toHaveBeenCalled();
  });

  it('等待实际导航成功后才清除缓存', async () => {
    let finish!: () => void;
    mocks.storage.set(REDIRECT_KEY, '/pages/usercenter/index');
    mocks.switchTab.mockImplementation(() => new Promise<void>((resolve) => { finish = resolve; }));
    const navigation = navigateAfterLogin();
    expect(mocks.storage.get(REDIRECT_KEY)).toBe('/pages/usercenter/index');
    expect(mocks.removeStorageSync).not.toHaveBeenCalled();
    finish();
    await expect(navigation).resolves.toBeUndefined();
    expect(mocks.storage.has(REDIRECT_KEY)).toBe(false);
  });

  it('switchTab 失败时降级 reLaunch，成功后清理目标', async () => {
    mocks.storage.set(REDIRECT_KEY, '/pages/usercenter/index');
    mocks.switchTab.mockRejectedValue(new Error('switchTab failed'));
    mocks.reLaunch.mockImplementation(async ({ url }: { url: string }) => {
      expect(mocks.storage.get(REDIRECT_KEY)).toBe(url);
      expect(mocks.removeStorageSync).not.toHaveBeenCalled();
    });
    await expect(navigateAfterLogin()).resolves.toBeUndefined();
    expect(mocks.reLaunch).toHaveBeenCalledExactlyOnceWith({ url: '/pages/usercenter/index' });
    expect(mocks.storage.has(REDIRECT_KEY)).toBe(false);
  });

  it('全部导航失败时向调用方抛错并保留目标，下一次可重试', async () => {
    const target = '/pages/order/order-detail/index?orderNo=RETRY-1';
    const failure = new Error('reLaunch failed');
    mocks.storage.set(REDIRECT_KEY, target);
    mocks.redirectTo.mockRejectedValueOnce(new Error('redirectTo failed'));
    mocks.reLaunch.mockRejectedValueOnce(failure);
    await expect(navigateAfterLogin()).rejects.toBe(failure);
    expect(mocks.reLaunch).toHaveBeenCalledExactlyOnceWith({ url: target });
    expect(mocks.removeStorageSync).not.toHaveBeenCalled();
    expect(mocks.storage.get(REDIRECT_KEY)).toBe(target);
    await expect(navigateAfterLogin()).resolves.toBeUndefined();
    expect(mocks.redirectTo).toHaveBeenLastCalledWith({ url: target });
    expect(mocks.storage.has(REDIRECT_KEY)).toBe(false);
  });
});
