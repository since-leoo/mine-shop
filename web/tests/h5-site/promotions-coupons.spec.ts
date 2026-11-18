import { expect, test } from '@playwright/test'
import { capturePage, expectHealthy, expectWithinViewport, productImage, setupPage, type ApiCall, type ApiReply } from './helpers'

const title = '限时精选多功能家用好物超长商品名称大容量升级款适合全家一起使用'
const product = (id: number, extras: any = {}) => ({ id, spuId: String(id), thumb: productImage, title: `${title}${id}`, price: 999, originPrice: 1999, soldNum: 38, tags: ['精选好物'], ...extras })
const coupon = (extras: any = {}) => ({ coupon_id: '21', name: '全场精选商品优惠券长名称测试', type: 'discount', discount_value: 850, threshold_amount: 1999, available_quantity: 8, is_receivable: true, ...extras })
const detail = { key: '21', title: '全场八五折优惠券', type: 'discount', value: 850, base: 1999, status: 'default', storeAdapt: '商城通用', useNotes: '结算时满足门槛即可使用' }
const base = (call: ApiCall): ApiReply | undefined => {
  if (call.path.includes('/diy/')) return { data: { page: null, components: [] } }
  if (call.path === '/api/v1/cart') return { data: { storeGoods: [] } }
  if (/\/review\/product\/\d+\/(summary|stats)$/.test(call.path)) return { data: { list: [], total: 0 } }
  if (/\/products\/\d+$/.test(call.path)) return { data: { ...product(11), skuList: [], spuTagList: [], primaryImage: productImage, images: [productImage] } }
  if (/\/products\/\d+\//.test(call.path) || call.path.includes('/reviews')) return { data: { list: [], total: 0 } }
  if (call.path.includes('/group-buy/products/')) return { data: { ...product(11), groupBuyId: '101', minPeople: 2 } }
  if (call.path.includes('/seckill/products/')) return { data: product(11) }
  return undefined
}
const groupProduct = (id: number, people: number) => product(id, { activityId: String(100 + id), minPeople: people, groupCount: 5, successGroupCount: 3 })
const liveGroup = (name: string, extras: any = {}) => ({ groupNo: name, leaderNickname: name, leaderAvatar: productImage, joinedCount: 1, needCount: 2, expireTime: new Date(Date.now() + 60_000).toISOString(), ...extras })

test('团购展示全部商品、正确价格，筛选后只参当前活动的有效团', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/group-buy/products') return { data: { list: [groupProduct(1, 2), groupProduct(2, 3), groupProduct(3, 2), groupProduct(4, 2)] } }
    if (call.path.endsWith('/101/groups')) return { data: [liveGroup('二人团'), liveGroup('过期团', { expireTime: new Date(Date.now() - 1000).toISOString() }), liveGroup('满员团', { joinedCount: 2 })] }
    if (call.path.endsWith('/102/groups')) return { data: [liveGroup('三人团', { needCount: 3 })] }
    return base(call)
  })
  await page.goto('/#/pages/promotion/group-buy/index')
  await expect(page.locator('.group-buy__featured-price')).toHaveText('¥9.99')
  await expect(page.locator('.group-buy__list-name').filter({ hasText: title })).toHaveCount(3)
  await expect(page.getByText('二人团的团', { exact: true })).toBeVisible()
  await expect(page.getByText('过期团的团', { exact: true })).toHaveCount(0)
  await expect(page.getByText('满员团的团', { exact: true })).toHaveCount(0)
  await expectWithinViewport(page.locator('.group-buy__featured-main, .group-buy__featured-price-row, .group-buy__metrics'), page.viewportSize()!.width)
  if (page.viewportSize()!.width === 375) await capturePage(page, 'h5-group-buy-after-375.png')
  await page.locator('.group-buy__nav-chip').filter({ hasText: '3人团' }).click()
  await expect(page.getByText('三人团的团', { exact: true })).toBeVisible()
  await page.locator('.group-buy__featured-btn').click()
  await expect(page).toHaveURL(/groupBuyId=102.*groupNo=/)
  expect(decodeURIComponent(page.url())).toContain('groupNo=三人团')
  expectHealthy(api)
})

test('团购切换时旧请求不能把参团编号附到新活动', async ({ page }) => {
  let release!: () => void
  const held = new Promise<void>(resolve => { release = resolve })
  const api = await setupPage(page, async call => {
    if (call.path === '/api/v1/group-buy/products') return { data: { list: [groupProduct(1, 2), groupProduct(2, 3)] } }
    if (call.path.endsWith('/101/groups')) { await held; return { data: [liveGroup('旧活动团')] } }
    if (call.path.endsWith('/102/groups')) return { data: [liveGroup('新活动团', { needCount: 3 })] }
    return base(call)
  })
  await page.goto('/#/pages/promotion/group-buy/index')
  await expect.poll(() => api.requests.some(call => call.path.endsWith('/101/groups'))).toBe(true)
  await page.locator('.group-buy__nav-chip').filter({ hasText: '3人团' }).click()
  await expect(page.getByText('新活动团的团', { exact: true })).toBeVisible()
  const oldResponse = page.waitForResponse(response => response.url().includes('/101/groups'))
  release()
  await (await oldResponse).finished()
  await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))))
  await expect(page.getByText('旧活动团的团', { exact: true })).toHaveCount(0)
  await page.locator('.group-buy__featured-btn').click()
  await expect(page).toHaveURL(/groupBuyId=102.*groupNo=/)
  expect(decodeURIComponent(page.url())).toContain('groupNo=新活动团')
  expectHealthy(api)
})

test('秒杀尊重活动参数，可预览未来场次且不显示虚假的抢购状态', async ({ page }) => {
  const now = Date.now()
  const sessions = [
    { id: '901', activityId: '99', time: '别的活动', status: 'ongoing', startTime: now - 10000, endTime: now + 60000 },
    { id: '701', activityId: '7', time: '当前场', status: 'ongoing', startTime: now - 10000, endTime: now + 60000 },
    { id: '702', activityId: '7', time: '下一场', status: 'upcoming', startTime: now + 3600000, endTime: now + 7200000 },
  ]
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/seckill/sessions') return { data: sessions }
    if (call.path === '/api/v1/seckill/products') {
      const sessionId = call.query.get('session_id') || '701'
      return { data: { list: [product(11, { activityId: '7', sessionId, stockQuantity: 8, soldQuantity: 2, totalQuantity: 10 })], time: 60 } }
    }
    return base(call)
  })
  await page.goto('/#/pages/promotion/detail/index?activityId=7')
  await expect(page.locator('.promotion-detail__featured-name')).toContainText(title)
  expect(api.requests.find(call => call.path.endsWith('/seckill/products'))?.query.get('activity_id')).toBe('7')
  await expect(page.locator('.promotion-detail__filter-chip').filter({ hasText: '别的活动' })).toHaveCount(0)
  await expect(page.locator('.promotion-detail__featured-cta')).toHaveText('立即抢购')
  await expect(page.getByText('暂无活动商品', { exact: true })).toHaveCount(0)
  await page.locator('.promotion-detail__filter-chip').filter({ hasText: '下一场' }).click()
  await expect(page.locator('.promotion-detail__featured-cta')).toHaveText('即将开抢')
  await expect(page.locator('.promotion-detail__featured-progress-meta')).toContainText('距开抢时间')
  await expectWithinViewport(page.locator('.promotion-detail__featured-card, .promotion-detail__featured-price-row, .promotion-detail__featured-bottom'), page.viewportSize()!.width)
  if (page.viewportSize()!.width === 375) await capturePage(page, 'h5-seckill-after-375.png')
  await page.locator('.promotion-detail__featured-card').click()
  await expect(page).toHaveURL(/activityId=7.*sessionId=702/)
  expectHealthy(api)
})

test('秒杀到期更新状态，倒计时不会复活', async ({ page }) => {
  const deadline = Date.now() + 4000
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/seckill/sessions') return { data: [{ id: '701', activityId: '7', time: '限时场', status: 'ongoing', startTime: Date.now() - 60000, endTime: deadline }] }
    if (call.path === '/api/v1/seckill/products') return { data: { list: [product(11, { sessionId: '701', activityId: '7', remainingTime: 99, stockQuantity: 8, soldQuantity: 2, totalQuantity: 10 })], time: 99 } }
    return base(call)
  })
  await page.goto('/#/pages/promotion/detail/index?activityId=7')
  await expect(page.locator('.promotion-detail__featured-cta')).toHaveText('立即抢购')
  await expect(page.locator('.promotion-detail__featured-cta')).toHaveText('已结束', { timeout: 7000 })
  await expect(page.locator('.promotion-detail__countdown-num')).toHaveText(['00', '00', '00'])
  await expect(page.locator('.promotion-detail__filter-chip--active')).toContainText('已结束')
  expectHealthy(api)
})

test('领券中心使用真实券单位、门槛和领取状态，连点只发一次请求', async ({ page }) => {
  let release!: () => void
  const held = new Promise<void>(resolve => { release = resolve })
  const api = await setupPage(page, async call => {
    if (call.path === '/api/v1/coupons/available') return { data: { list: [coupon(), coupon({ coupon_id: '22', type: 'price', discount_value: 1050 })] } }
    if (call.path === '/api/v1/member/coupons/receive') { await held; return { data: {} } }
    return base(call)
  })
  await page.goto('/#/pages/coupon/coupon-center/index')
  const tickets = page.locator('.coupon-ticket')
  await expect(tickets.first().locator('.coupon-ticket__value')).toHaveText('8.5折')
  await expect(tickets.first().locator('.coupon-ticket__condition')).toHaveText('满19.99元可用')
  await expect(tickets.nth(1).locator('.coupon-ticket__value')).toHaveText('10.5')
  await expectWithinViewport(page.locator('.coupon-ticket, .coupon-ticket__right, .coupon-ticket__status, .coupon-ticket__receive-btn'), page.viewportSize()!.width)
  const button = tickets.first().locator('.coupon-ticket__receive-btn')
  await button.click({ clickCount: 2 })
  await expect(button).toHaveText('领取中…')
  expect(api.requests.filter(call => call.path.endsWith('/coupons/receive'))).toHaveLength(1)
  expect(api.requests.find(call => call.path.endsWith('/coupons/receive'))?.body).toEqual({ coupon_id: '21' })
  release()
  await expect(button).toHaveText('已领取')
  if (page.viewportSize()!.width === 375) {
    await expect(page.getByText('领取成功', { exact: true })).not.toBeVisible()
    await capturePage(page, 'h5-coupon-center-after-375.png')
  }
  expectHealthy(api)
})

test('我的券到详情再立即使用保持券编号，适用商品请求首页且金额正确', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/member/coupons') return { data: [{ key: '21', title: detail.title, type: 'discount', value: 850, base: 1999 }] }
    if (call.path === '/api/v1/coupons/21') return { data: { detail } }
    if (call.path === '/api/v1/products') return { data: { list: [product(11), product(12)] } }
    return base(call)
  })
  await page.goto('/#/pages/coupon/coupon-list/index')
  await expect(page.locator('.coupon-ticket__value')).toHaveText('8.5折')
  await page.locator('.coupon-ticket').click()
  await expect(page).toHaveURL(/coupon-detail\/index\?id=21/)
  await expect(page.locator('.coupon-detail-page__hero-amount')).toHaveText('满19.99元8.5折')
  await expect(page.locator('.coupon-detail-page__ticket-status')).toHaveText('可使用')
  await expectWithinViewport(page.locator('.coupon-detail-page__ticket, .coupon-detail-page__footer-inner'), page.viewportSize()!.width)
  if (page.viewportSize()!.width === 375) await capturePage(page, 'h5-coupon-detail-after-375.png')
  await page.locator('.coupon-detail-page__btn--primary').click()
  await expect(page).toHaveURL(/coupon-activity-goods\/index\?id=21/)
  await expect(page.locator('.coupon-goods-page__desc')).toContainText('满19.99元8.5折')
  await expect(page.locator('.coupon-goods-page__price').first()).toHaveText('¥9.99')
  expect(api.requests.find(call => call.path === '/api/v1/products')?.query.get('page')).toBe('1')
  await expectWithinViewport(page.locator('.coupon-goods-page__card, .coupon-goods-page__price-row'), page.viewportSize()!.width)
  expectHealthy(api)
})

test('热卖榜低于十元的商品以分转元，长标题不挤压价格按钮', async ({ page }) => {
  const api = await setupPage(page, call => call.path === '/api/v1/products' ? { data: { list: [product(11), product(12)] } } : base(call))
  await page.goto('/#/pages/promotion/hot-ranking/index')
  await expect(page.locator('.hot-ranking__top-card .hot-ranking__price')).toHaveText('¥9.99')
  await expect(page.locator('.hot-ranking__top-sell')).toHaveText('累计销量 38')
  await expectWithinViewport(page.locator('.hot-ranking__top-card, .hot-ranking__item, .hot-ranking__price-row'), page.viewportSize()!.width)
  await page.locator('.hot-ranking__item-btn').click()
  await expect(page).toHaveURL(/spuId=12/)
  expectHealthy(api)
})
