export type DiyPageType = 'miniprogram' | 'h5' | 'all';

export interface DiyLink {
  type?: 'page' | 'url' | 'product' | 'category' | 'coupon' | 'group_buy' | 'seckill' | string;
  url?: string;
  path?: string;
  id?: string | number;
  params?: Record<string, string | number | boolean | undefined>;
}

export interface DiyComponent<TData = Record<string, any>, TProps = Record<string, any>> {
  id: string;
  type: string;
  name?: string;
  enabled?: boolean;
  props?: TProps & {
    link?: DiyLink;
    needLogin?: boolean;
    trackClick?: boolean;
  };
  style?: DiyComponentStyle;
  data?: TData;
}

export interface DiyPagePayload {
  page: {
    key: string;
    title?: string;
    theme?: DiyPageTheme;
    style?: DiyComponentStyle;
    [key: string]: any;
  } | null;
  components: DiyComponent[];
  publishedAt?: string | null;
}

export interface DiyImageItem {
  image?: string;
  img?: string;
  url?: string;
  title?: string;
  link?: DiyLink;
}

export interface DiyPageTheme {
  primaryColor?: string;
  priceColor?: string;
  backgroundColor?: string;
  cardRadius?: number;
  buttonShape?: 'round' | 'square' | 'plain' | string;
}

export interface DiyImageProps {
  layout?: string;
  variant?: string;
  widthMode?: 'full' | 'contained' | 'custom' | string;
  widthUnit?: 'percent' | 'px' | 'rpx' | string;
  width?: number;
  height?: number;
  radius?: number;
  objectFit?: 'cover' | 'contain' | 'fill' | string;
}

export interface DiyNavItem {
  icon?: string;
  svg?: string;
  svgCode?: string;
  iconText?: string;
  iconBg?: string;
  image?: string;
  title?: string;
  name?: string;
  link?: DiyLink;
}

export interface DiyMarketingEntryItem {
  title?: string;
  subtitle?: string;
  badge?: string;
  background?: string;
  color?: string;
  link?: DiyLink;
}

export interface DiyCategoryPanelChild {
  title?: string;
  name?: string;
  image?: string;
  thumbnail?: string;
  icon?: string;
  link?: DiyLink;
}

export interface DiyCategoryPanelItem {
  title?: string;
  name?: string;
  children?: DiyCategoryPanelChild[];
}

export interface DiyProductItem {
  id?: string | number;
  thumb?: string;
  image?: string;
  title?: string;
  name?: string;
  price?: number;
  originPrice?: number;
  [key: string]: any;
}

export interface DiyComponentStyle {
  textAlign?: 'left' | 'center' | 'right' | string;
  fontSize?: number;
  fontWeight?: number | string;
  fontFamily?: string;
  color?: string;
  background?: string;
  backgroundColor?: string;
  marginTop?: number;
  marginBottom?: number;
  padding?: number;
  borderRadius?: number;
  [key: string]: any;
}
