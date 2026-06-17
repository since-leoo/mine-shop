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
import MarketingEntry from '../diy/MarketingEntry';
import CategoryPanel from '../diy/CategoryPanel';
import UserProfileHeader from '../diy/UserProfileHeader';
import UserStats from '../diy/UserStats';
import UserOrderPanel from '../diy/UserOrderPanel';
import UserMenuList from '../diy/UserMenuList';
import { diyStyle, handleComponentClick } from './style';
import './index.scss';

interface DiyRendererProps {
  page?: DiyPagePayload | null;
  className?: string;
  transparent?: boolean;
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
  'marketing-entry': (component) => <MarketingEntry component={component} />,
  'category-panel': (component) => <CategoryPanel component={component} />,
  'user-profile-header': (component) => <UserProfileHeader component={component} />,
  'user-stats': (component) => <UserStats component={component} />,
  'user-order-panel': (component) => <UserOrderPanel component={component} />,
  'user-menu-list': (component) => <UserMenuList component={component} />,
};

export function renderDiyComponent(component: DiyComponent): JSX.Element | null {
  if (component.enabled === false) return null;
  const renderer = registry[component.type];
  return renderer ? renderer(component) : null;
}

export default function DiyRenderer({ page, className = '', transparent = false }: DiyRendererProps) {
  const components = page?.components || [];
  if (!page?.page || components.length === 0) return null;
  const theme = {
    primaryColor: '#2563eb',
    priceColor: '#ef4444',
    backgroundColor: '#f6f7f8',
    cardRadius: 8,
    ...(page.page.theme || {}),
  };

  const pageStyle = diyStyle(page.page.style || {});
  const pageBackground = page.page.style?.background || page.page.style?.backgroundColor;
  const rendererStyle = transparent
    ? { ...pageStyle, background: undefined, backgroundColor: 'transparent' }
    : { ...pageStyle, backgroundColor: pageBackground ? undefined : theme.backgroundColor };

  return (
    <View
      className={`diy-renderer ${className}`}
      style={{
        ...rendererStyle,
        '--diy-primary-color': theme.primaryColor,
        '--diy-price-color': theme.priceColor,
        '--diy-page-background-color': theme.backgroundColor,
        '--diy-card-radius': `${theme.cardRadius}px`,
      } as Record<string, string>}
    >
      {components.map((component) => (
        <View
          key={component.id}
          className="diy-renderer__item"
          onClick={() => handleComponentClick(component)}
        >
          {renderDiyComponent(component)}
        </View>
      ))}
    </View>
  );
}
