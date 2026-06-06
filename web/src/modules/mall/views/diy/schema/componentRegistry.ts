import type { DiyComponent, DiyComponentMeta, DiyPageTheme } from './types'

function id(prefix: string): string {
  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`
}

function component(
  type: string,
  name: string,
  data: Record<string, any>,
  props: Record<string, any> = {},
  style: Record<string, any> = {},
): DiyComponent {
  return {
    id: id(type),
    type,
    name,
    enabled: true,
    props,
    style,
    data,
  }
}

const imageBaseProps = {
  widthMode: 'full',
  widthUnit: 'percent',
  width: 100,
  height: 160,
  radius: 8,
  objectFit: 'cover',
}

export const defaultPageTheme: Required<DiyPageTheme> = {
  primaryColor: '#2563eb',
  priceColor: '#ef4444',
  backgroundColor: '#f6f7f8',
  cardRadius: 8,
  buttonShape: 'round',
}

export const componentRegistry: DiyComponentMeta[] = [
  {
    type: 'banner',
    name: '轮播图',
    icon: 'ph:image-square',
    description: '顶部焦点图，最多 10 张',
    category: 'ad',
    orientation: 'horizontal',
    defaults: () => component('banner', '轮播图', {
      items: [
        { image: '', title: '轮播图', link: { type: 'page', path: '' } },
      ],
    }, {
      ...imageBaseProps,
      height: 150,
      autoplay: true,
    }),
  },
  {
    type: 'quick-nav',
    name: '金刚区',
    icon: 'ph:squares-four',
    description: '常用入口，建议 5 到 10 个',
    category: 'base',
    orientation: 'both',
    defaults: () => component('quick-nav', '金刚区', {
      items: [
        { title: '分类', icon: '', link: { type: 'page', path: '/pages/category/index' } },
        { title: '优惠券', icon: '', link: { type: 'page', path: '/pages/coupon/coupon-center/index' } },
      ],
    }),
  },
  {
    type: 'image-ad',
    name: '图片广告',
    icon: 'ph:images',
    description: '单图或双列活动图',
    category: 'ad',
    orientation: 'both',
    defaults: () => component('image-ad', '图片广告', {
      items: [
        { image: '', title: '广告图', link: { type: 'page', path: '' } },
      ],
    }, {
      ...imageBaseProps,
      layout: 'single',
      height: 120,
    }),
  },
  {
    type: 'product-group',
    name: '商品组',
    icon: 'ph:shopping-bag',
    description: '手动或自动商品推荐',
    category: 'marketing',
    orientation: 'both',
    defaults: () => component('product-group', '商品组', {
      product_ids: [],
      products: [],
    }, {
      source: 'recommend',
      sort: 'default',
      limit: 10,
      layout: 'two-column',
    }),
  },
  {
    type: 'title-bar',
    name: '标题栏',
    icon: 'ph:text-aa',
    description: '模块标题与副标题',
    category: 'base',
    orientation: 'horizontal',
    defaults: () => component('title-bar', '标题栏', {}, {
      title: '精选推荐',
      subtitle: '为你挑选',
    }),
  },
  {
    type: 'gap',
    name: '辅助空白',
    icon: 'ph:arrows-out-line-vertical',
    description: '控制模块间距',
    category: 'base',
    orientation: 'vertical',
    defaults: () => component('gap', '辅助空白', {}, {
      height: 16,
      background: 'transparent',
    }),
  },
  {
    type: 'divider',
    name: '分割线',
    icon: 'ph:minus',
    description: '细线分隔内容',
    category: 'base',
    orientation: 'horizontal',
    defaults: () => component('divider', '分割线', {}, {
      color: '#e8ecef',
      margin: 24,
    }),
  },
  {
    type: 'notice-bar',
    name: '公告栏',
    icon: 'ph:megaphone',
    description: '滚动展示店铺公告或活动提示',
    category: 'marketing',
    orientation: 'horizontal',
    defaults: () => component('notice-bar', '公告栏', {
      items: [
        { text: '新人下单立减，限时领券', link: { type: 'coupon', id: '' } },
      ],
    }, {
      speed: 40,
      showIcon: true,
    }, {
      background: '#fff7ed',
      color: '#c2410c',
    }),
  },
  {
    type: 'coupon-group',
    name: '优惠券组',
    icon: 'ph:ticket',
    description: '展示可领取优惠券，最多 10 张',
    category: 'marketing',
    orientation: 'horizontal',
    defaults: () => component('coupon-group', '优惠券组', {
      couponIds: [],
      coupons: [],
    }, {
      title: '领券中心',
      limit: 3,
      layout: 'scroll',
    }),
  },
  {
    type: 'seckill-group',
    name: '秒杀组',
    icon: 'ph:timer',
    description: '绑定秒杀场次并展示限时商品',
    category: 'marketing',
    orientation: 'horizontal',
    defaults: () => component('seckill-group', '秒杀组', {
      sessionId: null,
      activityId: null,
      products: [],
    }, {
      title: '限时秒杀',
      limit: 6,
      layout: 'scroll',
    }),
  },
  {
    type: 'group-buy-group',
    name: '拼团组',
    icon: 'ph:users-three',
    description: '展示拼团活动商品',
    category: 'marketing',
    orientation: 'both',
    defaults: () => component('group-buy-group', '拼团组', {
      groupBuyIds: [],
      activities: [],
    }, {
      title: '多人拼团',
      limit: 6,
      layout: 'two-column',
    }),
  },
  {
    type: 'product-rank',
    name: '商品榜单',
    icon: 'ph:ranking',
    description: '热销、新品或推荐商品榜',
    category: 'marketing',
    orientation: 'vertical',
    defaults: () => component('product-rank', '商品榜单', {
      products: [],
    }, {
      title: '热销榜单',
      rankType: 'hot',
      limit: 10,
    }),
  },
  {
    type: 'search-bar',
    name: '搜索框',
    icon: 'ph:magnifying-glass',
    description: '页面顶部商品搜索入口',
    category: 'base',
    orientation: 'horizontal',
    defaults: () => component('search-bar', '搜索框', {}, {
      placeholder: '搜索商品',
      shape: 'round',
      target: '/pages/search/index',
    }),
  },
  {
    type: 'shop-info',
    name: '店铺信息',
    icon: 'ph:storefront',
    description: '展示店铺头像、名称和服务标签',
    category: 'user',
    orientation: 'horizontal',
    defaults: () => component('shop-info', '店铺信息', {
      tags: ['正品保障', '极速发货'],
    }, {
      logo: '',
      name: '官方商城',
      description: '精选好物，安心选购',
    }),
  },
  {
    type: 'rich-text',
    name: '富文本',
    icon: 'ph:text-align-left',
    description: '展示活动规则、说明或图文内容',
    category: 'base',
    orientation: 'vertical',
    defaults: () => component('rich-text', '富文本', {
      content: '<p>请输入图文内容</p>',
    }, {
      padding: 12,
    }),
  },
  {
    type: 'image-cube',
    name: '图片魔方',
    icon: 'ph:grid-four',
    description: '一行多图或橱窗式图片布局',
    category: 'ad',
    orientation: 'both',
    defaults: () => component('image-cube', '图片魔方', {
      items: [
        { image: '', title: '主推活动', link: { type: 'page', path: '' } },
        { image: '', title: '精选专区', link: { type: 'page', path: '' } },
      ],
    }, {
      ...imageBaseProps,
      layout: 'two',
      gap: 8,
      height: 120,
    }),
  },
]

export function createDefaultSchema(pageKey: string, title: string): { version: 1, page: { key: string, title: string, theme: typeof defaultPageTheme }, components: DiyComponent[] } {
  return {
    version: 1,
    page: {
      key: pageKey,
      title,
      theme: { ...defaultPageTheme },
    },
    components: [
      componentRegistry.find(item => item.type === 'title-bar')!.defaults(),
      componentRegistry.find(item => item.type === 'banner')!.defaults(),
    ],
  }
}
