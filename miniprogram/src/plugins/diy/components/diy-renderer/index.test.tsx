import { describe, expect, it, vi } from 'vitest';

vi.mock('@tarojs/components', () => ({
  View: ({ children }: { children?: unknown }) => children || 'View',
}));

vi.mock('../diy/Banner/index', () => ({ default: () => 'Banner' }));
vi.mock('../diy/QuickNav/index', () => ({ default: () => 'QuickNav' }));
vi.mock('../diy/ImageAd/index', () => ({ default: () => 'ImageAd' }));
vi.mock('../diy/ProductGroup/index', () => ({ default: () => 'ProductGroup' }));
vi.mock('../diy/TitleBar/index', () => ({ default: () => 'TitleBar' }));
vi.mock('../diy/Gap/index', () => ({ default: () => 'Gap' }));
vi.mock('../diy/Divider/index', () => ({ default: () => 'Divider' }));
vi.mock('../diy/NoticeBar/index', () => ({ default: () => 'NoticeBar' }));
vi.mock('../diy/CouponGroup/index', () => ({ default: () => 'CouponGroup' }));
vi.mock('../diy/SeckillGroup/index', () => ({ default: () => 'SeckillGroup' }));
vi.mock('../diy/GroupBuyGroup/index', () => ({ default: () => 'GroupBuyGroup' }));
vi.mock('../diy/ProductRank/index', () => ({ default: () => 'ProductRank' }));
vi.mock('../diy/SearchBar/index', () => ({ default: () => 'SearchBar' }));
vi.mock('../diy/ShopInfo/index', () => ({ default: () => 'ShopInfo' }));
vi.mock('../diy/RichText/index', () => ({ default: () => 'RichText' }));
vi.mock('../diy/ImageCube/index', () => ({ default: () => 'ImageCube' }));
vi.mock('../diy/MarketingEntry/index', () => ({ default: () => 'MarketingEntry' }));
vi.mock('../diy/CategoryPanel/index', () => ({ default: () => 'CategoryPanel' }));
vi.mock('../diy/UserProfileHeader/index', () => ({ default: () => 'UserProfileHeader' }));
vi.mock('../diy/UserStats/index', () => ({ default: () => 'UserStats' }));
vi.mock('../diy/UserOrderPanel/index', () => ({ default: () => 'UserOrderPanel' }));
vi.mock('../diy/UserMenuList/index', () => ({ default: () => 'UserMenuList' }));
vi.mock('../../../distribution/components/DistributionCenter/index', () => ({ default: () => 'DistributionCenter' }));

import { renderDiyComponent } from './index';
import { registerStorefrontPlugin, unregisterStorefrontPlugin } from '../../../../common/plugin-hooks';

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

  it('通过公共插件接口扩展渲染器，移除插件后跳过该组件', () => {
    registerStorefrontPlugin({ name: 'renderer-test', renderers: { 'example-extension': () => null } });
    expect(renderDiyComponent({ id: 'example', type: 'example-extension' })).not.toBeNull();
    unregisterStorefrontPlugin('renderer-test');
    expect(renderDiyComponent({ id: 'example', type: 'example-extension' })).toBeNull();
  });
});
