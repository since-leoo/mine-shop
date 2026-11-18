import { expect, test, type Page } from '@playwright/test'

const groupKey = 'MinePluginCenterRoute'
const tabItems = (page: Page) => page.locator('.mine-tabbar .tab-item')
const pluginTab = (page: Page) => tabItems(page).filter({ has: page.locator('.title', { hasText: /^插件中心$/ }) })
const snapshot = (page: Page) => page.evaluate(() => (window as any).__pluginTabsTest.snapshot())
const nav = (page: Page, label: string) => page.getByRole('navigation', { name: '测试导航' }).getByRole('link', { name: label, exact: true }).click()

async function open(page: Page, url = '/plugin-center') {
  await page.goto(url)
  await expect(page.getByTestId('current-url')).toHaveText(url)
  await expect(tabItems(page).first()).toBeVisible()
}
async function expectPluginGroup(page: Page, fullPath: string, count = 2) {
  await expect(page.getByTestId('current-url')).toHaveText(fullPath)
  await expect(pluginTab(page)).toHaveCount(1)
  await expect(pluginTab(page)).toHaveClass(/active/)
  await expect(tabItems(page)).toHaveCount(count)
  const state = await snapshot(page)
  const group = state.tabs.filter((item: any) => item.tabKey === groupKey)
  expect(group).toHaveLength(1)
  expect(group[0]).toMatchObject({ title: '插件中心', icon: 'carbon:application', fullPath })
  expect(group[0]).not.toHaveProperty('i18n')
  await expect(page.locator('.mine-tabbar .tab-item.active')).toHaveCount(1)
}
async function contextAction(page: Page, label: string) {
  await pluginTab(page).click({ button: 'right' })
  await page.locator('.mx-context-menu').getByText(label, { exact: true }).click()
}

const errors = new WeakMap<Page, string[]>()
test.beforeEach(async ({ page }) => {
  const captured: string[] = []
  errors.set(page, captured)
  page.on('pageerror', error => captured.push(error.message))
  await page.route('**/*', async (route) => {
    const url = new URL(route.request().url())
    if (url.hostname !== '127.0.0.1') return route.abort('blockedbyclient')
    if (url.pathname.startsWith('/admin/')) throw new Error(`Unexpected business API request: ${url}`)
    return route.continue()
  })
})
test.afterEach(async ({ page }) => { expect(errors.get(page)).toEqual([]) })

test('real center cards, plugin query navigation and return share one fixed-title tab', async ({ page }) => {
  await open(page)
  await page.locator('.plugin-card').filter({ has: page.getByRole('heading', { name: '供应链', exact: true }) }).click()
  await expectPluginGroup(page, '/plugin-center/since/supply-chain')
  await page.getByRole('button', { name: '订单子页', exact: true }).click()
  await expectPluginGroup(page, '/plugin-center/since/supply-chain?tab=orders&status=pending')
  await page.getByRole('link', { name: '切到微信设置', exact: true }).click()
  await expectPluginGroup(page, '/plugin-center/since/wechat?tab=settings')
  await page.getByRole('button', { name: '改当前标题', exact: true }).click()
  await expect(pluginTab(page).locator('.title')).toHaveText('插件中心')
  await page.getByRole('button', { name: '返回', exact: true }).click()
  await expectPluginGroup(page, '/plugin-center')
})

test('leaving the plugin tab then clicking it restores the last concrete URL and query after reload', async ({ page }) => {
  await open(page, '/plugin-center/since/supply-chain?tab=logs&page=2')
  await nav(page, '普通商品 A')
  await expect(page.getByTestId('current-url')).toHaveText('/items/1?view=a')
  await pluginTab(page).click()
  await expectPluginGroup(page, '/plugin-center/since/supply-chain?tab=logs&page=2', 3)
  await nav(page, '普通商品 A')
  await page.reload()
  await expect(page.getByTestId('current-url')).toHaveText('/items/1?view=a')
  await pluginTab(page).click()
  await expectPluginGroup(page, '/plugin-center/since/supply-chain?tab=logs&page=2', 3)
})

test('old persisted plugin tabs merge by registered ownership while retaining ordinary tabs and pinned state', async ({ page }) => {
  await page.addInitScript(() => {
    ;(window as any).__seedTabs = [
      { name: 'welcome', path: '/welcome', fullPath: '/welcome', title: '欢迎', affix: true },
      { name: 'LegacyWechat', path: '/legacy-wechat', fullPath: '/legacy-wechat?old=1', title: '微信旧菜单', i18n: 'old.wechat', affix: true },
      { name: 'Item', path: '/items/1', fullPath: '/items/1?view=a', title: '普通商品', affix: false },
      { name: 'MinePluginCenterRoute', path: '/plugin-center/since/supply-chain', fullPath: '/plugin-center/since/supply-chain?tab=orders', title: '旧供应链', affix: false },
      { name: 'HookPlugin', path: '/hook-plugin/31', fullPath: '/hook-plugin/31?tab=last', title: '旧 hook', affix: false },
      { name: 'Item', path: '/items/1', fullPath: '/items/1?view=b', title: '普通商品', affix: false },
    ]
  })
  await open(page, '/plugin-center/since/wechat?tab=current')
  await expectPluginGroup(page, '/plugin-center/since/wechat?tab=current', 4)
  const state = await snapshot(page)
  expect(state.tabs.find((item: any) => item.tabKey === groupKey).affix).toBe(true)
  expect(state.cached).toEqual(state.tabs)
  await nav(page, '普通商品 A')
  await pluginTab(page).click()
  await expectPluginGroup(page, '/plugin-center/since/wechat?tab=current', 4)
  await page.getByRole('button', { name: '关闭当前标签', exact: true }).click()
  await expectPluginGroup(page, '/plugin-center/since/wechat?tab=current', 4)
})

test('ordinary routes sharing a name or path still have distinct parameter and query tabs', async ({ page }) => {
  await open(page, '/welcome')
  for (const label of ['普通商品 A', '普通商品 B', '普通商品 C']) await nav(page, label)
  await expect(tabItems(page)).toHaveCount(4)
  const ordinary = tabItems(page).filter({ has: page.locator('.title', { hasText: /^普通商品$/ }) })
  await expect(ordinary).toHaveCount(3)
  expect((await snapshot(page)).tabs.filter((item: any) => item.name === 'Item').map((item: any) => item.fullPath)).toEqual([
    '/items/1?view=a', '/items/1?view=b', '/items/2?view=a',
  ])
  await ordinary.nth(1).click()
  await expect(page.getByTestId('current-url')).toHaveText('/items/1?view=b')
  await expect(page.locator('.mine-tabbar .tab-item.active')).toHaveCount(1)
  await expect(ordinary.nth(1)).toHaveClass(/active/)
})

test('legacy menus, plugin.views, nested views, hooks and static dashboard register the same group metadata', async ({ page }) => {
  await open(page)
  const state = await snapshot(page)
  for (const name of ['MinePluginCenterRoute', 'LegacyWechat', 'LegacyDiy', 'LegacyWorkbench', 'LegacyViews', 'LegacyViewsChild', 'HookPlugin', 'dashboard:workbench', 'dashboard:analysis', 'dashboard:report']) {
    expect(state.routes.find((route: any) => route.name === name), name).toMatchObject({ tabKey: groupKey, tabTitle: '插件中心', tabIcon: 'carbon:application' })
  }
  for (const name of ['Item', 'Other', 'welcome']) expect(state.routes.find((route: any) => route.name === name)).not.toHaveProperty('tabKey')
  for (const [label, url] of [
    ['微信旧菜单', '/legacy-wechat?tab=menu'],
    ['装修旧菜单', '/legacy-diy?page=2'],
    ['工作台旧菜单', '/legacy-workbench'],
    ['插件声明路由', '/views-plugin/17?tab=detail'],
    ['插件 hook 路由', '/hook-plugin/31?tab=detail'],
    ['静态工作台', '/dashboard/analysis'],
  ]) {
    await nav(page, label)
    await expectPluginGroup(page, url)
  }
})

test('closing the active plugin group chooses the adjacent ordinary tab and clears every grouped cache member', async ({ page }) => {
  await open(page, '/welcome')
  await nav(page, '普通商品 A')
  await nav(page, '微信旧菜单')
  await nav(page, '插件声明路由')
  await nav(page, '插件 hook 路由')
  const alive = (await snapshot(page)).alive
  expect(alive).toContain('ViewsFixture')
  expect(alive).toContain('HookFixture')
  await nav(page, '普通商品 B')
  await pluginTab(page).click()
  await pluginTab(page).locator('[data-icon="material-symbols:close-rounded"]').click()
  await expect(page.getByTestId('current-url')).toHaveText('/items/1?view=b')
  await expect(pluginTab(page)).toHaveCount(0)
  await expect(tabItems(page)).toHaveCount(3)
  const after = await snapshot(page)
  for (const name of ['LegacyWechat', 'LegacyViews', 'HookPlugin', 'ViewsFixture', 'HookFixture', 'plugins_since_wechat_views_PluginPage_vue']) expect(after.alive).not.toContain(name)
  expect(after.alive).toContain('modules_mall_views_product_index_vue')
})

for (const [action, expectedPaths] of [
  ['关闭左侧', ['/welcome', '/plugin-center/since/supply-chain?tab=orders&status=pending', '/items/1?view=b']],
  ['关闭右侧', ['/welcome', '/items/1?view=a', '/plugin-center/since/supply-chain?tab=orders&status=pending']],
  ['关闭其它', ['/welcome', '/plugin-center/since/supply-chain?tab=orders&status=pending']],
] as const) {
  test(`real context menu ${action} preserves fixed welcome and the current plugin URL`, async ({ page }) => {
    await open(page, '/welcome')
    await nav(page, '普通商品 A')
    await nav(page, '插件总览')
    await page.locator('.plugin-card').filter({ has: page.getByRole('heading', { name: '供应链', exact: true }) }).click()
    await page.getByRole('button', { name: '订单子页', exact: true }).click()
    await nav(page, '普通商品 B')
    await contextAction(page, action)
    await expectPluginGroup(page, '/plugin-center/since/supply-chain?tab=orders&status=pending', expectedPaths.length)
    expect((await snapshot(page)).tabs.map((item: any) => item.fullPath)).toEqual(expectedPaths)
  })
}

test('refresh via the actual context menu remounts the current child and retains its query', async ({ page }) => {
  const url = '/plugin-center/since/supply-chain?tab=logs&page=2'
  await open(page, url)
  const before = Number(await page.getByTestId('business-page').getAttribute('data-mount'))
  await contextAction(page, '刷新')
  await expect(page.getByTestId('business-page')).toHaveAttribute('data-mount', String(before + 1))
  await expectPluginGroup(page, url)
})

test('pinning the group persists across plugin switches and prevents closing current', async ({ page }) => {
  await open(page, '/plugin-center/since/supply-chain')
  await contextAction(page, '固定')
  await page.getByRole('link', { name: '切到微信设置', exact: true }).click()
  await expectPluginGroup(page, '/plugin-center/since/wechat?tab=settings')
  expect((await snapshot(page)).tabs.find((item: any) => item.tabKey === groupKey).affix).toBe(true)
  await page.getByRole('button', { name: '关闭当前标签', exact: true }).click()
  await expectPluginGroup(page, '/plugin-center/since/wechat?tab=settings')
})

test('old plugin cache opened from an ordinary page restores its last legacy URL', async ({ page }) => {
  await page.addInitScript(() => {
    ;(window as any).__seedTabs = [
      { name: 'welcome', path: '/welcome', fullPath: '/welcome', title: '欢迎', affix: true },
      { name: 'LegacyDiy', path: '/legacy-diy', fullPath: '/legacy-diy?page=1', title: '旧装修' },
      { name: 'MinePluginCenterRoute', path: '/plugin-center/since/supply-chain', fullPath: '/plugin-center/since/supply-chain?tab=orders', title: '旧供应链' },
      { name: 'LegacyViews', path: '/views-plugin/71', fullPath: '/views-plugin/71?tab=latest&page=8', title: '旧声明页' },
      { name: 'Item', path: '/items/1', fullPath: '/items/1?view=a', title: '普通商品' },
    ]
  })
  await open(page, '/items/1?view=a')
  await expect(tabItems(page)).toHaveCount(3)
  await pluginTab(page).click()
  await expectPluginGroup(page, '/views-plugin/71?tab=latest&page=8', 3)
})

test('dragging after close replaces the current store array and persists its order across reload', async ({ page }) => {
  await open(page, '/welcome')
  await nav(page, '普通商品 A')
  await nav(page, '插件总览')
  await nav(page, '普通商品 B')
  await nav(page, '其它页面')
  await page.getByRole('button', { name: '关闭当前标签', exact: true }).click()
  await expect(tabItems(page)).toHaveCount(4)
  // closeTab replaces tabList. Sortable must observe that ref, not its initial array.
  const source = await pluginTab(page).boundingBox()
  const target = await tabItems(page).nth(1).boundingBox()
  expect(source).not.toBeNull()
  expect(target).not.toBeNull()
  await page.mouse.move(source!.x + source!.width / 2, source!.y + source!.height / 2)
  await page.mouse.down()
  await page.mouse.move(target!.x + 3, target!.y + target!.height / 2, { steps: 14 })
  await page.mouse.up()
  const expected = ['/welcome', '/plugin-center', '/items/1?view=a', '/items/1?view=b']
  await expect.poll(async () => (await snapshot(page)).tabs.map((item: any) => item.fullPath)).toEqual(expected)
  await expect(pluginTab(page)).toHaveCount(1)
  await page.reload()
  await expect(page.getByTestId('current-url')).toHaveText('/items/1?view=b')
  expect((await snapshot(page)).tabs.map((item: any) => item.fullPath)).toEqual(expected)
})
