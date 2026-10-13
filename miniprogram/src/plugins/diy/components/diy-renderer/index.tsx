import { View } from '@tarojs/components';
import { DiyComponent, DiyPagePayload } from './types';
import Banner from '../diy/Banner/index';
import QuickNav from '../diy/QuickNav/index';
import ImageAd from '../diy/ImageAd/index';
import ProductGroup from '../diy/ProductGroup/index';
import TitleBar from '../diy/TitleBar/index';
import Gap from '../diy/Gap/index';
import Divider from '../diy/Divider/index';
import NoticeBar from '../diy/NoticeBar/index';
import CouponGroup from '../diy/CouponGroup/index';
import SeckillGroup from '../diy/SeckillGroup/index';
import GroupBuyGroup from '../diy/GroupBuyGroup/index';
import ProductRank from '../diy/ProductRank/index';
import SearchBar from '../diy/SearchBar/index';
import ShopInfo from '../diy/ShopInfo/index';
import RichText from '../diy/RichText/index';
import ImageCube from '../diy/ImageCube/index';
import MarketingEntry from '../diy/MarketingEntry/index';
import CategoryPanel from '../diy/CategoryPanel/index';
import UserProfileHeader from '../diy/UserProfileHeader/index';
import UserStats from '../diy/UserStats/index';
import UserOrderPanel from '../diy/UserOrderPanel/index';
import UserMenuList from '../diy/UserMenuList/index';
import { diyStyle, handleComponentClick, schemaSize } from './style';
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
    primaryColor: '#E8836B',
    priceColor: '#E8836B',
    backgroundColor: '#FAF3ED',
    cardRadius: 16,
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
        '--diy-card-radius': schemaSize(theme.cardRadius),
      } as Record<string, string>}
    >
      {components.filter((component) => component.enabled !== false && registry[component.type]).map((component) => (
        <View
          key={component.id}
          className={`diy-renderer__item diy-renderer__item--${component.type}`}
          onClick={() => handleComponentClick(component)}
        >
          {renderDiyComponent(component)}
        </View>
      ))}
    </View>
  );
}
