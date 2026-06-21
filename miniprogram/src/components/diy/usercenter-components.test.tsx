import { describe, expect, it, vi } from 'vitest';

vi.mock('@tarojs/components', () => ({
  Image: 'image',
  Text: 'text',
  View: 'view',
}));

vi.mock('../diy-renderer/assets', () => ({
  resolveDiyAsset: (value: string) => value,
}));

vi.mock('../diy-renderer/link', () => ({
  navigateDiyLink: vi.fn(),
}));

import UserMenuList from './UserMenuList';
import UserOrderPanel from './UserOrderPanel';
import UserProfileHeader from './UserProfileHeader';
import UserStats from './UserStats';

function textContent(node: any): string {
  if (node === null || node === undefined || typeof node === 'boolean') return '';
  if (typeof node === 'string' || typeof node === 'number') return String(node);
  if (Array.isArray(node)) return node.map(textContent).join('');
  return textContent(node.props?.children);
}

describe('DIY usercenter components', () => {
  it('renders profile header from schema props', () => {
    const node = UserProfileHeader({
      component: {
        id: 'user-profile-header',
        type: 'user-profile-header',
        props: { avatar: 'https://example.com/avatar.png', nickname: '小花花', inviteCode: 'WARM2026', qrcodeIcon: 'assets/usercenter/profile-qrcode.svg' },
        style: { borderRadius: { bottomLeft: 24, bottomRight: 24 } },
      },
    });

    expect(textContent(node)).toContain('小花花');
    expect(textContent(node)).toContain('邀请码: WARM2026');
    expect(node?.props?.style?.borderBottomLeftRadius).toBe('48rpx');
    expect(node?.props?.style?.borderBottomRightRadius).toBe('48rpx');
    const children = node.props.children as any[];
    expect(children[0].props.children.type).toBe('image');
    expect(children[2].type).toBe('image');
    expect(children[2].props.src).toBe('assets/usercenter/profile-qrcode.svg');
  });

  it('does not force radius when profile header schema has no radius', () => {
    const node = UserProfileHeader({
      component: {
        id: 'user-profile-header',
        type: 'user-profile-header',
        props: {},
        style: {},
      },
    });

    expect(node?.props?.style?.borderBottomLeftRadius).toBeUndefined();
    expect(node?.props?.style?.borderBottomRightRadius).toBeUndefined();
  });

  it('renders stats, order entries and menu rows from schema data', () => {
    const stats = UserStats({
      component: {
        id: 'user-stats',
        type: 'user-stats',
        data: { items: [{ label: '优惠券', value: '3' }, { label: '积分', value: '520' }] },
      },
    });
    const orders = UserOrderPanel({
      component: {
        id: 'user-order-panel',
        type: 'user-order-panel',
        props: { title: '我的订单', moreText: '全部订单' },
        data: { items: [{ label: '待付款', badge: true }, { label: '退换/售后' }] },
      },
    });
    const menu = UserMenuList({
      component: {
        id: 'user-account-menu',
        type: 'user-menu-list',
        data: { items: [{ label: '收货地址' }, { label: '优惠券', value: '3张可用' }] },
      },
    });

    expect(textContent(stats)).toContain('优惠券');
    expect(textContent(stats)).toContain('520');
    expect(textContent(orders)).toContain('我的订单');
    expect(textContent(orders)).toContain('退换/售后');
    expect(textContent(menu)).toContain('收货地址');
    expect(textContent(menu)).toContain('3张可用');
  });

  it('renders numeric order badges and hides zero counts', () => {
    const orders = UserOrderPanel({
      component: {
        id: 'user-order-panel',
        type: 'user-order-panel',
        props: { title: '我的订单' },
        data: {
          items: [
            { label: '待付款', orderNum: 2 },
            { label: '待发货', orderNum: 0 },
            { label: '退换/售后', orderNum: 120 },
          ],
        },
      },
    });

    const grid = (orders?.props?.children as any[])[1];
    const items = grid.props.children;
    const firstBadge = items[0].props.children[0].props.children[0];
    const secondBadge = items[1].props.children[0].props.children[0];
    const thirdBadge = items[2].props.children[0].props.children[0];

    expect(textContent(firstBadge)).toBe('2');
    expect(secondBadge).toBeNull();
    expect(textContent(thirdBadge)).toBe('99+');
  });
});
