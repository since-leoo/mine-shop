import type { DiyComponent, DiyComponentMeta } from './types'

function id(prefix: string): string {
  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`
}

function component(type: string, name: string, data: Record<string, any>, props: Record<string, any> = {}, style: Record<string, any> = {}): DiyComponent {
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

export const componentRegistry: DiyComponentMeta[] = [
  {
    type: 'banner',
    name: '轮播图',
    icon: 'ph:image-square',
    description: '顶部焦点图，最多 10 张',
    defaults: () => component('banner', '轮播图', {
      items: [
        { image: '', title: '轮播图', link: { type: 'page', path: '' } },
      ],
    }),
  },
  {
    type: 'quick-nav',
    name: '金刚区',
    icon: 'ph:squares-four',
    description: '常用入口，建议 5 到 10 个',
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
    defaults: () => component('image-ad', '图片广告', {
      items: [
        { image: '', title: '广告图', link: { type: 'page', path: '' } },
      ],
    }),
  },
  {
    type: 'product-group',
    name: '商品组',
    icon: 'ph:shopping-bag',
    description: '手动或自动商品推荐',
    defaults: () => component('product-group', '商品组', {
      mode: 'recommend',
      product_ids: [],
      products: [],
    }, {
      limit: 10,
    }),
  },
  {
    type: 'title-bar',
    name: '标题栏',
    icon: 'ph:text-aa',
    description: '模块标题与副标题',
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
    defaults: () => component('divider', '分割线', {}, {
      color: '#e8ecef',
      margin: 24,
    }),
  },
]

export function createDefaultSchema(pageKey: string, title: string): { version: 1; page: { key: string; title: string }; components: DiyComponent[] } {
  return {
    version: 1,
    page: {
      key: pageKey,
      title,
    },
    components: [
      componentRegistry.find(item => item.type === 'title-bar')!.defaults(),
      componentRegistry.find(item => item.type === 'banner')!.defaults(),
    ],
  }
}
