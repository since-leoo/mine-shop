import { expect, test } from '@playwright/test'
import { execFileSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import { capturePage, expectHealthy, expectWithinViewport, productImage, setupPage, type ApiCall } from './helpers'

// 与 fresh 部署使用同一份 PHP schema；不启动应用容器，也不读取 .env 或数据库。
const root = fileURLToPath(new URL('../../../', import.meta.url))
const definitions = JSON.parse(execFileSync('php', ['-r', 'require "tests/bootstrap-unit.php"; require "plugins/diy/Database/Seeders/support/DefaultDiyPageSchemas.php"; echo json_encode(\\seeders\\support\\DefaultDiyPageSchemas::all());'], { cwd: root, encoding: 'utf8' }))
function seeded(key: string) { return structuredClone(definitions.find((item: any) => item.page_key === key && item.page_type === 'h5').schema) }
const center = {
  userInfo: { nickName: '接口中的真实会员', phoneNumber: '13800138000', avatarUrl: '', balance: 123456 },
  countsData: [{ type: 'coupon', num: 3 }, { type: 'point', num: 527 }, { type: 'collect', num: 0 }],
  orderTagInfos: [{ orderNum: 2 }, { orderNum: 0 }, { orderNum: 4 }, { orderNum: 0 }, { orderNum: 1 }],
}
const coupon = { coupon_id: 21, name: '全场精选长名称满额八五折优惠券', type: 'discount', discount_value: 850, threshold_amount: 1999, is_receivable: true }
const detail = { key: '21', title: coupon.name, type: 'discount', value: 850, base: 1999, status: 'default', useNotes: '订单满19.99元可使用', storeAdapt: '商城通用' }
function shared(call: ApiCall) {
  if (call.path.startsWith('/api/v1/diy/pages/')) return { data: seeded(call.path.split('/').at(-1)!) }
  if (call.path === '/api/v1/cart') return { data: { storeGoods: [] } }
  if (call.path === '/api/v1/products') return { data: { list: [], total: 0 } }
  if (call.path === '/api/v1/member/center') return { data: center }
  if (call.path === '/api/v1/member/wallet/transactions') return { data: { list: [], total: 0 } }
  if (call.path === '/api/v1/coupons/available') return { data: { list: [] } }
  if (call.path === '/api/v1/coupons/21') return { data: { detail } }
  if (call.path === '/api/v1/member/coupons') return { data: [] }
  if (call.path === '/api/v1/order/preview') return { data: { settleType: 1, goodsCount: 2, price: { goodsAmount: 1998, payAmount: 1998 }, items: [], storeGoodsList: [] } }
}

test('fresh DIY空购物车不虚构商品，底栏仍可回首页', async ({ page, viewport }) => {
  const api = await setupPage(page, shared)
  await page.goto('/#/pages/cart/index')
  const cart = page.locator('.cart-page--diy:visible')
  await expect(cart).toContainText('购物车还是空的，先去挑点喜欢的商品吧')
  await expect(cart.locator('.goods-card')).toHaveCount(0)
  await expect(cart).toContainText('暂无商品，敬请期待')
  await expectWithinViewport(cart.locator('.cart-page__diy-body, .diy-product-group'), viewport!.width)
  await capturePage(page, `diy-cart-empty-${viewport!.width}.png`)
  await cart.locator('.h5-tab-bar__item').filter({ hasText: '首页' }).click()
  await expect(page).toHaveURL(/\/pages\/home\/index$/)
  await expect(page.locator('.home--diy:visible')).toBeVisible()
  expectHealthy(api)
})

test('fresh DIY购物车有真实商品时显示结算和分单位金额，不混入空态', async ({ page, viewport }) => {
  const api = await setupPage(page, call => call.path === '/api/v1/cart' ? { data: { storeGoods: [{ storeId: '1', storeName: '官方店铺', promotionGoodsList: [{ goodsPromotionList: [{ skuId: '501', spuId: '101', title: '真实购物车长标题家居好物大容量款', thumb: productImage, price: 999, quantity: 2, stockQuantity: 8, isSelected: true }] }] }] } } : shared(call))
  await page.goto('/#/pages/cart/index')
  const cart = page.locator('.cart-page:visible')
  await expect(cart.locator('.cart-goods-item')).toHaveCount(1)
  await expect(cart.locator('.cart-bar__total-price')).toHaveText('¥19.98')
  await expect(cart.getByText('购物车还是空的，先去挑点喜欢的商品吧')).toHaveCount(0)
  await expectWithinViewport(cart.locator('.cart-goods-item, .cart-bar__right, .cart-bar__settle'), viewport!.width)
  await cart.locator('.cart-bar__settle').click()
  await expect(page).toHaveURL(/order-confirm\/index\?type=cart$/)
  expectHealthy(api)
})

test('fresh DIY个人中心实时覆盖资产与订单数量，统计不被header遮挡且余额可跳转', async ({ page, viewport }) => {
  const api = await setupPage(page, shared)
  await page.goto('/#/pages/usercenter/index')
  const screen = page.locator('.usercenter--diy:visible')
  await expect(screen.locator('.diy-user-profile-header__name')).toHaveText(center.userInfo.nickName)
  await expect(screen.locator('.diy-user-profile-header__code')).toHaveText('手机号：13800138000')
  await expect(screen.locator('.diy-user-stats__value')).toHaveText(['3', '527', '¥1234.56', '0'])
  await expect(screen.locator('.diy-user-order-panel__badge-text')).toHaveText(['2', '4', '1'])
  await expectWithinViewport(screen.locator('.diy-user-stats, .diy-user-stats__value, .diy-user-order-panel'), viewport!.width)
  for (const value of await screen.locator('.diy-user-stats__value').all()) {
    expect(await value.evaluate(element => {
      const box = element.getBoundingClientRect()
      return !!document.elementFromPoint(box.x + box.width / 2, box.y + box.height / 2)?.closest('.diy-user-stats')
    })).toBe(true)
  }
  const avatar = screen.locator('.diy-user-profile-header__avatar-img img')
  await expect.poll(() => avatar.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0)).toBe(true)
  await capturePage(page, `diy-usercenter-${viewport!.width}.png`)
  await screen.locator('.diy-user-stats__item').filter({ hasText: '收藏' }).click()
  await expect(page).toHaveURL(/\/pages\/usercenter\/index$/)
  await screen.locator('.diy-user-stats__item').filter({ hasText: '余额' }).click()
  await expect(page).toHaveURL(/wallet-transactions\/index$/)
  await expect(page.locator('.wallet-page:visible')).toBeVisible()
  await page.goBack()
  await expect(screen).toBeVisible()
  await screen.locator('.diy-user-stats__item').filter({ hasText: '积分' }).click()
  await expect(page).toHaveURL(/wallet-transactions\/index\?type=points$/)
  await expect(page.locator('.wallet-page:visible')).toHaveCount(1)
  await expect(page.locator('.wallet-tabs__item--active:visible')).toHaveText('积分')
  expectHealthy(api)
})

test('fresh DIY个人中心不伪造邀请码，远程头像失败使用可加载的本地默认头像', async ({ page }) => {
  const api = await setupPage(page, call => call.path === '/api/v1/member/center' ? { data: { ...center, userInfo: { ...center.userInfo, phoneNumber: '', avatarUrl: 'https://invalid.example/avatar.png' } } } : shared(call))
  await page.goto('/#/pages/usercenter/index')
  const screen = page.locator('.usercenter--diy:visible')
  await expect(screen.locator('.diy-user-profile-header__name')).toHaveText(center.userInfo.nickName)
  await expect(screen.locator('.diy-user-profile-header__code')).toHaveCount(0)
  await expect(screen).not.toContainText('WARM2026')
  const avatar = screen.locator('.diy-user-profile-header__avatar-img img')
  await expect.poll(() => avatar.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0 && !img.src.includes('invalid.example'))).toBe(true)
  expectHealthy(api)
})

test('fresh DIY领券中心无券无商品时保持真实空态', async ({ page, viewport }) => {
  const api = await setupPage(page, shared)
  await page.goto('/#/pages/coupon/coupon-center/index')
  const group = page.locator('.diy-coupon-group:visible')
  await expect(group).toContainText('暂无可领取优惠券')
  await expect(group.locator('.diy-coupon-group__item')).toHaveCount(0)
  await expect(page.locator('.goods-card')).toHaveCount(0)
  await expectWithinViewport(group, viewport!.width)
  expectHealthy(api)
})

test('fresh DIY八五折券显示真实门槛、领取一次并可到详情和适用商品', async ({ page, viewport }) => {
  let release!: () => void
  const held = new Promise<void>(resolve => { release = resolve })
  const api = await setupPage(page, async call => {
    if (call.path === '/api/v1/coupons/available') return { data: { list: [coupon, { ...coupon, coupon_id: 22, type: 'price', discount_value: 1050 }] } }
    if (call.path === '/api/v1/member/coupons/receive') { await held; return { data: {} } }
    return shared(call)
  })
  await page.goto('/#/pages/coupon/coupon-center/index')
  const tickets = page.locator('.diy-coupon-group__item:visible')
  await expect(tickets.first().locator('.diy-coupon-group__amount')).toHaveText('8.5折')
  await expect(tickets.first().locator('.diy-coupon-group__condition')).toHaveText('满19.99可用')
  await expect(tickets.nth(1).locator('.diy-coupon-group__amount')).toHaveText('¥10.5')
  await expectWithinViewport(page.locator('.diy-coupon-group__item, .diy-coupon-group__footer, .diy-coupon-group__receive'), viewport!.width)
  const button = tickets.first().locator('.diy-coupon-group__receive')
  await button.click({ clickCount: 2 })
  await expect(button).toHaveText('领取中')
  expect(api.requests.filter(call => call.path.endsWith('/coupons/receive'))).toHaveLength(1)
  expect(api.requests.find(call => call.path.endsWith('/coupons/receive'))?.body).toEqual({ coupon_id: '21' })
  expect(page.url()).toContain('/coupon-center/')
  release()
  await expect(button).toHaveText('已领取')
  await capturePage(page, `diy-coupon-center-${viewport!.width}.png`)
  await tickets.first().locator('.diy-coupon-group__name').click()
  await expect(page).toHaveURL(/coupon-detail\/index\?id=21$/)
  await expect(page.locator('.coupon-detail-page__hero-amount')).toHaveText('满19.99元8.5折')
  await page.locator('.coupon-detail-page__btn--primary').click()
  await expect(page).toHaveURL(/coupon-activity-goods\/index\?id=21$/)
  expectHealthy(api)
})
