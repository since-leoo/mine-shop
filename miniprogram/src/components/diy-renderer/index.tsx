import { View } from '@tarojs/components';
import { DiyComponent, DiyPagePayload } from './types';
import Banner from '../diy/Banner';
import QuickNav from '../diy/QuickNav';
import ImageAd from '../diy/ImageAd';
import ProductGroup from '../diy/ProductGroup';
import TitleBar from '../diy/TitleBar';
import Gap from '../diy/Gap';
import Divider from '../diy/Divider';
import NoticeBar from '../diy/NoticeBar';
import CouponGroup from '../diy/CouponGroup';
import SeckillGroup from '../diy/SeckillGroup';
import GroupBuyGroup from '../diy/GroupBuyGroup';
import ProductRank from '../diy/ProductRank';
import SearchBar from '../diy/SearchBar';
import ShopInfo from '../diy/ShopInfo';
import RichText from '../diy/RichText';
import ImageCube from '../diy/ImageCube';
import './index.scss';

interface DiyRendererProps {
  page?: DiyPagePayload | null;
}

const registry: Record<string, (component: DiyComponent) => JSX.Element | null> = {
  banner: (component) => <Banner component={component} />,
  'quick-nav': (component) => <QuickNav component={component} />,
  'image-ad': (component) => <ImageAd component={component} />,
  'product-group': (component) => <ProductGroup component={component} />,
  'title-bar': (component) => <TitleBar component={component} />,
  gap: (component) => <Gap component={component} />,
  divider: (component) => <Divider component={component} />,
  'notice-bar': (component) => <NoticeBar component={component} />,
  'coupon-group': (component) => <CouponGroup component={component} />,
  'seckill-group': (component) => <SeckillGroup component={component} />,
  'group-buy-group': (component) => <GroupBuyGroup component={component} />,
  'product-rank': (component) => <ProductRank component={component} />,
  'search-bar': (component) => <SearchBar component={component} />,
  'shop-info': (component) => <ShopInfo component={component} />,
  'rich-text': (component) => <RichText component={component} />,
  'image-cube': (component) => <ImageCube component={component} />,
};

export function renderDiyComponent(component: DiyComponent): JSX.Element | null {
  if (component.enabled === false) return null;
  const renderer = registry[component.type];
  return renderer ? renderer(component) : null;
}

export default function DiyRenderer({ page }: DiyRendererProps) {
  const components = page?.components || [];
  if (!page?.page || components.length === 0) return null;
  const theme = {
    primaryColor: '#2563eb',
    priceColor: '#ef4444',
    backgroundColor: '#f6f7f8',
    cardRadius: 8,
    ...(page.page.theme || {}),
  };

  return (
    <View
      className="diy-renderer"
      style={{
        backgroundColor: theme.backgroundColor,
        '--diy-primary-color': theme.primaryColor,
        '--diy-price-color': theme.priceColor,
        '--diy-card-radius': `${theme.cardRadius}px`,
      } as Record<string, string>}
    >
      {components.map((component) => (
        <View key={component.id} className="diy-renderer__item">
          {renderDiyComponent(component)}
        </View>
      ))}
    </View>
  );
}
