import { expect, test, type Page } from '@playwright/test'

const credentials = { phone: '13800138000', password: 'password-test-123' }
const member = { id: 11, nickname: '密码登录会员', phone: credentials.phone, avatar: '' }

async function mockPasswordApi(page: Page, baseURL: string, rejected = false) {
  const protectedHeaders: string[] = []
  await page.route('**/*', route => new URL(route.request().url()).origin === baseURL
    ? route.continue()
    : route.abort())
  await page.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname
    let data: unknown = {}
    let code = 200
    if (path === '/api/v1/login/h5Password') {
      code = rejected ? 422 : 200
      data = rejected ? null : { token: 'password-test-token', member }
    } else if (path === '/api/v1/member/center') {
      protectedHeaders.push(route.request().headers().authorization || '')
      data = {
        userInfo: { nickName: member.nickname, phoneNumber: member.phone, avatarUrl: '', balance: 54321 },
        countsData: [{ type: 'coupon', num: 2 }, { type: 'point', num: 88 }, { type: 'collect', num: 1 }],
        orderTagInfos: [],
      }
    } else if (path === '/api/v1/member/profile') {
      protectedHeaders.push(route.request().headers().authorization || '')
      data = { member }
    } else if (path.includes('/diy/')) {
      data = { page: null, components: [] }
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: { 'access-control-allow-origin': '*' },
      body: JSON.stringify({ code, data, message: code === 200 ? '' : '手机号或密码错误' }),
    })
  })
  return protectedHeaders
}

async function submitPassword(page: Page) {
  const form = page.locator('.auth-page--login:visible')
  await form.locator('input[placeholder="请输入手机号"]').fill(credentials.phone)
  await form.locator('input[type="password"]').fill(credentials.password)
  const request = page.waitForRequest(req => new URL(req.url()).pathname === '/api/v1/login/h5Password' && req.method() === 'POST')
  await form.locator('.auth-button--primary').click()
  expect((await request).postDataJSON()).toEqual(credentials)
}

async function expectLoggedInUsercenter(page: Page, protectedHeaders: string[]) {
  await expect(page).toHaveURL(/#\/pages\/usercenter\/index$/)
  await expect(page.locator('.usercenter--h5:visible .usercenter__nickname')).toHaveText(member.nickname)
  await expect(page.locator('.usercenter--h5:visible .usercenter__wallet-num')).toHaveText(['2', '88', '¥543.21', '1'])
  expect(protectedHeaders.length).toBeGreaterThan(0)
  expect(protectedHeaders.every(value => value === 'Bearer password-test-token')).toBe(true)
  expect(await page.evaluate(() => JSON.parse(localStorage.getItem('accessToken') || '{}').data)).toBe('password-test-token')
  expect(await page.evaluate(() => localStorage.getItem('h5LoginRedirect'))).toBeNull()
}

test('直接打开登录页，密码登录默认进入个人中心并展示资料', async ({ page, baseURL }) => {
  const protectedHeaders = await mockPasswordApi(page, baseURL!)
  await page.goto('/#/pages/auth/login/index')
  await submitPassword(page)
  await expectLoggedInUsercenter(page, protectedHeaders)
})

test('未登录访问个人中心，密码登录后回到个人中心', async ({ page, baseURL }) => {
  const protectedHeaders = await mockPasswordApi(page, baseURL!)
  await page.goto('/#/pages/usercenter/index')
  await expect(page).toHaveURL(/#\/pages\/auth\/login\/index\?redirect=/)
  await submitPassword(page)
  await expectLoggedInUsercenter(page, protectedHeaders)
})

test('登录页没有query时使用缓存的个人中心回跳地址', async ({ page, baseURL }) => {
  const protectedHeaders = await mockPasswordApi(page, baseURL!)
  await page.goto('/#/pages/auth/login/index')
  // Taro H5 的存储封装采用 {data}，模拟此前保存的受保护页面回跳地址。
  await page.evaluate(() => localStorage.setItem('h5LoginRedirect', JSON.stringify({ data: '/pages/usercenter/index' })))
  await submitPassword(page)
  await expectLoggedInUsercenter(page, protectedHeaders)
})

test('明确指定其他受保护页面时保留回跳地址和query', async ({ page, baseURL }) => {
  const protectedHeaders = await mockPasswordApi(page, baseURL!)
  const target = '/pages/user/person-info/index?source=password-login'
  await page.goto(`/#/pages/auth/login/index?redirect=${encodeURIComponent(target)}`)
  // 显式地址优先于旧缓存，不能统一改成用户中心。
  await page.evaluate(() => localStorage.setItem('h5LoginRedirect', JSON.stringify({ data: '/pages/usercenter/index' })))
  await submitPassword(page)
  await expect(page).toHaveURL(/#\/pages\/user\/person-info\/index\?source=password-login$/)
  await expect(page.locator('.person-info:visible .person-info__cell-value').first()).toHaveText(member.nickname)
  expect(protectedHeaders).toContain('Bearer password-test-token')
  expect(await page.evaluate(() => localStorage.getItem('h5LoginRedirect'))).toBeNull()
})

test('密码错误显示失败提示，不跳转且不保存登录凭证', async ({ page, baseURL }) => {
  const protectedHeaders = await mockPasswordApi(page, baseURL!, true)
  await page.goto('/#/pages/auth/login/index')
  await submitPassword(page)
  await expect(page.getByText('手机号或密码错误', { exact: true })).toBeVisible()
  await expect(page).toHaveURL(/#\/pages\/auth\/login\/index$/)
  expect(await page.evaluate(() => ({
    token: localStorage.getItem('accessToken'),
    profile: localStorage.getItem('memberProfile'),
  }))).toEqual({ token: null, profile: null })
  expect(protectedHeaders).toEqual([])
})
