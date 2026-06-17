import type { DiyComponent, DiyComponentMeta, DiyPageTheme, DiySchema } from './types'

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

export const designPageBackgroundColor = '#FAF3ED'

export const designHomePageBackground = 'linear-gradient(180deg, #E8836B 0, #F2A99A 90px, #F5C5A3 180px, #FAF3ED 330px, #FAF3ED 100%)'

export function designBannerImage(index: number) {
  return `https://tdesign.gtimg.com/miniprogram/template/retail/home/v2/banner${index}.png`
}

export const defaultPageTheme: Required<DiyPageTheme> = {
  primaryColor: '#E8836B',
  priceColor: '#E8836B',
  backgroundColor: designPageBackgroundColor,
  cardRadius: 16,
  buttonShape: 'round',
}

export function defaultPageStyle(pageKey: string) {
  if (pageKey === 'home') {
    return {
      background: designHomePageBackground,
      backgroundColor: designPageBackgroundColor,
    }
  }

  return {
    background: designPageBackgroundColor,
    backgroundColor: designPageBackgroundColor,
  }
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
        { image: designBannerImage(1), title: '春日上新', link: { type: 'page', path: '/pages/goods/result/index' } },
        { image: designBannerImage(2), title: '限时特惠', link: { type: 'page', path: '/pages/coupon/coupon-center/index' } },
      ],
    }, {
      ...imageBaseProps,
      widthMode: 'contained',
      height: 148,
      radius: 24,
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
    }, {
      columns: 5,
      rows: 1,
      iconSize: 48,
      iconRadius: 16,
      imageSize: 30,
      itemGap: 7,
    }, {
      margin: { top: 0, right: 16, bottom: 8, left: 16 },
      padding: { top: 11, right: 8, bottom: 7, left: 8 },
      background: '#FFFFFF',
      borderRadius: 16,
      boxShadow: '0 2px 8px rgba(200,140,110,0.08)',
    }),
  },
  {
    type: 'category-panel',
    name: '商品分类',
    icon: 'ph:list-bullets',
    description: '左右栏商品分类，适合分类页主区域',
    category: 'base',
    orientation: 'both',
    defaults: () => component('category-panel', '商品分类', {}, {
      columns: 3,
      activeIndex: 0,
    }, {
      margin: { top: 0, right: 0, bottom: 0, left: 0 },
      height: '100%',
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
    type: 'marketing-entry',
    name: '营销入口',
    icon: 'ph:rocket-launch',
    description: '秒杀、拼团等活动统一跳转入口',
    category: 'marketing',
    orientation: 'horizontal',
    defaults: () => component('marketing-entry', '营销入口', {
      items: [
        {
          title: '秒杀专场',
          subtitle: '点击进入专题页',
          badge: '距下一场 02:18:45',
          background: '#F0A18E',
          color: '#FFFFFF',
          link: { type: 'page', path: '/pages/promotion/seckill/index' },
        },
        {
          title: '拼团会场',
          subtitle: '精选团购每天上新',
          badge: '3人团最低5折起',
          background: '#86BFA9',
          color: '#FFFFFF',
          link: { type: 'page', path: '/pages/promotion/group-buy/index' },
        },
      ],
    }, {
      title: '今日活动直达',
      layout: 'two-column',
      itemGap: 10,
      cardRadius: 10,
    }, {
      margin: { top: 0, right: 16, bottom: 8, left: 16 },
      padding: { top: 12, right: 14, bottom: 14, left: 14 },
      background: '#FFFFFF',
      borderRadius: 16,
      boxShadow: '0 2px 8px rgba(200,140,110,0.08)',
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
    type: 'user-profile-header',
    name: '用户头部',
    icon: 'ph:user-circle',
    description: '会员中心头像、昵称和邀请码头部',
    category: 'user',
    orientation: 'horizontal',
    defaults: () => component('user-profile-header', '用户头部', {}, {
      nickname: '小花花',
      inviteCode: 'WARM2026',
      avatar: '',
      qrcodeIcon: 'assets/usercenter/profile-qrcode.svg',
    }, {
      padding: { top: 96, right: 28, bottom: 38, left: 28 },
      background: 'linear-gradient(180deg, var(--diy-primary-color) 0%, color-mix(in srgb, var(--diy-primary-color) 62%, #ffffff) 72%, var(--diy-page-background-color) 100%)',
      color: '#FFFFFF',
      borderRadius: { bottomLeft: 30, bottomRight: 30 },
    }),
  },
  {
    type: 'user-stats',
    name: '用户资产',
    icon: 'ph:chart-bar',
    description: '优惠券、积分、余额、收藏等资产统计',
    category: 'user',
    orientation: 'horizontal',
    defaults: () => component('user-stats', '用户资产', {
      items: [
        { label: '优惠券', value: '3', link: { type: 'page', path: '/pages/coupon/coupon-list/index' } },
        { label: '积分', value: '520', link: { type: 'page', path: '/pages/usercenter/index' } },
        { label: '余额', value: '¥88', link: { type: 'page', path: '/pages/usercenter/wallet-transactions/index' } },
        { label: '收藏', value: '12', link: { type: 'page', path: '/pages/usercenter/index' } },
      ],
    }, {}, {
      margin: { top: -20, right: 20, bottom: 14, left: 20 },
      padding: { top: 0, right: 0, bottom: 14, left: 0 },
      background: '#FFFFFF',
      borderRadius: 16,
      position: 'relative',
      zIndex: 2,
    }),
  },
  {
    type: 'user-order-panel',
    name: '我的订单',
    icon: 'ph:receipt',
    description: '会员中心订单状态入口',
    category: 'user',
    orientation: 'horizontal',
    defaults: () => component('user-order-panel', '我的订单', {
      items: [
        { label: '待付款', icon: 'assets/usercenter/order-pay.svg', badge: true, link: { type: 'page', path: '/pages/order/order-list/index', params: { tabType: 5 } } },
        { label: '待发货', icon: 'assets/usercenter/order-deliver.svg', link: { type: 'page', path: '/pages/order/order-list/index', params: { tabType: 10 } } },
        { label: '待收货', icon: 'assets/usercenter/order-receive.svg', link: { type: 'page', path: '/pages/order/order-list/index', params: { tabType: 40 } } },
        { label: '待评价', icon: 'assets/usercenter/order-review.svg', link: { type: 'page', path: '/pages/order/order-list/index', params: { tabType: 60 } } },
        { label: '退换/售后', icon: 'assets/usercenter/order-service.svg', link: { type: 'page', path: '/pages/order/after-service-list/index' } },
      ],
    }, {
      title: '我的订单',
      moreText: '全部订单',
      moreLink: { type: 'page', path: '/pages/order/order-list/index' },
    }, {
      margin: { top: 0, right: 20, bottom: 14, left: 20 },
      background: '#FFFFFF',
      borderRadius: 14,
    }),
  },
  {
    type: 'user-menu-list',
    name: '用户菜单',
    icon: 'ph:list',
    description: '收货地址、优惠券、钱包、客服等列表入口',
    category: 'user',
    orientation: 'vertical',
    defaults: () => component('user-menu-list', '用户菜单', {
      items: [
        { label: '收货地址', icon: 'assets/usercenter/menu-address.svg', link: { type: 'page', path: '/pages/user/address/list/index' } },
        { label: '优惠券', value: '3张可用', icon: 'assets/usercenter/menu-coupon.svg', link: { type: 'page', path: '/pages/coupon/coupon-list/index' } },
        { label: '我的钱包', icon: 'assets/usercenter/menu-wallet.svg', link: { type: 'page', path: '/pages/usercenter/wallet-transactions/index' } },
      ],
    }, {}, {
      margin: { top: 0, right: 20, bottom: 14, left: 20 },
      background: '#FFFFFF',
      borderRadius: 14,
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

export function createDefaultSchema(pageKey: string, title: string): DiySchema {
  return {
    version: 1,
    page: {
      key: pageKey,
      title,
      theme: { ...defaultPageTheme },
      style: defaultPageStyle(pageKey),
    },
    components: [
      componentRegistry.find(item => item.type === 'title-bar')!.defaults(),
      componentRegistry.find(item => item.type === 'banner')!.defaults(),
    ],
  }
}
