import { expect, test } from '@playwright/test'
import { createHash } from 'node:crypto'
import { setupPage, expectHealthy, productImage, type ApiCall, type ApiReply } from './helpers'

const deferred = () => { let resolve!: () => void; const promise = new Promise<void>(done => { resolve = done }); return { promise, resolve } }
function common(call: ApiCall): ApiReply | undefined {
  if (call.path.includes('/order/submit-result/')) return { data: { status: 'created' } }
  if (call.path.includes('/order/pay-info/')) return { data: { pay_amount: 999, total_amount: 999, pay_methods: [{ channel: 'balance', name: '余额支付', enabled: true }] } }
  if (call.path === '/api/v1/order/list') return { data: { list: [], pagination: { total: 0 } } }
  if (call.path === '/api/v1/order/statistics') return { data: {} }
  if (call.path === '/api/v1/after-sales') return { data: { list: [], pagination: { total: 0 } } }
  if (call.path === '/api/v1/products') return { data: { list: [] } }
}

test('零应付金额不回退原价，成功后继续读取真实支付结果', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path.includes('/order/pay-info/')) return { data: { pay_amount: 0, total_amount: 9999, pay_methods: [{ channel: 'balance', name: '余额支付', enabled: true }] } }
    if (call.path === '/api/v1/order/payment') return { data: { is_paid: true } }
    if (call.path.includes('/order/detail/')) return { data: { order_no: 'ZERO', status: 'paid', pay_status: 'paid', pay_amount: 0 } }
    return common(call)
  })
  await page.goto('/#/pages/order/cashier/index?tradeNo=ZERO&payAmount=88888')
  await expect(page.locator('.cashier__method--active')).toContainText('余额支付')
  await expect(page.locator('.cashier__price')).toHaveText('¥0.00')
  await page.locator('.cashier__pay-btn').click()
  await expect(page).toHaveURL(/pay-result\/index/)
  await expect(page.locator('.pay-result__title')).toHaveText('支付成功')
  await expect(page.locator('.pay-result__price')).toHaveText('¥0.00')
  expect(api.requests.find(r => r.path === '/api/v1/order/payment')?.body).toEqual({ order_no: 'ZERO', pay_method: 'balance' })
  expectHealthy(api)
})

test('支付请求防连击，取消离页后迟到的成功响应不能拉回结果页', async ({ page }) => {
  const gate = deferred()
  const api = await setupPage(page, async call => {
    if (call.path === '/api/v1/order/payment') { await gate.promise; return { data: { is_paid: true } } }
    return common(call)
  })
  await page.goto('/#/pages/order/cashier/index?tradeNo=LATE')
  await expect(page.locator('.cashier__method--active')).toBeVisible()
  await page.locator('.cashier__pay-btn').click({ clickCount: 2 })
  await expect.poll(() => api.requests.filter(r => r.path === '/api/v1/order/payment').length).toBe(1)
  await page.locator('.cashier__cancel-btn').click()
  await expect(page).toHaveURL(/order-list\/index/)
  gate.resolve()
  await page.waitForTimeout(300)
  await expect(page).toHaveURL(/order-list\/index/)
  expect(api.requests.some(r => r.path.includes('/order/detail/'))).toBe(false)
  expectHealthy(api)
})

test('收银台离页后处理中响应不能再启动轮询', async ({ page }) => {
  const gate = deferred()
  let polls = 0
  const api = await setupPage(page, async call => {
    if (call.path.includes('/order/submit-result/')) { polls++; await gate.promise; return { data: { status: 'processing' } } }
    return common(call)
  })
  await page.goto('/#/pages/order/cashier/index?tradeNo=PENDING')
  await expect.poll(() => polls).toBe(1)
  // 浏览器导航离页；遮罩不应阻止浏览器本身的导航，也不能留下后台定时器。
  await page.evaluate(() => { location.hash = '/pages/order/invoice/index' })
  await expect(page.locator('.invoice__notice')).toBeVisible()
  gate.resolve()
  await page.waitForTimeout(1800)
  expect(polls).toBe(1)
  expect(api.requests.some(r => r.path.includes('/order/pay-info/'))).toBe(false)
  expectHealthy(api)
})

test('头像选图走真实上传流程，携带完整API签名后更新资料', async ({ page }) => {
  let avatar = ''
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/member/profile') return { data: { nickname: '头像测试会员', phone: '13800138000', avatar, gender: 'female' } }
    if (call.path === '/api/v1/upload/image') return { data: { url: productImage } }
    if (call.path === '/api/v1/member/profile/update') { avatar = call.body.avatar_url; return { data: {} } }
    return common(call)
  })
  await page.goto('/#/pages/user/person-info/index')
  await expect(page.locator('.person-info__cells')).toBeVisible()
  const chooser = page.waitForEvent('filechooser')
  await page.locator('.person-info__avatar-section').click()
  await (await chooser).setFiles({ name: 'avatar.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN84AAAAASUVORK5CYII=', 'base64') })
  await expect.poll(() => api.requests.filter(r => r.path === '/api/v1/member/profile/update').length).toBe(1)
  const upload = api.requests.find(r => r.path === '/api/v1/upload/image')!
  expect(upload.headers.authorization).toBe('Bearer site-test-token')
  expect(upload.headers['x-client-id']).toBe('h5')
  expect(upload.headers['x-timestamp']).toMatch(/^\d+$/)
  expect(upload.headers['x-nonce']).toBeTruthy()
  const bodyHash = createHash('sha256').update('').digest('hex') // 上传multipart按服务端约定不参与签名内容。
  expect(upload.headers['x-body-sha256']).toBe(bodyHash)
  expect(upload.headers['x-signature']).toMatch(/^[a-f0-9]{64}$/)
  await expect(page.locator('.person-info__avatar')).toHaveAttribute('src', productImage)
  expectHealthy(api)
})
