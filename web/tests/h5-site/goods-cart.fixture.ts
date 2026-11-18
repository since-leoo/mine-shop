import { type Page } from '@playwright/test'
import { productImage, setupPage, type ApiCall, type ApiReply } from './helpers'

export const title = '北欧简约陶瓷花瓶温暖日系家居装饰摆件特别加长商品标题ABCDEFGHIJKLMNOPQRSTUVWXYZ'
export const cartRows = [
  { skuId: '501', spuId: '101', title, thumb: productImage, price: 999, quantity: 1, stockQuantity: 3, isSelected: true, specInfo: '暖杏色 / 大号' },
  { skuId: '502', spuId: '102', title: '有机棉柔软毛巾', thumb: productImage, price: 19999798, quantity: 1, stockQuantity: 8, isSelected: false, specInfo: '米白色 / 2条装' },
]
export function product(id = '101') {
  const simple = id === '102'
  return { spuId: id, title, primaryImage: productImage, images: [productImage], desc: [productImage], minSalePrice: 999, maxLinePrice: 1599, soldNum: 28, available: 1,
    specList: simple ? [] : [{ specId: '1', title: '颜色', specValueList: [{ specValueId: '11', specValue: '暖杏色' }, { specValueId: '12', specValue: '薄荷绿（暂时缺货）' }] }],
    skuList: simple ? [{ skuId: '502', stockInfo: { stockQuantity: 8 }, priceInfo: [{ price: 999 }], specInfo: [] }] : [
      { skuId: '501', stockInfo: { stockQuantity: 3 }, priceInfo: [{ price: 999 }], specInfo: [{ specId: '1', specValueId: '11' }] },
      { skuId: '503', stockInfo: { stockQuantity: 0 }, priceInfo: [{ price: 1199 }], specInfo: [{ specId: '1', specValueId: '12' }] },
    ],
  }
}
export const review = { id: 1, nickname: '很长的会员昵称ABCDEF国际事业部测试用户', avatar: '', rating: 5, content: '颜色很温暖，包装完好。' + '使用体验很好'.repeat(15), skuName: '暖杏色 / 大号', createdAt: '2026-10-13 10:00:00', images: [productImage], adminReply: '感谢您的反馈' }
export async function goodsApi(page: Page, custom?: (call: ApiCall) => ApiReply | undefined | Promise<ApiReply | undefined>) {
  let rows = structuredClone(cartRows)
  const cart = () => ({ storeGoods: rows.length ? [{ storeId: '1', storeName: '温馨小铺', promotionGoodsList: [{ goodsPromotionList: rows }] }] : [] })
  return setupPage(page, async call => {
    const override = await custom?.(call)
    if (override) return override
    const { path, method, query, body } = call
    if (path === '/api/v1/cart') return { data: cart() }
    if (path === '/api/v1/cart/items' && method === 'POST') return { data: cart() }
    if (/\/cart\/items\/\d+$/.test(path)) {
      const id = path.split('/').at(-1)
      if (method === 'PUT') rows = rows.map(row => row.skuId === id ? { ...row, quantity: body.quantity } : row)
      if (method === 'DELETE') rows = rows.filter(row => row.skuId !== id)
      return { data: cart() }
    }
    if (path === '/api/v1/diy/pages/cart') return { data: { page: null, components: [] } }
    if (path === '/api/v1/products') {
      const list = query.get('sort') === 'price_desc' ? [{ ...product('102'), title: '高价商品', price: 2000 }, { ...product(), price: 999 }] : [{ ...product(), price: 999 }, { ...product('102'), title: '高价商品', price: 2000 }]
      return { data: { list, pagination: { total: 2 } } }
    }
    if (/^\/api\/v1\/products\/\d+$/.test(path)) return { data: product(path.split('/').at(-1)) }
    if (path.endsWith('/stats')) return { data: { total: 1, good: 1, withImages: 1 } }
    if (/\/review\/product\/\d+(\/summary)?$/.test(path)) return { data: { list: [review], total: 1, page: Number(query.get('page') || 1) } }
    if (path === '/api/v1/review' && method === 'POST') return { data: { id: 100 } }
    if (path === '/api/v1/upload/image') return { data: { url: 'https://assets.example/review.png' } }
    if (path === '/api/v1/group-buy/products/7/102') return { data: { ...product('102'), activityInfo: { activityId: 7, minPeople: 2, status: 'active', startTime: '2020-01-01T00:00:00Z', endTime: '2030-01-01T00:00:00Z' } } }
    if (path === '/api/v1/group-buy/products/7/groups') return { data: [{ groupNo: 'GROUP-10', leaderNickname: '测试团长', joinedCount: 1, needCount: 2, expireTime: '2030-01-01T00:00:00Z' }] }
    if (/\/seckill\/products\/\d+\/102$/.test(path)) {
      const id = path.split('/').at(-2)
      return { data: { ...product('102'), minSalePrice: 599, skuList: [{ skuId: '502', stockInfo: { stockQuantity: 8 }, priceInfo: [{ price: 599 }], specInfo: [] }], activityInfo: { activityId: 6, sessionId: id, maxQuantityPerUser: 2, status: id === '9' ? 'pending' : id === '10' ? 'ended' : 'active', startTime: id === '9' ? '2030-01-01T00:00:00Z' : '2020-01-01T00:00:00Z', endTime: id === '10' ? '2021-01-01T00:00:00Z' : '2030-01-01T00:00:00Z' } } }
    }
    if (path === '/api/v1/member/coupons') return { data: [] }
    if (path === '/api/v1/order/preview') return { data: { settleType: 1, goodsCount: 1, price: { goodsAmount: 999, payAmount: 999 }, items: [], storeGoodsList: [] } }
    return undefined
  })
}
