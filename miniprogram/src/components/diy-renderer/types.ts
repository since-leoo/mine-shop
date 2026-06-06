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
  props?: TProps;
  style?: Record<string, any>;
  data?: TData;
}

export interface DiyPagePayload {
  page: {
    key: string;
    title?: string;
    theme?: DiyPageTheme;
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
  widthMode?: 'full' | 'contained' | 'custom' | string;
  widthUnit?: 'percent' | 'px' | 'rpx' | string;
  width?: number;
  height?: number;
  radius?: number;
  objectFit?: 'cover' | 'contain' | 'fill' | string;
}

export interface DiyNavItem {
  icon?: string;
  image?: string;
  title?: string;
  name?: string;
  link?: DiyLink;
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
