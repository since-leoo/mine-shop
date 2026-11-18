import { expect, test } from '@playwright/test'
import { writeFile } from 'node:fs/promises'
import { goodsApi } from './goods-cart.fixture'
import { expectHealthy } from './helpers'

test('确认订单转场中可见返回按钮立即点击不会白屏', async ({ page }, testInfo) => {
  const api = await goodsApi(page)
  const observations: unknown[] = []
  await page.addInitScript(() => document.addEventListener('click', event => {
    const button = (event.target as Element)?.closest('.order-confirm .page-nav__back')
    if (button) (window as any).__visibleBackClick = { className: button.closest('.taro_page')?.className, scrollLeft: document.getElementById('app')?.scrollLeft }
  }, true))
  try {
    for (let attempt = 0; attempt < 6; attempt += 1) {
      await page.goto('/?navigationAttempt=' + attempt + '#/pages/goods/details/index?spuId=102&orderType=group_buy&groupBuyId=7')
      await page.locator('.group-teams__join-btn:visible').click()
      await page.locator('.spec-popup__confirm-btn:visible').click()
      // 按钮必须已经进入真实视区且没有遮挡，避免 locator 自动滚动屏幕外转场页。
      for (let hitAttempt = 0; hitAttempt < 5; hitAttempt += 1) {
        const target = await page.waitForFunction(() => {
          const button = document.querySelector('.order-confirm .page-nav__back')
          if (!button) return null
          const rect = button.getBoundingClientRect()
          const x = rect.left + rect.width / 2
          const y = rect.top + rect.height / 2
          if (rect.left < 0 || rect.right > innerWidth || rect.top < 0 || rect.bottom > innerHeight) return null
          if (!button.contains(document.elementFromPoint(x, y))) return null
          return { x, y, left: rect.left, top: rect.top, scrollLeft: document.getElementById('app')?.scrollLeft,
            pageClass: button.closest('.taro_page')?.className }
        }, undefined, { polling: 'raf' })
        const point = await target.jsonValue()
        observations.push(point)
        await page.mouse.click(point.x, point.y)
        const accepted = await page.evaluate(() => (window as any).__visibleBackClick)
        if (accepted) { observations.push({ accepted }); break }
      }
      await expect(page).toHaveURL(/goods\/details\/index\?spuId=102/)
      await expect(page.locator('.goods-group-bottom__origin:visible')).toBeVisible()
      // 等待曾导致白屏的旧定时器结束，确保返回页不会再次被隐藏。
      await page.waitForTimeout(450)
      await expect(page.locator('.goods-group-bottom__origin:visible')).toBeVisible()
    }
    expectHealthy(api)
  } finally {
    const output = testInfo.outputPath('visible-back-clicks.json')
    await writeFile(output, JSON.stringify(observations, null, 2))
    await testInfo.attach('visible-back-clicks', { path: output, contentType: 'application/json' })
  }
})
