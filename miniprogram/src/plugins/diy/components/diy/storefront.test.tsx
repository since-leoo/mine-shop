import { describe, expect, it, vi } from 'vitest';
vi.mock('@tarojs/components', () => ({ View: 'view', Text: 'text', Image: 'image' }));
vi.mock('../../../../components/diy-renderer/assets', () => ({ resolveDiyAsset: (value: string) => value }));
vi.mock('../diy-renderer/assets', () => ({ resolveDiyAsset: (value: string) => value }));
vi.mock('../diy-renderer/link', () => ({ navigateDiyLink: vi.fn() }));
import ProductGroup, { normalizeProduct } from './ProductGroup';
import { navigateDiyLink } from '../diy-renderer/link';
import { categoryLeaves, normalizeCategories } from './CategoryPanel/categories';
import { couponAmountLabel, couponThreshold } from './CouponGroup';
import SearchBar from './SearchBar';
import GroupBuyGroup from './GroupBuyGroup';
import SeckillGroup from './SeckillGroup';

function find(node: any, predicate: (node: any) => boolean): any {
  if (!node || typeof node !== 'object') return undefined;
  if (predicate(node)) return node;
  for (const child of [node.props?.children].flat(Infinity)) {
    const result = find(child, predicate); if (result) return result;
  }
}

describe('DIY live storefront contracts', () => {
  it('empty products never become sample purchases; real products preserve cents and do not fabricate SKU IDs', () => {
    const empty = ProductGroup({ component: { id: 'empty', type: 'product-group', data: { products: [] } } });
    expect(find(empty, node => node.props?.className === 'diy-product-group__empty').props.children).toBe('暂无商品，敬请期待');
    expect(normalizeProduct({ id: 7, minPrice: 599, maxPrice: 999 }, 0)).toMatchObject({ spuId: 7, skuId: undefined, price: 599, originPrice: 999 });
    const group = ProductGroup({ component: { id: 'real', type: 'product-group', data: { products: [{ id: 7, price: 599 }] } } });
    const list = find(group, node => typeof node.props?.onAddCart === 'function');
    list.props.onAddCart(list.props.goodsList[0]);
    expect(navigateDiyLink).toHaveBeenLastCalledWith({ type: 'product', id: 7 }, { needLogin: undefined });
  });
  it('leaf and nested category IDs remain selectable', () => {
    const categories = normalizeCategories([{ id: 9, name: '茶具' }, { id: 10, children: [{ id: 11, children: [{ id: 12, name: '水杯' }] }] }]);
    expect(categoryLeaves(categories[0])[0].groupId).toBe('9');
    expect(categoryLeaves(categories[1]).map(item => item.groupId)).toEqual(['12']);
  });
  it('discount coupon uses the same hundredths contract as the native coupon page', () => {
    expect(couponAmountLabel({ type: 'percent', value: 850 })).toBe('8.5折');
    expect(couponAmountLabel({ type: 'discount', discountValue: 850 })).toBe('8.5折');
    expect(couponAmountLabel({ type: 'fixed', value: 599 })).toBe('¥5.99');
    expect(couponThreshold({ thresholdAmount: 1999 })).toBe(1999);
    expect(couponThreshold({ threshold_amount: 1999 })).toBe(1999);
  });
  it('search default resolves the registered goods search route', () => {
    const search = SearchBar({ component: { id: 'search', type: 'search-bar' } });
    search.props.onClick({ stopPropagation: vi.fn() });
    expect(navigateDiyLink).toHaveBeenLastCalledWith({ type: 'page', path: '/pages/goods/search/index' }, { needLogin: undefined });
  });
  it('group-buy and seckill product clicks retain their business context', () => {
    const group = GroupBuyGroup({ component: { id: 'group', type: 'group-buy-group', data: { activities: [{ id: 21, productId: 9, groupPrice: 0, price: 599 }] } } });
    expect(find(group, node => node.props?.className === 'diy-group-buy-group__price').props.children).toEqual(['¥', '0.00']);
    find(group, node => node.props?.className === 'diy-group-buy-group__item').props.onClick({ stopPropagation: vi.fn() });
    expect(navigateDiyLink).toHaveBeenLastCalledWith({ type: 'page', path: '/pages/goods/details/index', params: { spuId: 9, orderType: 'group_buy', groupBuyId: 21 } }, { needLogin: undefined });
    // Published API responses are camelCase; old stored schemas may still use snake_case.
    for (const session of [{ id: 8, activityId: 22 }, { id: 8, activity_id: 22 }]) {
      const seckill = SeckillGroup({ component: { id: 'kill', type: 'seckill-group', data: { session, products: [{ id: 9, price: 599 }] } } });
      find(seckill, node => node.props?.className === 'diy-seckill-group__item').props.onClick({ stopPropagation: vi.fn() });
      expect(navigateDiyLink).toHaveBeenLastCalledWith({ type: 'page', path: '/pages/goods/details/index', params: { spuId: 9, orderType: 'seckill', activityId: 22, sessionId: 8 } }, { needLogin: undefined });
      seckill!.props.onClick({ stopPropagation: vi.fn() });
      expect(navigateDiyLink).toHaveBeenLastCalledWith({ type: 'page', path: '/pages/promotion/detail/index', params: { activityId: 22, sessionId: 8 } }, { needLogin: undefined });
    }
  });
});
