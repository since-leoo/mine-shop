import { expect, test, type Page, type Route } from '@playwright/test'

const registeredMember = { nickname: '注册会员', phone: '13800138000', avatar: '' }
const refreshedCenter = {
  userInfo: { nickName: '已刷新会员', phoneNumber: '13800138000', avatarUrl: '/__test-member-avatar.svg', balance: 12345 },
  countsData: [{ type: 'coupon', num: 3 }, { type: 'point', num: 456 }, { type: 'collect', num: 7 }],
  orderTagInfos: [],
}

async function mockApi(page: Page, baseURL: string, initialMode: 'hold' | 'success' | 'failure', avatarUrl = refreshedCenter.userInfo.avatarUrl) {
  let mode = initialMode
  const center = { ...refreshedCenter, userInfo: { ...refreshedCenter.userInfo, avatarUrl } }
  const pending: Route[] = []
  const centerRequests: string[] = []
  const respond = (route: Route, status: number, data: unknown) => route.fulfill({
    status,
    contentType: 'application/json',
    headers: { 'access-control-allow-origin': '*' },
    body: JSON.stringify({ code: status, data, message: status === 200 ? '' : '个人中心暂时不可用' }),
  })
  await page.route('**/*', route => new URL(route.request().url()).origin === baseURL
    ? route.continue()
    : route.abort())
  await page.route('**/__test-member-avatar.svg', route => route.fulfill({
    contentType: 'image/svg+xml',
    body: '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect width="64" height="64" fill="#e8836b"/><circle cx="32" cy="24" r="12" fill="white"/></svg>',
  }))
  await page.route('**/__missing-member-avatar.svg', route => route.fulfill({ status: 404, body: 'missing avatar' }))
  await page.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname
    if (path === '/api/v1/auth/register') {
      await respond(route, 200, { token: 'test-token', member: registeredMember })
    } else if (path === '/api/v1/member/center') {
      centerRequests.push(route.request().headers().authorization || '')
      if (mode === 'hold') { pending.push(route); return }
      await respond(route, mode === 'failure' ? 500 : 200, center)
    } else if (path.includes('/diy/')) {
      await respond(route, 200, { page: null, components: [] })
    } else {
      await respond(route, 200, { code: '123456', userAgreement: '', privacyPolicy: '' })
    }
  })
  return {
    centerRequests,
    hold() { mode = 'hold' },
    async succeed() {
      mode = 'success'
      await Promise.all(pending.splice(0).map(route => respond(route, 200, center)))
    },
  }
}

async function registerFromGuestUsercenter(page: Page) {
  await page.goto('/#/pages/usercenter/index')
  await expect(page).toHaveURL(/#\/pages\/auth\/login\/index/)
  await page.getByText('去注册', { exact: true }).click()
  await expect(page).toHaveURL(/#\/pages\/auth\/register\/index/)
  const form = page.locator('.auth-register-page-entry:visible')
  await form.locator('input[placeholder="请输入手机号"]').fill(registeredMember.phone)
  await form.locator('.auth-code-row input').fill('123456')
  await form.locator('input[placeholder="请输入 6-20 位登录密码"]').fill('register-test-123')
  await form.locator('input[placeholder="请再次输入登录密码"]').fill('register-test-123')
  const request = page.waitForRequest(req => new URL(req.url()).pathname === '/api/v1/auth/register' && req.method() === 'POST')
  await form.locator('.auth-button--primary').click()
  expect((await request).postDataJSON()).toEqual({
    phone: registeredMember.phone, code: '123456', password: 'register-test-123', password_confirmation: 'register-test-123',
  })
  await expect(page).toHaveURL(/#\/pages\/usercenter\/index$/)
  await expect(page.locator('.usercenter--h5:visible')).toBeVisible()
}

async function expectLoadedAvatar(page: Page, defaultAvatar = false) {
  const avatar = page.locator('.usercenter--h5:visible .usercenter__avatar img')
  await expect(avatar).toBeVisible()
  await expect.poll(() => avatar.evaluate(img => (img as HTMLImageElement).complete && (img as HTMLImageElement).naturalWidth > 0)).toBe(true)
  const source = await avatar.getAttribute('src')
  if (defaultAvatar) {
    expect(source).not.toContain('__test-member-avatar.svg')
    expect(source).not.toContain('__missing-member-avatar.svg')
    expect(await avatar.evaluate(img => {
      const src = (img as HTMLImageElement).src
      return src.startsWith('data:image/') || new URL(src).origin === location.origin
    })).toBe(true)
  } else {
    expect(source).toContain('__test-member-avatar.svg')
  }
  await expect(page.locator('.usercenter--h5:visible .usercenter__avatar-emoji')).toHaveCount(0)
}

async function expectRefreshedCenter(page: Page) {
  await expect(page.locator('.usercenter--h5:visible .usercenter__nickname')).toHaveText('已刷新会员')
  await expect(page.locator('.usercenter--h5:visible .usercenter__wallet-num')).toHaveText(['3', '456', '¥123.45', '7'])
  await expectLoadedAvatar(page)
}

test('注册返回保留缓存会员与默认头像，接口完成后刷新，重新载入仍保留资料', async ({ page, baseURL }) => {
  const api = await mockApi(page, baseURL!, 'hold')
  await registerFromGuestUsercenter(page)
  // 暂停接口响应，确保展示来自刚注册的缓存，而不是请求碰巧足够快。
  await expect(page.locator('.usercenter--h5:visible .usercenter__nickname')).toHaveText('注册会员')
  await expectLoadedAvatar(page, true)
  await expect.poll(() => api.centerRequests.length).toBeGreaterThan(0)
  expect(api.centerRequests.every(value => value === 'Bearer test-token')).toBe(true)
  await api.succeed()
  await expectRefreshedCenter(page)
  api.hold()
  await page.reload()
  await expect(page).toHaveURL(/#\/pages\/usercenter\/index$/)
  await expect(page.locator('.usercenter--h5:visible .usercenter__nickname')).toHaveText('已刷新会员')
  await expectLoadedAvatar(page)
  await api.succeed()
})

test('个人中心头部不会遮挡资产数字，重叠区域命中钱包', async ({ page, baseURL }) => {
  await mockApi(page, baseURL!, 'success')
  await registerFromGuestUsercenter(page)
  await expectRefreshedCenter(page)
  // 等待路由过渡完成后仍需通过真实命中检测；toBeVisible 本身不能发现被头部盖住。
  await expect.poll(() => page.locator('.usercenter--h5:visible .usercenter__wallet-num').evaluateAll(numbers => numbers.map(number => {
    const rect = number.getBoundingClientRect()
    const hit = document.elementFromPoint(rect.x + rect.width / 2, rect.y + 1)
    return hit?.closest('.usercenter__wallet-card') ? 'wallet' : String(hit?.className)
  })), { message: '四个资产数字顶部必须命中钱包，不能被 header 或其他页面遮住' }).toEqual(['wallet', 'wallet', 'wallet', 'wallet'])
  if (process.env.H5_USERCENTER_SCREENSHOT && page.viewportSize()?.width === 375) {
    await page.screenshot({ path: process.env.H5_USERCENTER_SCREENSHOT, fullPage: true })
  }
})

test('个人中心失败显示重新加载，重试成功恢复昵称头像与资产并清除错误', async ({ page, baseURL }) => {
  const api = await mockApi(page, baseURL!, 'failure')
  await registerFromGuestUsercenter(page)
  const error = page.locator('.usercenter--h5:visible .usercenter__load-error')
  await expect(error).toBeVisible()
  await expect(page.locator('.usercenter--h5:visible .usercenter__nickname')).toHaveText('注册会员')
  await expectLoadedAvatar(page, true)
  await api.succeed()
  await error.getByText('重新加载', { exact: true }).click()
  await expectRefreshedCenter(page)
  await expect(error).toHaveCount(0)
  expect(api.centerRequests.length).toBeGreaterThanOrEqual(2)
})

test('会员头像返回404后回退到可加载的本地默认头像', async ({ page, baseURL }) => {
  await mockApi(page, baseURL!, 'success', '/__missing-member-avatar.svg')
  const failedAvatar = page.waitForResponse(response => new URL(response.url()).pathname === '/__missing-member-avatar.svg' && response.status() === 404)
  await registerFromGuestUsercenter(page)
  await failedAvatar
  await expect(page.locator('.usercenter--h5:visible .usercenter__nickname')).toHaveText('已刷新会员')
  await expectLoadedAvatar(page, true)
})

test('切换账号后晚到的旧个人中心响应不能覆盖新账号缓存', async ({ page, baseURL }) => {
  const api = await mockApi(page, baseURL!, 'hold')
  await registerFromGuestUsercenter(page)
  await expect(page.locator('.usercenter--h5:visible .usercenter__nickname')).toHaveText('注册会员')
  await expect.poll(() => api.centerRequests.length).toBeGreaterThan(0)
  expect(api.centerRequests.every(value => value === 'Bearer test-token')).toBe(true)
  const nextMember = { id: 2, nickname: '另一个账号', phone: '13900139000', avatar: '' }
  await page.evaluate((member) => {
    // 按已安装 Taro H5 的 {data} 存储协议模拟另一个登录窗口切换账号。
    if (JSON.parse(localStorage.getItem('accessToken') || '{}').data !== 'test-token') {
      throw new Error('真实注册流程尚未写入测试 token')
    }
    localStorage.setItem('accessToken', JSON.stringify({ data: 'second-account-token' }))
    localStorage.setItem('memberProfile', JSON.stringify({ data: member }))
  }, nextMember)
  const response = page.waitForResponse(res => new URL(res.url()).pathname === '/api/v1/member/center')
  await api.succeed()
  await (await response).finished()
  // 等响应处理和微任务执行完再检查，避免在旧回调尚未运行时提前得到假阳性。
  await page.evaluate(() => new Promise<void>(resolve => requestAnimationFrame(() => requestAnimationFrame(() => resolve()))))
  expect(await page.evaluate(() => ({
    token: JSON.parse(localStorage.getItem('accessToken') || '{}').data,
    member: JSON.parse(localStorage.getItem('memberProfile') || '{}').data,
  }))).toEqual({ token: 'second-account-token', member: nextMember })
})
