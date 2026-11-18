import { expect, test, type Page } from '@playwright/test'

test.beforeEach(async ({ page, baseURL }) => {
  // 阻止外部资源和真实接口；只允许本地静态构建以及下面明确模拟的 API。
  await page.route('**/*', route => new URL(route.request().url()).origin === baseURL
    ? route.continue()
    : route.abort())
  await page.route('**/api/**', route => route.fulfill({
    status: 200,
    contentType: 'application/json',
    headers: { 'access-control-allow-origin': '*' },
    body: JSON.stringify({ code: 200, data: { code: '123456', user_agreement: '', privacy_policy: '' } }),
  }))
})

async function checkCodeRow(page: Page) {
  const row = page.locator('.auth-code-row')
  const input = row.locator('input')
  const button = row.locator('.auth-code-row__button')
  await expect(row).toBeVisible()
  const geometry = await row.evaluate((element) => {
    const rect = (node: Element) => {
      const { x, y, width, height, right } = node.getBoundingClientRect()
      return { x, y, width, height, right }
    }
    return {
      row: rect(element),
      input: rect(element.querySelector('input')!),
      button: rect(element.querySelector('.auth-code-row__button')!),
      overflow: element.scrollWidth - element.clientWidth,
      viewport: window.innerWidth,
      // 设计宽度 216rpx（375px 视口为 108px）；Taro 按根字号缩放。
      expectedButtonWidth: 216 / 40 * Number.parseFloat(getComputedStyle(document.documentElement).fontSize),
    }
  })
  expect(geometry.input.width, `验证码输入框几何信息: ${JSON.stringify(geometry)}`).toBeGreaterThanOrEqual(60)
  expect(geometry.overflow).toBeLessThanOrEqual(1)
  expect(geometry.row.x).toBeGreaterThanOrEqual(-1)
  expect(geometry.row.right).toBeLessThanOrEqual(geometry.viewport + 1)
  expect(geometry.input.x).toBeGreaterThanOrEqual(geometry.row.x - 1)
  expect(geometry.input.right).toBeLessThanOrEqual(geometry.button.x + 1)
  expect(geometry.button.right).toBeLessThanOrEqual(geometry.row.right + 1)
  expect(Math.abs(geometry.button.width - geometry.expectedButtonWidth)).toBeLessThanOrEqual(1)
  await input.fill('123456')
  await expect(input).toHaveValue('123456')
  return { input, button, width: geometry.button.width }
}

test('登录短信验证码行可用，切回密码仍可编辑', async ({ page }) => {
  await page.goto('/#/pages/auth/login/index')
  const phone = page.locator('.auth-page--login:visible input[placeholder="请输入手机号"]')
  await expect(phone).toHaveCount(1)
  await phone.fill('13800138000')
  await page.locator('.auth-tab').nth(1).click()
  await expect(phone).toHaveCount(1)
  await expect(phone).toHaveValue('13800138000')
  await expect(page.locator('.auth-page--login:visible').getByText('登录账号', { exact: true })).toHaveCount(0)
  await checkCodeRow(page)
  if (process.env.H5_LOGIN_SCREENSHOT && page.viewportSize()?.width === 375) {
    await page.screenshot({ path: process.env.H5_LOGIN_SCREENSHOT, fullPage: true })
  }
  await phone.fill('13900139000')
  await page.locator('.auth-tab').nth(0).click()
  await expect(phone).toHaveCount(1)
  await expect(phone).toHaveValue('13900139000')
  const password = page.locator('input[type="password"]')
  await password.fill('layout-test-password')
  await expect(password).toHaveValue('layout-test-password')
})

for (const [route, scene, title] of [
  ['register', 'register', '注册'],
  ['forgot-password', 'forgot_password', '忘记密码'],
] as const) {
  test(`${title}验证码行可用，发送后禁用倒计时不改变按钮宽度`, async ({ page }) => {
    await page.goto(`/#/pages/auth/${route}/index`)
    const { button, width } = await checkCodeRow(page)
    await page.locator('input[placeholder="请输入手机号"]').fill('13800138000')
    const request = page.waitForRequest(req => new URL(req.url()).pathname === '/api/v1/auth/captcha' && req.method() === 'POST')
    await button.click()
    expect((await request).postDataJSON()).toEqual({ phone: '13800138000', scene })
    await expect(button).toHaveText(/^\d+s$/)
    await expect(button).toHaveAttribute('disabled')
    expect(Math.abs((await button.boundingBox())!.width - width)).toBeLessThanOrEqual(1)
    // 等待至少一次真实倒计时更新，确保 disabled 和数字文案没有重新撑大按钮。
    await expect(button).toHaveText(/^[1-5]\ds$/)
    expect(Math.abs((await button.boundingBox())!.width - width)).toBeLessThanOrEqual(1)
  })
}
