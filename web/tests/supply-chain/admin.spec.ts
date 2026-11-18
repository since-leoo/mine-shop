import type { Page, Route } from '@playwright/test'
import { expect, test } from '@playwright/test'

const connection = { id: 1, name: '芸众测试连接', api_origin: 'https://supply.example.com', app_key: 'app-001', has_app_secret: true, mode: 'active' }
const product = (id: string) => ({ external_product_id: id, name: `供货商品 ${id}`, cost_price: 12345, stock: 50, imported: false, selected: null })
async function mockDefaults(page: Page, handler?: (route: Route, url: URL) => Promise<boolean | void>) {
  await page.route('**/admin/plugin-center/plugins', route => route.fulfill({ json: { code: 200, data: [{ name: 'since/supply-chain', installed: true, enabled: true }] } }))
  await page.route('**/admin/supply-chain/**', async (route) => {
    const url = new URL(route.request().url())
    if (await handler?.(route, url)) return
    if (url.pathname.endsWith('/connections')) return route.fulfill({ json: { code: 200, data: { list: [connection], total: 1 } } })
    return route.fulfill({ json: { code: 200, data: { list: [], total: 0 } } })
  })
}

test('主项目插件中心通过供应链入口加载内部连接设置，不显示假停用按钮', async ({ page }) => {
  const errors: string[] = []
  page.on('pageerror', error => errors.push(error.message))
  await mockDefaults(page)
  await page.goto('/?page=shell')
  await expect(page.getByRole('heading', { name: '插件中心', exact: true })).toBeVisible()
  const card = page.locator('.plugin-card').filter({ hasText: '供应链' })
  await expect(card.getByRole('button', { name: '设置', exact: true })).toHaveCount(0)
  await card.click()
  await expect(page.getByRole('tab', { name: '选品中心', exact: true })).toBeVisible()
  await expect(page.locator('.plugin-detail-actions').getByRole('button', { name: '插件设置', exact: true })).toHaveCount(0)
  await expect(page.getByRole('button', { name: '停用插件', exact: true })).toHaveCount(0)
  await page.getByRole('tab', { name: '连接设置', exact: true }).click()
  await expect(page.getByRole('cell', { name: '芸众测试连接', exact: true })).toBeVisible()
  await page.getByRole('button', { name: '编辑', exact: true }).click()
  await expect(page.getByTestId('connection-secret')).toHaveValue('')
  expect(errors).toEqual([])
})

test('编辑连接不回显或重发旧密钥，失败保留修改内容', async ({ page }) => {
  const writes: Record<string, unknown>[] = []
  let reject = false
  await mockDefaults(page, async (route, url) => {
    if (url.pathname.endsWith('/connections/1') && route.request().method() === 'PUT') {
      writes.push(route.request().postDataJSON())
      await route.fulfill(reject ? { status: 409, json: { message: '连接版本已变化，请重试' } } : { json: { code: 200, data: connection } })
      return true
    }
  })
  await page.goto('/?page=settings')
  await page.getByRole('button', { name: '编辑', exact: true }).click()
  await expect(page.getByTestId('connection-secret')).toHaveValue('')
  await page.getByTestId('connection-name').fill('新版供应链')
  await page.getByTestId('save-connection').click()
  await expect.poll(() => writes.length).toBe(1)
  expect(writes[0]).toMatchObject({ name: '新版供应链', app_key: 'app-001' })
  expect(writes[0]).not.toHaveProperty('app_secret')
  await expect(page.getByText('供应链连接已保存')).toBeVisible()
  reject = true
  await page.getByRole('button', { name: '编辑', exact: true }).click()
  await page.getByTestId('connection-secret').fill('replacement-secret')
  await page.getByTestId('save-connection').click()
  await expect(page.getByRole('alert').filter({ hasText: '连接版本已变化' })).toBeVisible()
  expect(writes[1].app_secret).toBe('replacement-secret')
  await expect(page.getByTestId('connection-secret')).toHaveValue('replacement-secret')
})

test('退货物流映射只保存明确的供应商名称与编码，拒绝无效JSON和缺码映射', async ({ page }) => {
  const writes: Record<string, unknown>[] = []
  await mockDefaults(page, async (route, url) => {
    if (url.pathname.endsWith('/connections/1') && route.request().method() === 'PUT') {
      writes.push(route.request().postDataJSON())
      await route.fulfill({ json: { code: 200, data: connection } }); return true
    }
    if (url.pathname.endsWith('/connections')) {
      await route.fulfill({ json: { code: 200, data: { list: [{ ...connection, settings: { return_carriers: { 顺丰: { name: '顺丰速运', code: 'SF' } } } }], total: 1 } } }); return true
    }
  })
  await page.goto('/?page=settings')
  await page.getByRole('button', { name: '编辑', exact: true }).click()
  await page.getByRole('button', { name: '高级设置：退货物流映射' }).click()
  await expect(page.getByTestId('return-carriers')).toHaveValue(JSON.stringify({ 顺丰: { name: '顺丰速运', code: 'SF' } }, null, 2))
  await page.getByTestId('return-carriers').fill('{broken')
  await page.getByTestId('save-connection').click()
  await expect(page.getByText('退货物流映射必须是有效 JSON 对象')).toBeVisible()
  expect(writes).toHaveLength(0)
  await page.getByTestId('return-carriers').fill('{"顺丰":{"name":"顺丰速运"}}')
  await page.getByTestId('save-connection').click()
  await expect(page.getByText('退货物流映射格式无效：每个本地公司名称只允许 name、code 两个非空字符串字段')).toBeVisible()
  expect(writes).toHaveLength(0)
  await page.getByTestId('return-carriers').fill('{"顺丰":{"name":"顺丰速运","code":"SF"}}')
  await page.getByTestId('save-connection').click()
  await expect.poll(() => writes.length).toBe(1)
  expect(writes[0].settings).toEqual({ return_carriers: { 顺丰: { name: '顺丰速运', code: 'SF' } } })
  expect(writes[0]).not.toHaveProperty('app_secret')
  await page.getByTestId('new-connection').click()
  await expect(page.getByTestId('return-carriers')).toHaveValue('{}')
})

test('选品跨页保留且提交异步导入，分类校验和金额单位正确', async ({ page }) => {
  const writes: Record<string, unknown>[] = []
  await mockDefaults(page, async (route, url) => {
    if (url.pathname.endsWith('/catalog')) {
      const id = url.searchParams.get('page') === '2' ? 'B-2' : 'A-1'
      await route.fulfill({ json: { code: 200, data: { list: [product(id)], total: 21 } } }); return true
    }
    if (url.pathname.endsWith('/imports')) {
      writes.push(route.request().postDataJSON())
      await route.fulfill({ json: { code: 200, data: { task_id: 81 } } }); return true
    }
    if (url.pathname.endsWith('/tasks/81/parameters')) {
      await route.fulfill({ json: { code: 200, data: { id: 81, type: 'catalog.import', status: 'pending', attempts: [] } } }); return true
    }
  })
  await page.goto('/')
  await expect(page.getByRole('cell', { name: '¥123.45', exact: true })).toBeVisible()
  await page.getByRole('checkbox', { name: '选择商品 A-1' }).locator('..').click()
  await page.locator('.btn-next').click()
  await page.getByRole('checkbox', { name: '选择商品 B-2' }).locator('..').click()
  await expect(page.getByText('已选 2 / 50 件（跨页保留）')).toBeVisible()
  await page.getByTestId('open-import').click()
  await page.getByTestId('confirm-import').click()
  await expect(page.getByText('请填写本系统有效的商品分类 ID')).toBeVisible()
  expect(writes).toHaveLength(0)
  await page.getByTestId('import-category').locator('input').fill('12')
  await page.getByTestId('import-multiplier').locator('input').fill('1.25')
  await page.getByTestId('confirm-import').click()
  await expect.poll(() => writes.length).toBe(1)
  expect(writes[0]).toEqual({ connection_id: 1, external_product_ids: ['A-1', 'B-2'], category_id: 12, price_multiplier: 1.25 })
  await expect(page.getByText('导入任务已提交，请在任务日志查看执行结果')).toBeVisible()
  await expect(page.getByRole('heading', { name: '任务 #81' })).toBeVisible()
})

test('详情预览只读，供应商失败不会提交导入或选品', async ({ page }) => {
  const writes: string[] = []
  await mockDefaults(page, async (route, url) => {
    if (route.request().method() !== 'GET') writes.push(url.pathname)
    if (url.pathname.endsWith('/catalog')) {
      await route.fulfill({ json: { code: 200, data: { list: [product('A/1')], total: 1 } } }); return true
    }
    if (url.pathname.includes('/catalog/')) {
      expect(url.pathname).toContain('A%2F1')
      expect(url.searchParams.get('connection_id')).toBe('1')
      await route.fulfill({ status: 502, json: { message: '上游商品详情暂不可用' } }); return true
    }
  })
  await page.goto('/')
  await page.getByRole('button', { name: '详情预览' }).click()
  await expect(page.getByRole('dialog').getByText('上游商品详情暂不可用')).toBeVisible()
  expect(writes).toHaveLength(0)
})

test('未知采购只能查单，失败任务重试记录原因并保留逐次记录', async ({ page }) => {
  const writes: Array<{ path: string, data: unknown }> = []
  const failed = { id: 41, type: 'catalog.sync', status: 'failed', retryable: true, error_message: '上游限流', attempt_count: 2 }
  await mockDefaults(page, async (route, url) => {
    if (route.request().method() === 'POST') {
      writes.push({ path: url.pathname, data: route.request().postDataJSON() })
      await route.fulfill({ json: { code: 200, data: { task_id: 41 } } }); return true
    }
    if (url.pathname.endsWith('/tasks')) {
      await route.fulfill({ json: { code: 200, data: { list: [failed, { id: 42, type: 'purchase.submit', status: 'unknown', retryable: true }], total: 2 } } }); return true
    }
    if (url.pathname.endsWith('/tasks/41/parameters')) {
      await route.fulfill({ json: { code: 200, data: { ...failed, attempts: [{ id: 401, attempt_no: 1, status: 'failed', error_message: '首次连接超时' }, { id: 402, attempt_no: 2, status: 'failed', error_message: '上游限流' }] } } }); return true
    }
    if (url.pathname.endsWith('/purchases')) {
      await route.fulfill({ json: { code: 200, data: { list: [{ id: 31, order_no: 'SALE-1', merchant_order_no: 'PUR-1', status: 'unknown' }], total: 1 } } }); return true
    }
  })
  await page.goto('/')
  await page.getByRole('tab', { name: '任务与重试' }).click()
  const unknown = page.getByRole('row').filter({ hasText: 'purchase.submit' })
  await expect(unknown.getByRole('button', { name: '重试', exact: true })).toHaveCount(0)
  await page.getByRole('row').filter({ hasText: 'catalog.sync' }).getByRole('button', { name: '重试', exact: true }).click()
  await page.getByTestId('confirm-retry').click()
  await expect(page.getByText('请填写本次重试原因')).toBeVisible()
  expect(writes).toHaveLength(0)
  await page.getByTestId('retry-reason').fill('已确认供应商限流恢复')
  await page.getByTestId('confirm-retry').click()
  await expect.poll(() => writes.length).toBe(1)
  expect(writes[0]).toEqual({ path: '/admin/supply-chain/tasks/41/retry', data: { reason: '已确认供应商限流恢复' } })
  await page.getByRole('row').filter({ hasText: 'catalog.sync' }).getByRole('button', { name: '执行详情' }).click()
  await expect(page.getByText('第 1 次执行')).toBeVisible()
  await expect(page.getByText('第 2 次执行')).toBeVisible()
  await expect(page.getByText('首次连接超时')).toBeVisible()
  await page.getByRole('button', { name: 'Close this dialog' }).click()
  await page.getByRole('tab', { name: '采购订单' }).click()
  await expect(page.getByRole('button', { name: '查询上游结果' })).toBeVisible()
  await expect(page.getByRole('button', { name: '重新采购' })).toHaveCount(0)
})

test('请求参数按文本安全展示，隐藏密钥且独立限制参数权限', async ({ page }) => {
  const reads: string[] = []
  await mockDefaults(page, async (route, url) => {
    reads.push(url.pathname)
    if (url.pathname.endsWith('/logs')) {
      await route.fulfill({ json: { code: 200, data: { list: [{ id: 61, method: 'POST', path: '/app/order', status: 'failed', http_status: 400, error_message: '余额不足' }], total: 1 } } }); return true
    }
    if (url.pathname.endsWith('/logs/61')) {
      await route.fulfill({ json: { code: 200, data: { id: 61, method: 'POST', path: '/app/order', status: 'failed', request: { appSecret: 'DO-NOT-SHOW', nested: { 'x-token': 'PRIVATE-TOKEN' }, note: '<img src=x onerror="window.__executed=true">' }, response: { code: 400, message: '余额不足' } } } }); return true
    }
  })
  await page.goto('/')
  await page.getByRole('tab', { name: '接口日志' }).click()
  await page.getByRole('button', { name: '请求参数与结果' }).click()
  await expect(page.getByTestId('request-params')).toContainText('<img src=x')
  await expect(page.getByTestId('request-params')).not.toContainText('DO-NOT-SHOW')
  await expect(page.getByTestId('request-params')).not.toContainText('PRIVATE-TOKEN')
  await expect(page.getByTestId('request-params').locator('img')).toHaveCount(0)
  expect(await page.evaluate(() => (window as unknown as { __executed?: boolean }).__executed)).toBeUndefined()
  await page.addInitScript(() => { (window as unknown as { __deniedPermissions: string[] }).__deniedPermissions = ['supply-chain:logs/params'] })
  reads.length = 0
  await page.reload()
  await page.getByRole('tab', { name: '接口日志' }).click()
  await expect(page.getByText('无参数查看权限')).toBeVisible()
  await expect(page.getByRole('button', { name: '请求参数与结果' })).toHaveCount(0)
  expect(reads).not.toContain('/admin/supply-chain/logs/61')
})

test('连接暂停时禁止导入，无连接权限不会请求密钥配置', async ({ page }) => {
  await mockDefaults(page, async (route, url) => {
    if (url.pathname.endsWith('/connections')) {
      await route.fulfill({ json: { code: 200, data: { list: [{ ...connection, mode: 'draining' }], total: 1 } } }); return true
    }
    if (url.pathname.endsWith('/catalog')) {
      await route.fulfill({ json: { code: 200, data: { list: [product('A-1')], total: 1 } } }); return true
    }
  })
  await page.goto('/')
  await expect(page.getByRole('checkbox', { name: '选择商品 A-1' })).toBeDisabled()
  await expect(page.getByTestId('open-import')).toBeDisabled()
  const configRequests: string[] = []
  await page.addInitScript(() => { (window as unknown as { __deniedPermissions: string[] }).__deniedPermissions = ['supply-chain:connections/read'] })
  page.on('request', request => { if (request.url().includes('/connections')) configRequests.push(request.url()) })
  await page.goto('/?page=settings')
  await expect(page.getByText('暂无供应链连接查看权限')).toBeVisible()
  expect(configRequests).toHaveLength(0)
})

test('采购售后政策绑定订单行，禁止换货且不自动触发客户退款', async ({ page }) => {
  const writes: Array<{ path: string, data: Record<string, unknown> }> = []
  const policyReads: URL[] = []
  const afterSale = { id: 71, connection_id: 1, after_sale_id: 8, supply_order_id: 9, status: 'pending_review', external_orders: [{ external_order_no: 'REMOTE-1', items: [{ external_order_item_id: 'LINE-1', external_sku_id: 'SKU-A', quantity: 2, amount_cents: 24690 }] }] }
  await mockDefaults(page, async (route, url) => {
    if (route.request().method() === 'POST') {
      writes.push({ path: url.pathname, data: route.request().postDataJSON() })
      await route.fulfill({ json: { code: 200, data: {} } }); return true
    }
    if (url.pathname.endsWith('/after-sales/types')) {
      policyReads.push(url)
      await route.fulfill({ json: { code: 200, data: { reason_types: [{ code: 1, name: '质量问题' }], refund_type: { 1: '退货退款' } } } }); return true
    }
    if (url.pathname.endsWith('/after-sales/71')) {
      await route.fulfill({ json: { code: 200, data: afterSale } }); return true
    }
    if (url.pathname.endsWith('/after-sales')) {
      await route.fulfill({ json: { code: 200, data: { list: [afterSale], total: 1 } } }); return true
    }
  })
  await page.goto('/')
  await page.getByRole('tab', { name: '采购售后' }).click()
  await page.getByRole('button', { name: '申请采购售后', exact: true }).click()
  await expect(page.getByTestId('load-after-sale-policy')).toBeDisabled()
  await page.getByRole('button', { name: '选用', exact: true }).click()
  await page.getByTestId('load-after-sale-policy').click()
  await expect(page.getByTestId('after-sale-policy')).toContainText('质量问题')
  expect(policyReads[0].searchParams.get('external_order_item_id')).toBe('LINE-1')
  await page.getByTestId('after-sale-reason-type').locator('input').fill('1')
  await page.getByTestId('after-sale-reason').fill('供货商品质量问题')
  await page.getByTestId('after-sale-received').locator('input').fill('1')
  await page.getByTestId('after-sale-refund-way').locator('input').fill('1')
  await page.getByTestId('after-sale-refund-type').locator('input').fill('2')
  await page.getByTestId('submit-after-sale').click()
  await expect(page.getByText('当前不支持换货（refund_type=2），请按上游政策选择已支持的退款类型')).toBeVisible()
  expect(writes).toHaveLength(0)
  await page.getByTestId('after-sale-refund-type').locator('input').fill('1')
  await page.getByTestId('after-sale-amount').locator('input').fill('12345')
  await page.getByTestId('submit-after-sale').click()
  await expect.poll(() => writes.length).toBe(1)
  expect(writes[0]).toMatchObject({ path: '/admin/supply-chain/after-sales/71/submit', data: { external_order_item_id: 'LINE-1', amount_cents: 12345, refund_type: 1, reason_type: 1 } })
  expect(writes.every(write => write.path.startsWith('/admin/supply-chain/'))).toBe(true)
})

test('切换供应链连接后，迟到响应不覆盖新连接商品', async ({ page }) => {
  let releaseFirst: () => void = () => {}
  const delayed = new Promise<void>((resolve) => { releaseFirst = resolve })
  let initialStarted = false
  await mockDefaults(page, async (route, url) => {
    if (url.pathname.endsWith('/connections')) {
      await route.fulfill({ json: { code: 200, data: { list: [connection, { ...connection, id: 2, name: '第二供应链' }], total: 2 } } }); return true
    }
    if (url.pathname.endsWith('/catalog')) {
      if (url.searchParams.get('connection_id') === '1') {
        initialStarted = true
        await delayed
        await route.fulfill({ json: { code: 200, data: { list: [product('OLD')], total: 1 } } })
      }
      else await route.fulfill({ json: { code: 200, data: { list: [product('NEW')], total: 1 } } })
      return true
    }
  })
  await page.goto('/')
  await expect.poll(() => initialStarted).toBe(true)
  await page.locator('header .el-select').click()
  await page.getByRole('option', { name: '第二供应链 · 运行中' }).click()
  await expect(page.getByRole('cell', { name: '供货商品 NEW', exact: true })).toBeVisible()
  const lateResponse = page.waitForResponse(response => response.url().includes('/catalog?') && new URL(response.url()).searchParams.get('connection_id') === '1')
  releaseFirst()
  await lateResponse
  await expect(page.getByRole('cell', { name: '供货商品 OLD', exact: true })).toHaveCount(0)
  await expect(page.getByText('已选 0 / 50 件（跨页保留）')).toBeVisible()
})

test('未知售后的候选号只供核对，不提供认领或重复申请操作', async ({ page }) => {
  const writes: string[] = []
  const sale = { id: 72, after_sale_id: 22, supply_order_id: 31, connection_id: 1, status: 'unknown', external_after_sale_id: null, candidate_external_after_sale_id: 'CANDIDATE-91' }
  await mockDefaults(page, async (route, url) => {
    if (route.request().method() !== 'GET') writes.push(url.pathname)
    if (url.pathname.endsWith('/after-sales/72')) {
      await route.fulfill({ json: { code: 200, data: sale } }); return true
    }
    if (url.pathname.endsWith('/after-sales')) {
      await route.fulfill({ json: { code: 200, data: { list: [sale], total: 1 } } }); return true
    }
  })
  await page.goto('/')
  await page.getByRole('tab', { name: '采购售后' }).click()
  await expect(page.getByRole('button', { name: '申请采购售后', exact: true })).toHaveCount(0)
  await page.getByRole('button', { name: '售后详情', exact: true }).click()
  await expect(page.getByTestId('after-sale-candidate')).toContainText('CANDIDATE-91')
  await expect(page.getByTestId('after-sale-candidate')).toContainText('尚未认领为本次售后')
  await expect(page.getByRole('dialog').getByRole('button', { name: /认领|取消售后|提交|选用/ })).toHaveCount(0)
  expect(writes).toEqual([])
})

for (const entry of [{ name: '供应链主页面', url: '/' }, { name: '独立连接设置', url: '/?page=settings' }]) {
  for (const state of [{ name: '未安装', installed: false, enabled: true }, { name: '已停用', installed: true, enabled: false }]) {
    test(`${entry.name}${state.name}时只查询插件状态，不请求供应链业务接口`, async ({ page }) => {
      const supplyRequests: string[] = []
      let stateReads = 0
      await mockDefaults(page, async (route) => { supplyRequests.push(route.request().url()) })
      await page.route('**/admin/plugin-center/plugins', async (route) => {
        stateReads++
        await route.fulfill({ json: { code: 200, data: [{ name: 'since/supply-chain', installed: state.installed, enabled: state.enabled }] } })
      })
      await page.goto(entry.url)
      await expect(page.getByText('该功能插件未安装或已停用')).toBeVisible()
      expect(stateReads).toBe(1)
      expect(supplyRequests).toEqual([])
      await expect(page.getByRole('tab', { name: '选品中心', exact: true })).toHaveCount(0)
      await expect(page.getByTestId('new-connection')).toHaveCount(0)
      await page.getByTestId('refresh-plugin-status').click()
      await expect.poll(() => stateReads).toBe(2)
      await expect(page.getByText('该功能插件未安装或已停用')).toBeVisible()
      expect(supplyRequests).toEqual([])
    })
  }

  test(`${entry.name}状态查询失败时可刷新重试，确认已安装启用后才加载业务`, async ({ page }) => {
    const supplyRequests: string[] = []
    let stateReads = 0
    await mockDefaults(page, async (route) => { supplyRequests.push(route.request().url()) })
    await page.route('**/admin/plugin-center/plugins', async (route) => {
      stateReads++
      await route.fulfill(stateReads === 1
        ? { status: 503, json: { code: 503, message: '状态服务暂不可用' } }
        : { json: { code: 200, data: [{ name: 'since/supply-chain', installed: true, enabled: true }] } })
    })
    await page.goto(entry.url)
    await expect(page.getByText('暂时无法读取插件状态，请刷新重试')).toBeVisible()
    expect(supplyRequests).toEqual([])
    await page.getByTestId('refresh-plugin-status').click()
    await expect.poll(() => stateReads).toBe(2)
    await expect.poll(() => supplyRequests.length).toBeGreaterThan(0)
    await expect(page.getByText('暂时无法读取插件状态，请刷新重试')).toHaveCount(0)
    if (entry.url === '/') await expect(page.getByRole('tab', { name: '选品中心', exact: true })).toBeVisible()
    else await expect(page.getByRole('cell', { name: '芸众测试连接', exact: true })).toBeVisible()
  })
}
