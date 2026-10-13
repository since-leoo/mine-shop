import { afterEach, describe, expect, it, vi } from 'vitest';

const platform = vi.hoisted(() => ({ h5: true }));
vi.mock('../../../../common/platform', () => ({ isH5: () => platform.h5 }));
vi.mock('@tarojs/components', () => ({ View: 'view', Text: 'text', Image: 'image' }));
vi.mock('./link', () => ({ navigateDiyLink: vi.fn() }));
import MarketingEntry from '../diy/MarketingEntry';
import QuickNav from '../diy/QuickNav';
import GoodsList from '../../../../components/GoodsList';
import { imageOuterStyle } from '../diy/imageStyle';
import { diyStyle, schemaSize } from './style';

function find(node: any, className: string): any {
  if (!node || typeof node !== 'object') return undefined;
  if (node.props?.className === className) return node;
  for (const child of [node.props?.children].flat(Infinity)) {
    const result = find(child, className); if (result) return result;
  }
}

afterEach(() => { platform.h5 = true; });

describe('runtime DIY dimensions', () => {
  it.each([true, false])('marketing and navigation honor stored dimensions on H5=%s', h5 => {
    platform.h5 = h5;
    const marketing = MarketingEntry({ component: { id: 'marketing', type: 'marketing-entry', props: { itemGap: 13, cardRadius: 12 }, data: { items: [{ title: '活动' }] } } });
    expect(find(marketing, 'diy-marketing-entry__grid').props.style.gap).toBe(h5 ? '13PX' : '26rpx');
    expect(find(marketing, 'diy-marketing-entry__card').props.style.borderRadius).toBe(h5 ? '12PX' : '24rpx');
    const nav = QuickNav({ component: { id: 'nav', type: 'quick-nav', props: { iconSize: 48, iconRadius: 16, itemGap: 7 }, data: { items: [{ title: '分类' }] } } });
    expect(find(nav, 'diy-quick-nav__icon-wrap').props.style.width).toBe(h5 ? '48PX' : '96rpx');
    expect(find(nav, 'diy-quick-nav__icon-wrap').props.style.borderRadius).toBe(h5 ? '16PX' : '32rpx');
    expect(find(nav, 'diy-quick-nav__title').props.style.marginTop).toBe(h5 ? '7PX' : '14rpx');
    expect(imageOuterStyle({ widthMode: 'contained' }).marginLeft).toBe(h5 ? '16PX' : '32rpx');
  });

  it('goods gap and padding use browser units and preserve explicit zero', () => {
    const list = GoodsList({ goodsList: [], gap: 18, padding: { top: 0, right: 8, bottom: 14, left: 8 } });
    expect(list.props.style).toEqual({ gap: '18PX', padding: '0PX 8PX 14PX 8PX' });
    expect(GoodsList({ goodsList: [], gap: 0, padding: '0 32rpx 12px' }).props.style).toEqual({ gap: '0PX', padding: '0 16PX 12PX' });
    expect(schemaSize(0)).toBe('0PX');
    expect(schemaSize('invalid')).toBeUndefined();
    expect(diyStyle({ padding: '8px 32rpx' }).padding).toBe('8PX 16PX');
  });
});
