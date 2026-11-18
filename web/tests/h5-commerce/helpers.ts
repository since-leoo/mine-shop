import { expect, type Locator, type Page } from '@playwright/test'
import { join } from 'node:path'

export const addresses = [
  {
    id: '11', name: '陈晓明（国际事业部行政收货中心）', phone: '13800138000',
    provinceName: '广东省', cityName: '深圳市', districtName: '南山区',
    provinceCode: '440000', cityCode: '440300', districtCode: '440305',
    detailAddress: '高新技术产业园创新科技大厦二期南座二十六层国际事业部前台收发室，请工作日上午联系收件人后送达',
    isDefault: 1,
  },
  {
    id: '12', name: '李小华', phone: '13900139000',
    provinceName: '浙江省', cityName: '杭州市', districtName: '西湖区',
    provinceCode: '330000', cityCode: '330100', districtCode: '330106',
    detailAddress: '文三路 100 号电商科技中心 B 座 1208 室', isDefault: 0,
  },
]
export const goods = [{ skuId: '501', quantity: 2, storeId: '1', storeName: '商城' }]
export const confirmUrl = `/#/pages/order/order-confirm/index?goodsRequestList=${encodeURIComponent(JSON.stringify(goods))}`
export const addressUrl = '/#/pages/user/address/list/index'
export const selectedAddressKey = 'order.confirm.selectedAddress'
export const coupon = { id: '20', couponId: '20', title: '满百减十二元券', discountValue: 1200, base: 10000, type: 'amount', timeLimit: '2030-12-31' }
export const secondCoupon = { ...coupon, id: '21', couponId: '21', title: '订单切换五元券', discountValue: 500 }
const productImage = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="80" height="80"%3E%3Crect width="80" height="80" fill="%23f0ded5"/%3E%3C/svg%3E'

export async function mockCommerce(page: Page, baseURL: string, options: { largeAmount?: boolean; coupons?: boolean } = {}) {
  const requests: Array<{ path: string; method: string; body: any; authorization: string }> = []
  const errors: string[] = []
  let list = structuredClone(addresses)
  const goodsAmount = options.largeAmount ? 19_999_998 : 39_998
  const shippingFee = 600
  const activityDiscount = 800
  let latestPayAmount = goodsAmount + shippingFee - activityDiscount
  await page.addInitScript(() => {
    localStorage.setItem('accessToken', JSON.stringify({ data: 'commerce-test-token' }))
    localStorage.setItem('memberProfile', JSON.stringify({ data: { id: 41, nickname: '订单回归会员', phone: '13800138000', avatar: '' } }))
  })
  page.on('pageerror', error => errors.push(error.message))
  await page.route('**/*', route => new URL(route.request().url()).origin === baseURL ? route.continue() : route.abort())
  await page.route('**/api/**', async route => {
    const req = route.request()
    const url = new URL(req.url())
    const path = url.pathname
    if (req.method() === 'OPTIONS') return route.fulfill({ status: 204, headers: { 'access-control-allow-origin': '*', 'access-control-allow-methods': '*', 'access-control-allow-headers': '*' } })
    const body = req.postDataJSON() || {}
    requests.push({ path, method: req.method(), body, authorization: req.headers().authorization || '' })
    let data: any
    if (path === '/api/v1/member/addresses' && req.method() === 'GET') data = list
    else if (/^\/api\/v1\/member\/addresses\/\d+$/.test(path)) {
      const id = path.split('/').at(-1)
      if (req.method() === 'DELETE') { list = list.filter(item => item.id !== id); data = null }
      else if (req.method() === 'PUT') {
        list = list.map(item => item.id === id ? {
          ...item, name: body.name, phone: body.phone, detailAddress: body.detail, isDefault: body.is_default,
          provinceName: body.province, cityName: body.city, districtName: body.district,
        } : item)
        data = { id }
      }
      else data = list.find(item => item.id === id)
    }
    else if (path === '/api/v1/geo/regions') data = [{ code: '440000', name: '广东省', level: 'province' }]
    else if (path === '/api/v1/member/coupons') data = options.coupons ? [coupon, secondCoupon] : []
    else if (path === '/api/v1/order/preview') {
      // 故障版本曾每秒发出数百次预览；超过合理上限立即记录失败，避免回归测试压满浏览器。
      if (requests.filter(item => item.path === path).length > 20) {
        if (!errors.includes('订单预览出现请求循环')) errors.push('订单预览出现请求循环')
        return route.abort('failed')
      }
      const couponAmount = body.coupon_id === '20' ? 1200 : body.coupon_id === '21' ? 500 : 0
      latestPayAmount = goodsAmount + shippingFee - activityDiscount - couponAmount
      data = {
        settleType: 1, price: { goodsAmount, shippingFee, discountAmount: activityDiscount + couponAmount, payAmount: latestPayAmount },
        couponAmount, goodsCount: 2, userAddress: list.find(item => item.id === String(body.address_id)) || list[0],
        storeGoodsList: [{ storeId: '1', couponList: options.coupons ? [coupon, secondCoupon] : [] }],
        items: [{ skuId: '501', productImage, productName: '春日限定多功能轻便旅行收纳套装超长商品标题测试版', specValues: ['奶油杏色', '家庭组合装'], unitPrice: goodsAmount / 2, quantity: 2 }],
      }
    }
    else if (path === '/api/v1/order/submit') data = { tradeNo: 'browser-test-trade' }
    else if (path === '/api/v1/order/submit-result/browser-test-trade') data = { status: 'success' }
    else if (path === '/api/v1/order/pay-info/browser-test-trade') data = { payAmount: latestPayAmount, payMethods: [{ channel: 'balance', name: '测试余额', enabled: true }] }
    else if (path.includes('/diy/')) data = { page: null, components: [] }
    else {
      errors.push(`未声明接口 ${req.method()} ${path}`)
      return route.fulfill({ status: 501, json: { code: 501, message: errors.at(-1) }, headers: { 'access-control-allow-origin': '*' } })
    }
    await route.fulfill({ status: 200, json: { code: 200, data }, headers: { 'access-control-allow-origin': '*' } })
  })
  return { requests, errors, payAmount: goodsAmount + shippingFee - activityDiscount - (options.coupons ? 1200 : 0) }
}

export async function expectWithinViewport(locator: Locator, viewportWidth: number) {
  // Taro navigateTo 会滑入新页面；等待实际边界稳定，不把过渡中的 translateX 当成布局溢出。
  await expect(async () => {
    const boxes = await locator.evaluateAll(elements => elements.map(element => {
      const r = element.getBoundingClientRect()
      return { left: r.left, right: r.right, width: r.width, scrollWidth: (element as HTMLElement).scrollWidth, clientWidth: (element as HTMLElement).clientWidth }
    }))
    expect(boxes.length).toBeGreaterThan(0)
    for (const box of boxes) {
      expect(box.left).toBeGreaterThanOrEqual(-1)
      expect(box.right).toBeLessThanOrEqual(viewportWidth + 1)
      expect(box.width).toBeGreaterThan(0)
      expect(box.scrollWidth).toBeLessThanOrEqual(box.clientWidth + 1)
    }
  }).toPass({ timeout: 5_000 })
}

export async function assertNoUnexpectedRequests(api: Awaited<ReturnType<typeof mockCommerce>>) {
  expect(api.errors).toEqual([])
  expect(api.requests.filter(item => !item.path.includes('/geo/') && !item.path.includes('/diy/')).every(item => item.authorization === 'Bearer commerce-test-token')).toBe(true)
}

export async function captureCommerce(page: Page, file: string) {
  if (process.env.H5_COMMERCE_SCREENSHOTS) {
    await page.screenshot({ path: join(process.env.H5_COMMERCE_SCREENSHOTS, file), fullPage: true, animations: 'disabled' })
  }
}
