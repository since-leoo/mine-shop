import { expect, test } from '@playwright/test'
import { setupPage, expectHealthy, expectWithinViewport, capturePage, type ApiCall } from './helpers'

const center = { user_info: { nick_name: '测试会员', phone_number: '13800138000', balance: 999 }, counts_data: [], order_tag_infos: [] }
function common(call: ApiCall) {
  if (call.path === '/api/v1/auth/register/protocols') return { data: { user_agreement: '测试协议', privacy_policy: '测试隐私' } }
  if (call.path === '/api/v1/member/center') return { data: center }
  if (call.path.includes('/diy/')) return { data: { page: null, components: [] } }
  if (call.path === '/api/v1/goods/hot' || call.path === '/api/v1/products/hot') return { data: [] }
}

test('钱包快速切换忽略旧响应、失败可重试，长来源和小额金额不挤出卡片', async ({ page }) => {
  let release!: () => void
  const held = new Promise<void>(resolve => { release = resolve })
  let failPoints = true
  const api = await setupPage(page, async call => {
    if (call.path !== '/api/v1/member/wallet/transactions') return common(call)
    if (call.query.get('wallet_type') === 'balance') { await held; return { data: { list: [{ id: 1, type: 'expense', amount: 999, description: '不应该覆盖积分的旧响应', created_at: '2026-10-01 12:30:00' }], total: 1 } } }
    if (failPoints) return { status: 500, message: '积分暂不可用' }
    return { data: { list: [{ id: 2, type: 'reward', amount: 12, description: 'ABCDEFGHIJK'.repeat(10), created_at: '2026-10-13 12:30:00' }], total: 1 } }
  })
  await page.goto('/#/pages/usercenter/wallet-transactions/index')
  await expect.poll(() => api.requests.length).toBeGreaterThan(0)
  await page.locator('.wallet-tabs__item').filter({ hasText: '积分' }).click()
  await expect(page.locator('.wallet-page__retry')).toBeVisible()
  release()
  failPoints = false
  await page.locator('.wallet-page__retry').click()
  await expect(page.locator('.wallet-page__amount')).toHaveText('+12')
  await expect(page.getByText('不应该覆盖积分的旧响应')).toHaveCount(0)
  await expectWithinViewport(page.locator('.wallet-page__summary, .wallet-page__item, .wallet-page__item-header'), page.viewportSize()!.width)
  await page.locator('.wallet-tabs__item').filter({ hasText: '余额' }).click()
  await expect(page.locator('.wallet-page__amount')).toHaveText('-9.99')
  await capturePage(page, `wallet-${page.viewportSize()!.width}.png`)
  expectHealthy(api)
})

test('个人资料失败重试、长昵称显示与含百分号昵称保存', async ({ page }) => {
  let failed = true
  const name = '会员100%满意'
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/member/profile') return failed ? { status: 500, message: '资料暂不可用' } : { data: { nickname: name, phone: '13800138000', gender: 'female', avatar: '' } }
    if (call.path === '/api/v1/member/profile/update') return { data: {} }
    return common(call)
  })
  await page.goto('/#/pages/user/person-info/index')
  await expect(page.locator('.person-info__error')).toBeVisible()
  failed = false
  await page.locator('.person-info__error').click()
  await expect(page.locator('.person-info__cells')).toContainText(name)
  await page.locator('.person-info__cell').filter({ hasText: '昵称' }).click()
  await expect(page.locator('.name-edit input')).toHaveValue(name)
  await page.locator('.name-edit input').fill('新的100%昵称')
  await page.locator('.name-edit__save-btn').click()
  await expect.poll(() => api.requests.filter(call => call.path.endsWith('/profile/update')).map(call => call.body)).toEqual([{ nick_name: '新的100%昵称' }])
  await expect(page.locator('.person-info:visible')).toBeVisible()
  await expectWithinViewport(page.locator('.person-info:visible .person-info__cells'), page.viewportSize()!.width)
  expectHealthy(api)
})

test('收银台无渠道及加载失败不能发起支付，可以重试', async ({ page }) => {
  let failed = true
  const api = await setupPage(page, call => {
    if (call.path.includes('/order/submit-result/')) return { data: { status: 'created' } }
    if (call.path.includes('/order/pay-info/')) return failed ? { status: 500, message: '支付信息暂不可用' } : { data: { pay_amount: 999, pay_methods: [] } }
    return common(call)
  })
  await page.goto('/#/pages/order/cashier/index?tradeNo=TEST-ORDER')
  await expect(page.locator('.cashier__retry')).toBeVisible()
  await page.locator('.cashier__pay-btn').click()
  failed = false
  await page.locator('.cashier__retry').click()
  await expect(page.locator('.cashier__methods')).toContainText('暂无可用支付方式')
  await expect(page.locator('.cashier__method')).toHaveCount(0)
  await page.locator('.cashier__pay-btn').click()
  expect(api.requests.some(call => call.path === '/api/v1/order/payment')).toBe(false)
  await expectWithinViewport(page.locator('.cashier__amount-card, .cashier__methods, .cashier__footer'), page.viewportSize()!.width)
  await capturePage(page, `cashier-empty-${page.viewportSize()!.width}.png`)
  expectHealthy(api)
})

test('H5收银台明确小程序微信渠道限制，余额支付传递真实渠道', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path.includes('/order/submit-result/')) return { data: { status: 'created' } }
    if (call.path.includes('/order/pay-info/')) return { data: { pay_amount: 999, pay_methods: [{ channel: 'wechat', name: '微信支付', enabled: true }, { channel: 'balance', name: '余额支付', enabled: true }] } }
    // 留在收银台验证接口失败绝不展示支付成功，不触及真实支付。
    if (call.path === '/api/v1/order/payment') return { code: 422, message: '余额不足' }
    return common(call)
  })
  await page.goto('/#/pages/order/cashier/index?tradeNo=TEST-ORDER')
  await expect(page.locator('.cashier__channel-hint')).toHaveText('请在小程序使用')
  await page.locator('.cashier__method').filter({ hasText: '微信支付' }).click()
  await expect(page.locator('.cashier__method--active')).toContainText('余额支付')
  await page.locator('.cashier__pay-btn').click()
  await expect.poll(() => api.requests.filter(call => call.path === '/api/v1/order/payment').map(call => call.body)).toEqual([{ order_no: 'TEST-ORDER', pay_method: 'balance' }])
  await expect(page).toHaveURL(/cashier/)
  await expect(page.locator('.cashier__pay-btn')).not.toContainText('支付中')
  await capturePage(page, `cashier-${page.viewportSize()!.width}.png`)
  expectHealthy(api)
})

test('客服故障可以重新连接且保留返回入口', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/customer-service/config') return { data: { enabled: false, offline_message: '客服当前休息中，请稍后联系' } }
    return common(call)
  })
  await page.goto('/#/pages/customer-service/index')
  await expect(page.locator('.customer-service__error')).toContainText('客服当前休息中')
  await expect(page.locator('.page-nav__back')).toBeVisible()
  await page.getByText('重新连接', { exact: true }).click()
  await expect.poll(() => api.requests.filter(call => call.path.endsWith('/config')).length).toBe(2)
  await expectWithinViewport(page.locator('.customer-service__header, .customer-service__error, .customer-service__composer'), page.viewportSize()!.width)
  await expect(page.locator('.customer-service__send')).toHaveAttribute('disabled', '')
  await capturePage(page, `customer-offline-${page.viewportSize()!.width}.png`)
  expectHealthy(api)
})

test('客服真实聊天布局容纳长消息，发送和转人工通过接口', async ({ page }) => {
  let human = false
  const conversation = () => ({ conversation_no: 'SUPPORT-1', reception_state: human ? 'human_waiting' : 'ai_active' })
  await page.routeWebSocket(/.*/, socket => {
    socket.onMessage(message => { if (String(message).includes('"event":"auth"')) socket.send(JSON.stringify({ event: 'authenticated' })) })
  })
  const api = await setupPage(page, call => {
    if (call.path.endsWith('/customer-service/config')) return { data: { enabled: true, gateway_url: 'ws://127.0.0.1:4003', socket_path: '/socket', allow_member_image: false } }
    if (call.path.endsWith('/customer-service/conversations') || call.path.endsWith('/conversations/SUPPORT-1')) return { data: conversation() }
    if (call.path.endsWith('/socket-ticket')) return { data: { ticket: 'test-ticket' } }
    if (call.path.endsWith('/categories')) return { data: [{ id: 1, name: '商品咨询' }, { id: 2, parent_id: 1, name: '尺码' }, { id: 3, parent_id: 2, name: '尺寸选择帮助' }] }
    if (call.path.endsWith('/messages') && call.method === 'GET') return { data: [{ id: 1, sequence: 1, sender_type: 'ai', message_type: 'text', content_json: { text: '长回复'.repeat(180) } }] }
    if (call.path.endsWith('/messages') && call.method === 'POST') return { data: { id: 2, sequence: 2, sender_type: 'member', client_message_id: call.body.client_message_id, message_type: 'text', content_json: { text: call.body.text } } }
    if (call.path.endsWith('/request-human')) { human = true; return { data: {} } }
    return common(call)
  })
  await page.goto('/#/pages/customer-service/index')
  await expect(page.locator('.customer-service__title')).toHaveText('AI 客服')
  await expect(page.locator('.customer-service__message')).toHaveCount(1)
  await page.locator('.customer-service__input input').fill('我要咨询尺码')
  await page.locator('.customer-service__send').click()
  await expect(page.locator('.customer-service__message.is-mine')).toContainText('我要咨询尺码')
  await page.getByText('转人工客服', { exact: true }).click()
  await expect(page.locator('.customer-service__title')).toHaveText('在线客服')
  await expect(page.locator('.customer-service__routing')).toContainText('正在排队')
  await expectWithinViewport(page.locator('.customer-service__routing, .customer-service__composer'), page.viewportSize()!.width)
  await expect.poll(async () => (await page.locator('.customer-service__composer').boundingBox())!.y + (await page.locator('.customer-service__composer').boundingBox())!.height).toBeLessThanOrEqual(901)
  await capturePage(page, `customer-chat-${page.viewportSize()!.width}.png`)
  expectHealthy(api)
})

test('找回密码完成验证码与密码重置，帮助入口可用', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/auth/captcha') return { data: {} }
    if (call.path === '/api/v1/auth/forgotPassword') return { data: {} }
    return common(call)
  }, { guest: true })
  await page.goto('/#/pages/auth/forgot-password/index')
  await page.getByText('验证码说明', { exact: true }).click()
  await expect(page.getByText('短信验证说明', { exact: true })).toBeVisible()
  await page.getByText('确定', { exact: true }).click()
  await page.locator('input[placeholder="请输入手机号"]').fill('13800138000')
  await page.locator('.auth-code-row__button').click()
  await expect(page.locator('.auth-code-row__button')).toContainText('s')
  await page.locator('.auth-code-row input').fill('123456')
  await page.locator('input[placeholder="请输入 6-20 位密码"]').fill('test-password')
  await page.locator('input[placeholder="请再次输入新密码"]').fill('test-password')
  await expectWithinViewport(page.locator('.auth-code-row, .auth-card'), page.viewportSize()!.width)
  await page.getByText('重置密码', { exact: true }).click()
  await expect(page).toHaveURL(/login/)
  expect(api.requests.find(call => call.path.endsWith('/forgotPassword'))?.body).toEqual({ phone: '13800138000', code: '123456', password: 'test-password', password_confirmation: 'test-password' })
  expectHealthy(api)
})

test('短信登录发送login场景、验证后返回个人中心', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/auth/captcha') return { data: {} }
    if (call.path === '/api/v1/login/h5Sms' && call.body.code !== '123456') return { code: 422, message: '验证码错误或已失效' }
    if (call.path === '/api/v1/login/h5Sms') return { data: { token: 'sms-token', member: { id: 1, phone: '13800138000', nickname: '测试会员' } } }
    return common(call)
  }, { guest: true })
  await page.goto('/#/pages/auth/login/index')
  await page.locator('.auth-tab').filter({ hasText: '短信验证码登录' }).click()
  await expect(page.locator('input[placeholder="请输入手机号"]')).toHaveCount(1)
  await page.locator('input[placeholder="请输入手机号"]').fill('13800138000')
  await page.locator('.auth-code-row__button').click()
  await expect(page.locator('.auth-code-row__button')).toContainText('s')
  expect(api.requests.find(call => call.path.endsWith('/captcha'))?.body).toEqual({ phone: '13800138000', scene: 'login' })
  await page.locator('.auth-code-row input').fill('000000')
  await page.locator('.auth-button--primary').click()
  await expect(page.getByText('验证码错误或已失效', { exact: true })).toBeVisible()
  await expect(page).toHaveURL(/login/)
  expect(await page.evaluate(() => localStorage.getItem('accessToken'))).toBeNull()
  await page.locator('.auth-code-row input').fill('123456')
  await page.locator('.auth-button--primary').click()
  await expect(page).toHaveURL(/#\/pages\/usercenter\/index$/)
  expect(api.requests.filter(call => call.path.endsWith('/h5Sms')).at(-1)?.body).toEqual({ phone: '13800138000', code: '123456' })
  await expect(page.locator('.usercenter__nickname')).toHaveText('测试会员')
  expectHealthy(api)
})


test('地址加载失败有重试入口，不把接口错误当成空地址', async ({ page }) => {
  let failed = true
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/member/addresses') return failed ? { code: 500, message: '地址暂不可用' } : { data: [] }
    return common(call)
  })
  await page.goto('/#/pages/user/address/list/index')
  await expect(page.getByText('地址暂不可用，点击重试', { exact: true })).toBeVisible()
  await expect(page.getByText('暂无收货地址', { exact: true })).toHaveCount(0)
  failed = false
  await page.getByText('地址暂不可用，点击重试', { exact: true }).click()
  await expect(page.getByText('暂无收货地址', { exact: true })).toBeVisible()
  expectHealthy(api)
})
