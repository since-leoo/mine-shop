import { expect, test } from '@playwright/test'
import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { capturePage, expectHealthy, expectWithinViewport, productImage, setupPage, type ApiCall } from './helpers'
// Snapshot exported from HEAD:seeders/support/DefaultDiyPageSchemas.php before the fresh defaults changed.
const legacyHome = JSON.parse(readFileSync(new URL('./fixtures/home-legacy.json', import.meta.url), 'utf8'))

async function expectHomeDimensions(page: import('@playwright/test').Page) {
  const home = page.locator('.home--diy:visible')
  const geometry = await home.evaluate(element => {
    const root = element.getBoundingClientRect()
    const search = element.querySelector('.diy-search-bar')!.getBoundingClientRect()
    const grid = getComputedStyle(element.querySelector('.diy-marketing-entry__grid')!)
    const card = getComputedStyle(element.querySelector('.diy-marketing-entry__card')!)
    const nav = getComputedStyle(element.querySelector('.diy-quick-nav__icon-wrap')!)
    return { top: root.top, searchTop: search.top - root.top, gap: grid.columnGap, radius: card.borderRadius, navWidth: nav.width, navRadius: nav.borderRadius }
  })
  expect(geometry.top).toBeCloseTo(0, 1)
  expect(geometry.searchTop).toBeCloseTo(4, 1)
  expect(geometry.gap).toBe('10px')
  expect(geometry.radius).toBe('10px')
  expect(geometry.navWidth).toBe('48px')
  expect(geometry.navRadius).toBe('16px')
}

// Executes only the PHP schema definitions, without .env/container/database boot.
const root = fileURLToPath(new URL('../../../', import.meta.url))
const definitions = JSON.parse(execFileSync('php', ['-r', 'require "tests/bootstrap-unit.php"; require "plugins/diy/Database/Seeders/support/DefaultDiyPageSchemas.php"; echo json_encode(\\seeders\\support\\DefaultDiyPageSchemas::all());'], { cwd: root, encoding: 'utf8' }))
function seeded(key: string) { return structuredClone(definitions.find((item: any) => item.page_key === key && item.page_type === 'h5').schema) }
const categories = [{ id: 31, name: '茶具与厨房精选', thumbnail: productImage, children: [] }, { id: 32, name: '家居收纳', children: [{ id: 33, name: '置物', children: [{ id: 34, name: '轻巧便携的收纳杯与长名称展示', thumbnail: productImage }] }] }]
function shared(call: ApiCall) {
  if (call.path === '/api/v1/categories') return { data: categories }
  if (call.path === '/api/v1/products') return { data: { list: [], total: 0 } }
  if (call.path === '/api/v1/products/901') return { data: { spuId: '901', title: '真实接口测试商品', minSalePrice: 599, maxSalePrice: 599, skuList: [], images: [], spuStockQuantity: 0 } }
  if (call.path === '/api/v1/review/product/901/summary') return { data: { list: [], total: 0 } }
  if (call.path === '/api/v1/review/product/901/stats') return { data: { total: 0, goodRate: 0 } }
  if (call.path === '/api/v1/cart' && call.method === 'GET') return { data: { items: [], total: 0 } }
  if (call.path === '/api/v1/seckill/sessions') return { data: { list: [] } }
  if (call.path === '/api/v1/seckill/products') return { data: { list: [] } }
  if (call.path === '/api/v1/diy/pages/category') return { data: seeded('category') }
}

test('fresh DIY首页不展示假商品，五个入口完整且分类tab可以进入叶级分类', async ({ page, viewport }) => {
  const api = await setupPage(page, call => call.path === '/api/v1/diy/pages/home' ? { data: seeded('home') } : shared(call))
  await page.goto('/#/pages/home/index')
  const home = page.locator('.home--diy:visible')
  await expect(home.locator('.diy-quick-nav__item')).toHaveCount(5)
  await expect(home.getByText('暂无商品，敬请期待')).toBeVisible()
  await expect(home.locator('.goods-card')).toHaveCount(0)
  await expectHomeDimensions(page)
  await expectWithinViewport(home.locator('.diy-quick-nav, .diy-marketing-entry'), viewport!.width)
  await capturePage(page, `home-diy-${viewport!.width}.png`)
  await home.getByText('全部分类', { exact: true }).click()
  await expect(page).toHaveURL(/\/pages\/category\/index$/)
  const panel = page.locator('.category-page:visible .diy-category-panel')
  await expect(panel.locator('.diy-category-panel__card')).toHaveCount(1)
  await expect(panel.locator('.diy-category-panel__name')).toHaveText('茶具与厨房精选')
  await panel.getByText('家居收纳', { exact: true }).click()
  await expect(panel.locator('.diy-category-panel__name')).toContainText('轻巧便携')
  await expectWithinViewport(panel.locator('.diy-category-panel__content, .diy-category-panel__card'), viewport!.width)
  await capturePage(page, `category-diy-${viewport!.width}.png`)
  await panel.locator('.diy-category-panel__card').click()
  await expect(page).toHaveURL(/categoryId=34/)
  expectHealthy(api)
})

test('已发布旧版首页顶部背景连续、活动卡圆角和间距保留，搜索仍可点击', async ({ page, viewport }) => {
  const api = await setupPage(page, call => call.path === '/api/v1/diy/pages/home' ? { data: structuredClone(legacyHome) } : shared(call))
  await page.goto('/#/pages/home/index')
  const home = page.locator('.home--diy:visible')
  await expect(home.locator('.diy-marketing-entry__card')).toHaveCount(2)
  await expectHomeDimensions(page)
  await expect(home.locator('.diy-quick-nav__title')).toHaveText(['分类', '优惠券', '拼团活动', '热卖榜单', '我的'])
  await expectWithinViewport(home.locator('.diy-search-bar, .diy-banner, .diy-marketing-entry'), viewport!.width)
  await capturePage(page, `home-legacy-${viewport!.width}.png`)
  await home.locator('.diy-search-bar').click()
  await expect(page).toHaveURL(/\/pages\/goods\/search\/index$/)
  expectHealthy(api)
})

test('页面padding仅应用一次，运行时商品间距和图片边距支持自定义配置', async ({ page, viewport }) => {
  const schema = seeded('home')
  schema.page.style = { ...schema.page.style, width: '100%', padding: { top: 24, right: 8, bottom: 12, left: 8 } }
  const banner = schema.components.find((item: any) => item.type === 'banner')
  banner.style = {} // Existing schemas can rely entirely on the contained widthMode default.
  const products = schema.components.find((item: any) => item.type === 'product-group')
  products.props = { ...products.props, gap: 18, listPadding: { top: 0, right: 8, bottom: 14, left: 8 } }
  products.data.products = [901, 902].map(id => ({ id, name: `测试商品${id}`, min_price: 599, main_image: productImage }))
  const api = await setupPage(page, call => call.path === '/api/v1/diy/pages/home' ? { data: schema } : shared(call))
  await page.goto('/#/pages/home/index')
  const home = page.locator('.home--diy:visible')
  await expect(home.locator('.goods-card')).toHaveCount(2)
  const geometry = await home.evaluate(element => {
    const root = element.getBoundingClientRect()
    const search = element.querySelector('.diy-search-bar')!.getBoundingClientRect()
    const banner = getComputedStyle(element.querySelector('.diy-banner')!)
    const list = getComputedStyle(element.querySelector('.goods-list')!)
    const cards = Array.from(element.querySelectorAll('.goods-list__item')).map(item => item.getBoundingClientRect())
    return { top: root.top, searchTop: search.top - root.top, searchLeft: search.left - root.left, bannerMargin: banner.marginLeft, gap: list.columnGap, padding: [list.paddingTop, list.paddingRight, list.paddingBottom, list.paddingLeft], sameRow: Math.abs(cards[0].top - cards[1].top) < 1, actualGap: cards[1].left - cards[0].right }
  })
  expect(geometry).toMatchObject({ top: 0, searchTop: 28, searchLeft: 24, bannerMargin: '16px', gap: '18px', padding: ['0px', '8px', '14px', '8px'], sameRow: true })
  expect(geometry.actualGap).toBeCloseTo(18, 1)
  await expectWithinViewport(home.locator('.diy-renderer, .diy-search-bar, .diy-banner, .goods-list, .goods-list__item'), viewport!.width)
  expectHealthy(api)
})

test('DIY真实商品价格保留分，加号进入详情选规格且不提交伪SKU', async ({ page }) => {
  const schema = seeded('home')
  schema.components.find((item: any) => item.type === 'product-group').data.products = [{ id: 901, name: '真实接口测试商品', min_price: 599, max_price: 999, main_image: productImage }]
  const api = await setupPage(page, call => call.path === '/api/v1/diy/pages/home' ? { data: schema } : shared(call))
  await page.goto('/#/pages/home/index')
  const card = page.locator('.home:visible .goods-card')
  await expect(card).toContainText('真实接口测试商品')
  await expect(card.locator('.goods-card__price')).toContainText('5.99')
  await card.locator('.goods-card__add-cart').click()
  await expect(page).toHaveURL(/spuId=901/)
  expect(api.requests.some(call => call.path.includes('/cart') && call.method === 'POST')).toBe(false)
  expectHealthy(api)
})

test('DIY不可用时原生首页低价高价均正确，分类tab有效且只请求一次', async ({ page, viewport }) => {
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/diy/pages/home' || call.path === '/api/v1/diy/pages/category') return { data: { page: null, components: [] } }
    if (call.path === '/api/v1/home') return { data: { swiper: [productImage], sections: { featured: [{ spuId: 901, title: '低价测试商品', price: 599 }, { spuId: 902, title: '高价测试商品', price: 123456 }] } } }
    return shared(call)
  })
  await page.goto('/#/pages/home/index')
  const home = page.locator('.home--h5:visible')
  await expect(home).toContainText('5.99')
  await expect(home).toContainText('1234.56')
  await expectWithinViewport(home.locator('.home-default-card, .home-quick-entries'), viewport!.width)
  await home.getByText('全部分类', { exact: true }).click()
  await expect(page).toHaveURL(/\/pages\/category\/index$/)
  await expect(page.locator('.category-page:visible .category-card')).toHaveCount(1)
  await expect(page.locator('.category-page:visible .category-card')).toContainText('茶具与厨房精选')
  expect(api.requests.filter(call => call.path === '/api/v1/categories')).toHaveLength(1)
  expectHealthy(api)
})

test('分类请求失败显示重试并可恢复，不永久空白', async ({ page }) => {
  let count = 0
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/categories') return ++count === 1 ? { code: 500, status: 500, message: '分类暂不可用' } : { data: categories }
    return shared(call)
  })
  await page.goto('/#/pages/category/index')
  await expect(page.getByText('分类加载失败', { exact: true })).toBeVisible()
  await page.getByText('点击重试', { exact: true }).click()
  await expect(page.locator('.diy-category-panel__card')).toHaveCount(1)
  expect(count).toBe(2)
  expectHealthy(api)
})
