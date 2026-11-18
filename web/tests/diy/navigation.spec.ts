import { test, expect, type Page } from '@playwright/test'

const fixture = {
  id: 7, page_key: 'home', title: '回归首页', page_type: 'h5', is_enabled: true, status: 'draft',
  updated_at: '2026-10-13 10:00:00', published_version_id: null,
  versions: [{ id: 12, status: 'draft', schema: { version: 1, page: { key: 'home', title: '回归首页' }, components: [] } }],
}
const centerList = '/plugin-center/since/diy'
const centerEditor = `${centerList}?view=editor&id=7`

async function mockApi(page: Page, waitForPage8?: Promise<void>, waitForDraft7?: Promise<void>) {
  const saves: any[] = []
  const errors: string[] = []
  const requests: string[] = []
  page.on('pageerror', error => errors.push(error.message))
  await page.route('**/*', async (route) => {
    const url = new URL(route.request().url())
    if (url.origin !== 'http://127.0.0.1:4001') return route.abort()
    if (!url.pathname.startsWith('/admin/')) return route.continue()
    requests.push(`${route.request().method()} ${url.pathname}`)
    let data: any
    if (url.pathname === '/admin/plugin-center/plugins') data = [{ name: 'since/diy', installed: true, enabled: true }]
    else if (url.pathname === '/admin/diy/pages/list') data = { list: [fixture], total: 1 }
    else if (url.pathname === '/admin/product/category/tree') data = []
    else if (url.pathname === '/admin/diy/pages/7' && route.request().method() === 'GET') data = fixture
    else if (url.pathname === '/admin/diy/pages/8' && route.request().method() === 'GET') {
      await waitForPage8
      data = {
        ...fixture, id: 8, page_key: 'activity', title: '第二活动页',
        versions: [{ id: 13, status: 'draft', schema: { version: 1, page: { key: 'activity', title: '第二活动页' }, components: [] } }],
      }
    }
    else if (/^\/admin\/diy\/pages\/[78]\/draft$/.test(url.pathname) && route.request().method() === 'PUT') {
      saves.push(route.request().postDataJSON())
      if (url.pathname === '/admin/diy/pages/7/draft') await waitForDraft7
      data = { id: 12 }
    }
    else if (/^\/admin\/diy\/pages\/[78]\/publish$/.test(url.pathname) && route.request().method() === 'POST') data = { id: 12 }
    else {
      errors.push(`Unmocked API ${url.pathname}`)
      return route.fulfill({ status: 501, json: { code: 501, message: errors.at(-1) } })
    }
    await route.fulfill({ json: { code: 200, data } })
  })
  return { saves, errors, requests }
}

async function expectList(page: Page) {
  await expect(page.getByText('回归首页', { exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: '装修', exact: true })).toBeVisible()
  await expect(page.getByTestId('route-not-found')).toHaveCount(0)
}

async function expectEditor(page: Page) {
  await expect(page.getByRole('button', { name: '保存草稿', exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: '返回列表', exact: true })).toBeVisible()
  await expect(page.getByTestId('route-not-found')).toHaveCount(0)
}

async function expectEditorLocation(page: Page, id = '7') {
  await expect(page).toHaveURL(url => url.pathname === centerList && url.searchParams.get('view') === 'editor' && url.searchParams.get('id') === id)
}

async function showPageSettings(page: Page) {
  // Element Plus hides the native radio and exposes its label as the click target.
  await page.locator('.diy-editor__toolbar .el-segmented__item').filter({ hasText: '页面设置' }).click()
  return page.locator('.page-setting-panel .el-form-item').filter({ hasText: '页面标题' }).getByRole('textbox')
}

async function openList(page: Page) {
  await page.goto('/plugin-center')
  await page.locator('.plugin-card').filter({ hasText: '店铺装修' }).click()
  await expect(page).toHaveURL(new RegExp(`${centerList}$`))
  await expectList(page)
}

// Both the action and destination are production components; the harness never declares a DIY route.
test('中心卡片进入列表，装修编辑页刷新后可返回列表与插件中心', async ({ page }) => {
  const api = await mockApi(page)
  await openList(page)
  await page.reload()
  await expectList(page)
  await page.getByRole('button', { name: '装修', exact: true }).click()
  await expectEditor(page)
  await expectEditorLocation(page)
  await page.reload()
  await expectEditor(page)
  await page.getByRole('button', { name: '返回列表', exact: true }).click()
  await expect(page).toHaveURL(new RegExp(`${centerList}$`))
  await expectList(page)
  await page.getByRole('button', { name: '返回', exact: true }).click()
  await expect(page).toHaveURL(/\/plugin-center\/?$/)
  await expect(page.locator('.plugin-card').filter({ hasText: '店铺装修' })).toBeVisible()
  expect(api.errors).toEqual([])
})

test('多个插件与 Composition i18n 同时启动，翻译合并后装修页正常渲染', async ({ page }) => {
  const api = await mockApi(page)
  await page.goto(centerEditor)
  await expectEditor(page)
  const translations = await page.evaluate(async () => {
    const modulePath = '/main.ts'
    const { i18n } = await import(modulePath)
    return ['zh_CN', 'en_US'].map(locale => ({
      customerService: i18n.global.t('customerService.title', {}, { locale }),
      exportCenter: i18n.global.t('exportMenu.exportCenter', {}, { locale }),
    }))
  })
  expect(translations).toEqual([
    { customerService: '客服工作台', exportCenter: '导出中心' },
    { customerService: 'Customer Service', exportCenter: 'Export Center' },
  ])
  expect(api.errors).toEqual([])
})

test('插件注册等待异步启动钩子完成，并向安装函数传递 i18n', async ({ page }) => {
  const api = await mockApi(page)
  await page.goto(centerEditor)
  await expectEditor(page)
  const result = await page.evaluate(async () => {
    const modulePath = '/plugin-registration.ts'
    const { createRegistrationFixture } = await import(modulePath)
    const events: string[] = []
    let release!: () => void
    const ready = new Promise<void>(resolve => { release = resolve })
    let receivedComposer = false
    const fixture = createRegistrationFixture({
      probe: {
        config: { enable: true, info: { name: 'probe', version: '1', author: 'test', description: '' } },
        hooks: { start: async () => { events.push('start'); await ready; events.push('ready') } },
        install: (_app: unknown, options: any) => {
          receivedComposer = options?.i18n === fixture.i18n.global
          events.push('install')
        },
      },
    })
    const registration = fixture.store.registerPlugin(fixture.app, { i18n: fixture.i18n.global })
      .then(() => events.push('registered'))
    await new Promise(resolve => setTimeout(resolve, 0))
    const beforeRelease = [...events]
    release()
    await registration
    return { beforeRelease, events, receivedComposer }
  })
  expect(result).toEqual({
    beforeRelease: ['start'],
    events: ['start', 'ready', 'install', 'registered'],
    receivedComposer: true,
  })
  expect(api.errors).toEqual([])
})

test('异步启动失败由插件注册返回，不安装失败插件及后续插件', async ({ page }) => {
  const api = await mockApi(page)
  await page.goto(centerEditor)
  await expectEditor(page)
  const result = await page.evaluate(async () => {
    const modulePath = '/plugin-registration.ts'
    const { createRegistrationFixture } = await import(modulePath)
    const installed: string[] = []
    const info = { name: 'probe', version: '1', author: 'test', description: '' }
    const { app, store, i18n } = createRegistrationFixture({
      failing: {
        config: { enable: true, info },
        hooks: { start: async () => { await Promise.resolve(); throw new Error('startup failed') } },
        install: () => installed.push('failing'),
      },
      later: { config: { enable: true, info: { ...info, name: 'later' } }, install: () => installed.push('later') },
    })
    try {
      await store.registerPlugin(app, { i18n: i18n.global })
      return { error: null, installed }
    }
    catch (error) {
      return { error: (error as Error).message, installed }
    }
  })
  expect(result).toEqual({ error: 'startup failed', installed: [] })
  expect(api.errors).toEqual([])
})

test('初始停用的翻译插件首次启用时仍收到 i18n，并保留主应用语言消息', async ({ page }) => {
  const api = await mockApi(page)
  await page.goto(centerEditor)
  await expectEditor(page)
  const result = await page.evaluate(async () => {
    const modulePath = '/plugin-registration.ts'
    const { createRegistrationFixture, customerService, exportCenter } = await import(modulePath)
    const disabledPlugin = (plugin: any) => ({ ...plugin, config: { ...plugin.config, enable: false } })
    const { app, store, i18n } = createRegistrationFixture({
      'since/customer-service': disabledPlugin(customerService),
      'since/export-center': disabledPlugin(exportCenter),
    })
    await store.registerPlugin(app, { i18n: i18n.global })
    const beforeEnable = [i18n.global.te('customerService.title'), i18n.global.te('exportMenu.exportCenter')]
    await store.enabled('since/customer-service')
    await store.enabled('since/export-center')
    return {
      beforeEnable,
      translations: [i18n.global.t('customerService.title'), i18n.global.t('exportMenu.exportCenter')],
      applicationTitle: i18n.global.t('applicationTitle'),
    }
  })
  expect(result).toEqual({
    beforeEnable: [false, false],
    translations: ['客服工作台', '导出中心'],
    applicationTitle: '商城后台',
  })
  expect(api.errors).toEqual([])
})

test('直达编辑页加载草稿并保存页面设置', async ({ page }) => {
  const api = await mockApi(page)
  await page.goto(centerEditor)
  await expectEditor(page)
  const titleInput = await showPageSettings(page)
  await expect(titleInput).toHaveValue('回归首页')
  await titleInput.fill('浏览器保存的草稿标题')
  await page.getByRole('button', { name: '保存草稿', exact: true }).click()
  await expect(page.getByText('草稿已保存', { exact: true })).toBeVisible()
  expect(api.saves).toHaveLength(1)
  expect(api.saves[0].schema).toMatchObject({ version: 1, page: { key: 'home', title: '浏览器保存的草稿标题' }, components: [] })
  expect(api.errors).toEqual([])
  if (process.env.DIY_SCREENSHOT) await page.screenshot({ path: process.env.DIY_SCREENSHOT, fullPage: true })
})

for (const legacy of ['/mall/diy/editor?id=7', '/mall/diy/page']) {
  test(`兼容旧地址 ${legacy}`, async ({ page }) => {
    const api = await mockApi(page)
    await page.goto(legacy)
    if (legacy.includes('editor')) {
      await expectEditor(page)
      await expectEditorLocation(page)
    }
    else {
      await expectList(page)
      await expect(page).toHaveURL(new RegExp(`${centerList}$`))
    }
    expect(api.errors).toEqual([])
  })
}

test('停用插件隐藏编辑器，重新启用可继续编辑', async ({ page }) => {
  const api = await mockApi(page)
  await page.goto(centerEditor)
  await expectEditor(page)
  await page.getByRole('button', { name: '停用插件', exact: true }).click()
  await expect(page.getByText('插件已停用，请先启用后使用', { exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: '保存草稿', exact: true })).toHaveCount(0)
  const before = api.requests.filter(request => request === 'GET /admin/diy/pages/7').length
  await page.getByRole('button', { name: '启用插件', exact: true }).click()
  await expectEditor(page)
  await expect.poll(() => api.requests.filter(request => request === 'GET /admin/diy/pages/7').length).toBeGreaterThan(before)
  expect(api.errors).toEqual([])
})

test('同一编辑器路由切换页面 ID，加载前不能保存，草稿不会串页', async ({ page }) => {
  let releasePage8!: () => void
  const page8Ready = new Promise<void>(resolve => { releasePage8 = resolve })
  const api = await mockApi(page, page8Ready)
  try {
    await page.goto(centerEditor)
    await expectEditor(page)
    const title7 = await showPageSettings(page)
    await expect(title7).toHaveValue('回归首页')
    await title7.fill('不能带入第二页的未保存标题')

    // Change only the query on the real router; a full page.goto would mask missing component remounts.
    await page.evaluate(async () => {
      const app = (document.querySelector('#app') as any).__vue_app__
      await app.config.globalProperties.$router.push('/plugin-center/since/diy?view=editor&id=8')
    })
    await expectEditorLocation(page, '8')
    await expect.poll(() => api.requests.includes('GET /admin/diy/pages/8')).toBe(true)
    await expect(page.getByRole('button', { name: '保存草稿', exact: true })).toBeDisabled()
    await expect(page.getByRole('button', { name: '发布', exact: true })).toBeDisabled()
    releasePage8()

    await expect(page.locator('.diy-editor__title strong')).toHaveText('第二活动页')
    const title8 = await showPageSettings(page)
    await expect(title8).toHaveValue('第二活动页')
    await title8.fill('第二页自己的草稿')
    await page.getByRole('button', { name: '保存草稿', exact: true }).click()
    await expect(page.getByText('草稿已保存', { exact: true })).toBeVisible()
    expect(api.saves).toHaveLength(1)
    expect(api.saves[0].schema.page).toMatchObject({ key: 'activity', title: '第二页自己的草稿' })
    expect(api.requests).toContain('PUT /admin/diy/pages/8/draft')
    expect(api.requests).not.toContain('PUT /admin/diy/pages/7/draft')
    expect(api.errors).toEqual([])
  }
  finally { releasePage8() }
})

test('缺少 update 权限时列表隐藏装修按钮，直达地址也不能加载编辑器', async ({ page }) => {
  await page.addInitScript(() => localStorage.setItem('diy-test-permissions', JSON.stringify(['mall:diy:page:read'])))
  const api = await mockApi(page)
  await page.goto(centerList)
  await expect(page.getByText('回归首页', { exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: '装修', exact: true })).toHaveCount(0)
  await page.goto(centerEditor)
  await expect(page.getByText('暂无页面装修权限', { exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: '保存草稿', exact: true })).toHaveCount(0)
  expect(api.requests).not.toContain('GET /admin/diy/pages/7')
  expect(api.errors).toEqual([])
})

test('发布等待草稿保存期间切页，发布请求始终使用点击时的页面 ID', async ({ page }) => {
  let releaseDraft7!: () => void
  const draft7Ready = new Promise<void>(resolve => { releaseDraft7 = resolve })
  const api = await mockApi(page, undefined, draft7Ready)
  try {
    await page.goto(centerEditor)
    await expectEditor(page)
    await page.getByRole('button', { name: '发布', exact: true }).click()
    await expect.poll(() => api.requests.includes('PUT /admin/diy/pages/7/draft')).toBe(true)
    expect(api.requests.filter(request => request.endsWith('/publish'))).toEqual([])
    await page.evaluate(async () => {
      const app = (document.querySelector('#app') as any).__vue_app__
      await app.config.globalProperties.$router.push('/plugin-center/since/diy?view=editor&id=8')
    })
    await expect(page.locator('.diy-editor__title strong')).toHaveText('第二活动页')
    releaseDraft7()
    await expect(page.getByText('页面已发布', { exact: true })).toBeVisible()
    expect(api.requests.filter(request => request.endsWith('/publish'))).toEqual(['POST /admin/diy/pages/7/publish'])
    expect(api.requests).not.toContain('PUT /admin/diy/pages/8/draft')
    expect(api.errors).toEqual([])
  }
  finally { releaseDraft7() }
})
