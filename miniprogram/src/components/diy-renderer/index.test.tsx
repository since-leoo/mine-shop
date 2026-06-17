import { describe, expect, it, vi } from 'vitest';

vi.mock('@tarojs/components', () => ({
  View: ({ children }: { children?: unknown }) => children || 'View',
}));

vi.mock('../diy/Banner', () => ({ default: () => 'Banner' }));
vi.mock('../diy/QuickNav', () => ({ default: () => 'QuickNav' }));
vi.mock('../diy/ImageAd', () => ({ default: () => 'ImageAd' }));
vi.mock('../diy/ProductGroup', () => ({ default: () => 'ProductGroup' }));
vi.mock('../diy/TitleBar', () => ({ default: () => 'TitleBar' }));
vi.mock('../diy/Gap', () => ({ default: () => 'Gap' }));
vi.mock('../diy/Divider', () => ({ default: () => 'Divider' }));
vi.mock('../diy/NoticeBar', () => ({ default: () => 'NoticeBar' }));
vi.mock('../diy/CouponGroup', () => ({ default: () => 'CouponGroup' }));
vi.mock('../diy/SeckillGroup', () => ({ default: () => 'SeckillGroup' }));
vi.mock('../diy/GroupBuyGroup', () => ({ default: () => 'GroupBuyGroup' }));
vi.mock('../diy/ProductRank', () => ({ default: () => 'ProductRank' }));
vi.mock('../diy/SearchBar', () => ({ default: () => 'SearchBar' }));
vi.mock('../diy/ShopInfo', () => ({ default: () => 'ShopInfo' }));
vi.mock('../diy/RichText', () => ({ default: () => 'RichText' }));
vi.mock('../diy/ImageCube', () => ({ default: () => 'ImageCube' }));
vi.mock('../diy/MarketingEntry', () => ({ default: () => 'MarketingEntry' }));
vi.mock('../diy/CategoryPanel', () => ({ default: () => 'CategoryPanel' }));
vi.mock('../diy/UserProfileHeader', () => ({ default: () => 'UserProfileHeader' }));
vi.mock('../diy/UserStats', () => ({ default: () => 'UserStats' }));
vi.mock('../diy/UserOrderPanel', () => ({ default: () => 'UserOrderPanel' }));
vi.mock('../diy/UserMenuList', () => ({ default: () => 'UserMenuList' }));

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
      'marketing-entry',
      'category-panel',
      'user-profile-header',
      'user-stats',
      'user-order-panel',
      'user-menu-list',
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
