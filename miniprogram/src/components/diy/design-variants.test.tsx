import { describe, expect, it, vi } from 'vitest';

vi.mock('@tarojs/components', () => ({
  Image: 'image',
  RichText: 'rich-text',
  Text: 'text',
  View: 'view',
}));

vi.mock('../diy-renderer/assets', () => ({
  resolveDiyAsset: (value: string) => value,
}));

vi.mock('../diy-renderer/link', () => ({
  navigateDiyLink: vi.fn(),
}));

import CouponGroup from './CouponGroup';
import ImageAd from './ImageAd';
import RichText from './RichText';
import ShopInfo from './ShopInfo';

function textContent(node: any): string {
  if (node === null || node === undefined || typeof node === 'boolean') return '';
  if (typeof node === 'string' || typeof node === 'number') return String(node);
  if (Array.isArray(node)) return node.map(textContent).join('');
  return textContent(node.props?.children);
}

describe('DIY design variants', () => {
  it('coupon group renders visible preview coupons when schema has no selected coupons', () => {
    const node = CouponGroup({
      component: {
        id: 'coupon-center-list',
        type: 'coupon-group',
        props: { layout: 'two-column', limit: 2 },
        data: { coupons: [] },
      },
    });

    expect(textContent(node)).toContain('满199减40');
    expect(node?.props?.className).toContain('diy-coupon-group--two-column');
  });

  it('image ad exposes coupon hero variant class from schema props', () => {
    const node = ImageAd({
      component: {
        id: 'coupon-center-hero',
        type: 'image-ad',
        props: { layout: 'single', variant: 'coupon-hero', height: 132, radius: 24 },
        data: { items: [{ image: 'https://example.test/coupon.png' }] },
      },
    });

    expect(node?.props?.className).toContain('diy-image-ad--coupon-hero');
  });

  it('shop info and rich text apply schema component styles', () => {
    const shop = ShopInfo({
      component: {
        id: 'user-shop-info',
        type: 'shop-info',
        props: { variant: 'user-profile', name: '温馨用户', description: '邀请码：WARM2026' },
        style: { background: '#E8836B', borderRadius: 0 },
        data: { tags: ['正品保障'] },
      },
    });
    const richText = RichText({
      component: {
        id: 'cart-empty-state',
        type: 'rich-text',
        props: { padding: 16 },
        style: { backgroundColor: '#FFFFFF', borderRadius: 24 },
        data: { content: '<p>购物车还是空的</p>' },
      },
    });

    expect(shop?.props?.className).toContain('diy-shop-info--user-profile');
    expect(shop?.props?.style?.background).toBe('#E8836B');
    expect(richText?.props?.style?.borderRadius).toBe('48rpx');
  });
});
