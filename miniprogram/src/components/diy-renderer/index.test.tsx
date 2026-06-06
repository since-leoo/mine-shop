import { describe, expect, it, vi } from 'vitest';

vi.mock('@tarojs/components', () => ({
  View: ({ children }: { children?: unknown }) => children || 'View',
}));

const componentNames = [
  'Banner',
  'QuickNav',
  'ImageAd',
  'ProductGroup',
  'TitleBar',
  'Gap',
  'Divider',
  'NoticeBar',
  'CouponGroup',
  'SeckillGroup',
  'GroupBuyGroup',
  'ProductRank',
  'SearchBar',
  'ShopInfo',
  'RichText',
  'ImageCube',
];

componentNames.forEach((name) => {
  vi.mock(`../diy/${name}`, () => ({
    default: () => name,
  }));
});

import { renderDiyComponent } from './index';

describe('DIY 渲染器注册表', () => {
  it('已注册二期组件类型', () => {
    [
      'notice-bar',
      'coupon-group',
      'seckill-group',
      'group-buy-group',
      'product-rank',
      'search-bar',
      'shop-info',
      'rich-text',
      'image-cube',
    ].forEach((type) => {
      expect(renderDiyComponent({ id: type, type })).not.toBeNull();
    });
  });

  it('禁用组件不会渲染', () => {
    expect(renderDiyComponent({ id: 'notice', type: 'notice-bar', enabled: false })).toBeNull();
  });

  it('未知组件会跳过', () => {
    expect(renderDiyComponent({ id: 'unknown', type: 'unknown' })).toBeNull();
  });
});
