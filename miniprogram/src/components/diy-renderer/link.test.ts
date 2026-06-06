import { describe, expect, it, vi } from 'vitest';

vi.mock('@tarojs/taro', () => ({
  default: {
    navigateTo: vi.fn(),
  },
}));

import Taro from '@tarojs/taro';
import { navigateDiyLink, resolveDiyLink } from './link';

describe('DIY 链接解析', () => {
  it('解析页面链接并拼接参数', () => {
    expect(resolveDiyLink({
      type: 'page',
      path: '/pages/goods/result/index',
      params: { keyword: 'tea' },
    })).toBe('/pages/goods/result/index?keyword=tea');
  });

  it('解析商品链接为商品详情页', () => {
    expect(resolveDiyLink({ type: 'product', id: 1001 })).toBe('/pages/goods/details/index?spuId=1001');
  });

  it('解析拼团链接到已注册页面', () => {
    expect(resolveDiyLink({ type: 'group_buy', id: 12 })).toBe('/pages/promotion/group-buy/index?id=12');
  });

  it('未知链接类型会被忽略', () => {
    expect(resolveDiyLink({ type: 'unknown', id: 1 })).toBe('');
  });

  it('存在有效链接时调用 Taro 跳转', () => {
    navigateDiyLink({ type: 'category', id: 9 });

    expect(Taro.navigateTo).toHaveBeenCalledWith({ url: '/pages/goods/result/index?categoryId=9' });
  });
});
