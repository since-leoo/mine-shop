import { expect, test } from '@playwright/test'

const settings = () => ({ enabled: false, h5_enabled: true, mini_program_enabled: true, h5_base_url: 'https://shop.example.com', commission_rate_bps: 1000, settlement_days: 7, withdrawal_min_amount: 1000, withdrawal_fee_bps: 0, commission_mode: 'all_orders', excluded_product_ids: [], product_rules: [], activity_enabled: false })

test('设置写入后端，元与百分比准确换算，保存失败保留编辑内容', async ({ page }) => {
  const writes: any[] = []
  let failed = false
  await page.route('**/admin/distribution/settings', async (route) => {
    if (route.request().method() === 'PUT') {
      const data = route.request().postDataJSON()
      writes.push(data)
      return failed ? route.fulfill({ status: 409, json: { message: '配置已变更，请重试' } }) : route.fulfill({ json: { data } })
    }
    return route.fulfill({ json: { data: settings() } })
  })
  await page.goto('/?page=settings')
  await page.getByTestId('distribution-enabled').click()
  await page.getByTestId('commission-rate').locator('input').fill('12.34')
  await page.getByTestId('withdrawal-min').locator('input').fill('25.67')
  await page.getByTestId('save-settings').click()
  await expect.poll(() => writes.length).toBe(1)
  expect(writes[0]).toMatchObject({ enabled: true, commission_rate_bps: 1234, withdrawal_min_amount: 2567 })
  await expect(page.getByText('分销与佣金设置已保存')).toBeVisible()
  failed = true
  await page.getByTestId('distribution-enabled').click()
  await page.getByTestId('save-settings').click()
  await expect(page.getByRole('alert').filter({ hasText: '配置已变更，请重试' })).toBeVisible()
  await expect(page.getByTestId('distribution-enabled')).not.toHaveClass(/is-checked/)
})

test('成员团队下钻分别查询本人及全部团队订单', async ({ page }) => {
  const requests: URL[] = []
  await page.route('**/admin/distribution/**', async (route) => {
    const url = new URL(route.request().url())
    requests.push(url)
    if (url.pathname.endsWith('/overview')) {
      return route.fulfill({ json: { data: {} } })
    }
    const list = url.pathname.endsWith('/orders')
      ? [{ id: 20, order_no: 'ORDER-20', member_id: 2, paid_amount: 12345, refund_amount: 1000, net_amount: 11345, status: 'completed' }]
      : [{ id: 2, nickname: '粉丝乙', parent_id: 1, invite_code: 'B002', direct_count: 1, team_count: 4, net_consumption_amount: 11345, order_count: 2 }]
    return route.fulfill({ json: { data: { list, total: 1 } } })
  })
  await page.goto('/')
  await page.getByRole('tab', { name: '成员与团队' }).click()
  await page.getByRole('button', { name: '2', exact: true }).click()
  await expect(page.getByRole('cell', { name: 'ORDER-20', exact: true })).toBeVisible()
  expect(requests.some(url => url.pathname.endsWith('/orders') && url.searchParams.get('member_id') === '2' && url.searchParams.get('scope') === 'self')).toBe(true)
  await page.getByRole('tab', { name: '成员与团队' }).click()
  await page.getByRole('button', { name: '查看团队', exact: true }).click()
  await expect(page.getByText('粉丝乙 的全部团队')).toBeVisible()
  expect(requests.some(url => url.pathname.endsWith('/members/2/team') && url.searchParams.get('scope') === 'all')).toBe(true)
  await page.getByRole('button', { name: '查看该团队全部订单' }).click()
  await expect(page.getByRole('cell', { name: '¥113.45', exact: true })).toBeVisible()
  expect(requests.some(url => url.pathname.endsWith('/orders') && url.searchParams.get('member_id') === '2' && url.searchParams.get('scope') === 'all')).toBe(true)
})

test('提现状态约束和打款流水校验', async ({ page }) => {
  const writes: any[] = []
  const row = { id: 4, member_id: 2, withdrawal_no: 'WD004', amount: 10000, fee_amount: 100, net_amount: 9900, status: 'pending', account_name: '张三', account_no: '62220001', channel: 'bank' }
  await page.route('**/admin/distribution/**', async (route) => {
    if (route.request().method() === 'POST') {
      const data = route.request().postDataJSON()
      writes.push(data)
      row.status = data.action === 'approve' ? 'approved' : 'paid'
      return route.fulfill({ json: { data: null } })
    }
    return route.fulfill({ json: { data: new URL(route.request().url()).pathname.endsWith('/overview') ? {} : { list: [row], total: 1 } } })
  })
  await page.goto('/')
  await page.getByRole('tab', { name: '提现审核' }).click()
  await page.getByRole('button', { name: '通过', exact: true }).click()
  await page.getByRole('dialog').getByRole('button', { name: '审核通过', exact: true }).click()
  await expect(page.getByRole('button', { name: '确认打款', exact: true })).toBeVisible()
  expect(writes[0].action).toBe('approve')
  await page.getByRole('button', { name: '确认打款', exact: true }).click()
  const dialog = page.getByRole('dialog')
  await dialog.getByRole('button', { name: '确认打款', exact: true }).click()
  await expect(dialog.getByText('请填写真实打款流水号后再确认')).toBeVisible()
  expect(writes.length).toBe(1)
  await dialog.locator('.el-form-item').filter({ hasText: '打款流水号' }).locator('input').fill('BANK-20261016-4')
  await dialog.getByRole('button', { name: '确认打款', exact: true }).click()
  await expect(page.getByText('已打款', { exact: true })).toBeVisible()
  expect(writes[1]).toMatchObject({ action: 'pay', payment_no: 'BANK-20261016-4' })
  await expect(page.getByRole('button', { name: '通过', exact: true })).toHaveCount(0)
})

test('无分销菜单权限记录时仍只允许超级管理员管理', async ({ page }) => {
  const paths: string[] = []
  await page.addInitScript(() => {
    (window as any).__superAdmin = false
  })
  await page.route('**/admin/distribution/**', async (route) => {
    const path = new URL(route.request().url()).pathname
    paths.push(path)
    return route.fulfill({ json: { data: path.endsWith('/overview') ? {} : { list: [{ id: 4, withdrawal_no: 'WD004', status: 'pending' }], total: 1 } } })
  })
  await page.goto('/?page=settings')
  await expect(page.getByText('分销管理仅限超级管理员')).toBeVisible()
  expect(paths).toHaveLength(0)
  await page.goto('/')
  await expect(page.getByText('分销管理仅限超级管理员')).toBeVisible()
  await expect(page.getByRole('tab')).toHaveCount(0)
  expect(paths).toHaveLength(0)
})

test('插件卡片直达后端佣金设置并移除本地假启停', async ({ page }) => {
  await page.route('**/admin/distribution/**', route => route.fulfill({ json: { data: settings() } }))
  await page.goto('/?page=shell')
  await expect(page.getByText('业务开关在设置中管理')).toBeVisible()
  await page.getByRole('button', { name: '设置', exact: true }).click()
  await expect(page.getByRole('dialog').getByTestId('distribution-enabled')).toBeVisible()
  await expect(page.getByRole('button', { name: '停用插件', exact: true })).toHaveCount(0)
})

test('DIY 注册、属性开关和真实预览联动，不持久化用户统计', async ({ page }) => {
  await page.route('**/admin/product/category/tree**', route => route.fulfill({ json: { data: [] } }))
  await page.goto('/?page=diy')
  const preview = page.locator('.preview-distribution')
  await expect(preview.getByText('粉丝团队')).toBeVisible()
  await expect(preview.getByText('消费订单')).toBeVisible()
  await expect(preview.getByText('可提现佣金')).toBeVisible()
  await page.locator('.el-form-item').filter({ hasText: '显示佣金统计' }).locator('.el-switch').click()
  await expect(preview.getByText('可提现佣金')).toHaveCount(0)
  await page.locator('.el-form-item').filter({ hasText: '模块标题' }).locator('input').fill('我的分享收益')
  await expect(preview.getByText('我的分享收益')).toBeVisible()
  await page.locator('.el-form-item').filter({ hasText: '显示邀请入口' }).locator('.el-switch').click()
  await expect(preview.getByText('邀请好友 ›')).toHaveCount(0)
})

test('SKU 商品固定佣金与排除商品按后端协议保存', async ({ page }) => {
  let saved: any
  await page.route('**/admin/distribution/settings', async (route) => {
    if (route.request().method() === 'PUT') {
      saved = route.request().postDataJSON()
    }
    return route.fulfill({ json: { data: saved || settings() } })
  })
  await page.goto('/?page=settings')
  await page.getByTestId('add-product-rule').click()
  const rule = page.locator('.product-rule')
  await rule.locator('.el-form-item').filter({ hasText: /^商品 ID/ }).locator('input').fill('12')
  await rule.locator('.el-form-item').filter({ hasText: 'SKU ID' }).locator('input').fill('345')
  await rule.locator('.el-select').click()
  await page.getByRole('option', { name: '固定金额（元/件）', exact: true }).click()
  await rule.locator('.el-form-item').filter({ hasText: '固定佣金（元/件）' }).locator('input').fill('1.23')
  await page.getByPlaceholder('例如：12, 25, 36').fill('22, 22, 33')
  await page.getByTestId('save-settings').click()
  await expect.poll(() => saved?.product_rules).toEqual([{ product_id: 12, sku_id: 345, mode: 'fixed', value: 123 }])
  expect(saved.excluded_product_ids).toEqual([22, 33])
})

test('操作日志请求分页并显示业务审计详情', async ({ page }) => {
  const requests: URL[] = []
  await page.route('**/admin/distribution/**', async (route) => {
    const url = new URL(route.request().url())
    requests.push(url)
    return route.fulfill({ json: { data: url.pathname.endsWith('/overview') ? {} : { list: [{ id: 8, operator_id: 2, action: 'withdrawal.pay', subject_id: 4, detail: '{"status":"paid","remark":"已核对"}', created_at: '2026-10-16 10:00:00' }], total: 1 } } })
  })
  await page.goto('/')
  await page.getByRole('tab', { name: '操作日志' }).click()
  await expect(page.getByRole('cell', { name: 'withdrawal.pay', exact: true })).toBeVisible()
  await expect(page.locator('.log-detail')).toContainText('已核对')
  expect(requests.some(url => url.pathname.endsWith('/logs') && url.searchParams.get('page_size') === '20')).toBe(true)
})
