import { expect, test } from '@playwright/test'
import { expectHealthy, setupPage } from './helpers'

for (const entry of ['direct', 'cart', 'cached-cart', 'cached-center', 'expired-cart'] as const) {
  test(`登录后正文切换且目的页面可点击：${entry}`, async ({ page }) => {
    let cartExpired = entry === 'expired-cart'
    const api = await setupPage(page, call => {
      if (call.path === '/api/v1/auth/register/protocols') return { data: {} }
      if (call.path === '/api/v1/login/h5Password') return { data: { token: 'auth-navigation-test', member: { id: 1, nickname: '导航测试会员', phone: '13800138000' } } }
      if (call.path.includes('/diy/')) return { data: { page: null, components: [] } }
      if (call.path === '/api/v1/cart') {
        if (cartExpired) { cartExpired = false; return { code: 401, message: '登录已过期' } }
        return { data: { storeGoods: [] } }
      }
      if (call.path === '/api/v1/products') return { data: { list: [], total: 0 } }
      if (call.path === '/api/v1/home') return { data: {} }
      if (call.path === '/api/v1/categories') return { data: [] }
      if (call.path === '/api/v1/seckill/sessions') return { data: [] }
      if (call.path === '/api/v1/member/center') return { data: { userInfo: { nickName: '导航测试会员', phoneNumber: '13800138000' }, countsData: [], orderTagInfos: [] } }
      if (call.path === '/api/v1/member/profile') return { data: { nickname: '导航测试会员', phone: '13800138000' } }
      return undefined
    }, { guest: entry !== 'expired-cart' })
    const destination = entry === 'direct' || entry === 'cached-center' ? 'usercenter' : 'cart'
    await page.goto('/#/pages/' + (entry === 'direct' ? 'auth/login' : destination) + '/index')
    const login = page.locator('.auth-page--login:visible')
    await expect(login.last()).toBeVisible()
    if (entry.startsWith('cached-')) {
      await login.getByText('游客先逛逛', { exact: true }).click()
      await expect(page.locator('.home:visible')).toBeVisible()
      await expect(page.locator('taro-tabbar:visible')).toHaveCount(0)
      await page.locator('.h5-tab-bar:visible .h5-tab-bar__item').filter({ hasText: destination === 'cart' ? '购物车' : '我的' }).click()
      await expect(login.last()).toBeVisible()
    }
    await login.last().locator('input[placeholder="请输入手机号"]').fill('13800138000')
    await login.last().locator('input[placeholder="请输入 6-20 位密码"]').fill('test-password')
    await login.last().locator('.auth-button--primary').click()
    await expect(page).toHaveURL(new RegExp('/pages/' + destination + '/index$'))
    await expect(login).toHaveCount(0)
    const screen = page.locator(destination === 'cart' ? '.cart-page:visible' : '.usercenter:visible')
    await expect(screen).toBeVisible()
    if (destination === 'cart') {
      await screen.locator('.cart-empty__btn').click()
      await expect(page.locator('.home:visible')).toBeVisible()
    } else {
      await screen.locator('.usercenter__menu-item').filter({ hasText: '资料设置' }).click()
      await expect(page.locator('.person-info:visible')).toBeVisible()
    }
    expectHealthy(api)
  })
}
