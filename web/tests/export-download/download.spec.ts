import { readFile } from 'node:fs/promises'
import { expect, test } from '@playwright/test'

const endpoint = '**/admin/export/tasks/*/download'
const csv = 'id,name\n7,测试订单\n'

for (const source of ['web', 'plugin']) {
  test(`${source}: 下载携带登录令牌并保存真实二进制文件`, async ({ page }) => {
    await page.route(endpoint, async route => {
      expect(route.request().headers().authorization).toBe('Bearer initial-token')
      await route.fulfill({
        contentType: 'text/csv',
        headers: { 'Content-Disposition': "attachment; filename=export.csv; filename*=UTF-8''%E8%AE%A2%E5%8D%95.csv" },
        body: csv,
      })
    })
    await page.goto('/')
    const downloaded = page.waitForEvent('download')
    await page.getByTestId(`${source}-download`).click()
    const file = await downloaded
    expect(file.suggestedFilename()).toBe('订单.csv')
    expect(await readFile((await file.path())!, 'utf8')).toBe(csv)
    await expect(page.getByTestId(`${source}-status`)).toHaveText('success')
  })

  for (const status of [200, 401, 403]) {
    for (const code of status === 200 ? [401, 403] : [status]) {
      test(`${source}: HTTP ${status} / 业务 ${code} JSON拒绝下载并提示`, async ({ page }) => {
        const downloads: string[] = []
        page.on('download', file => downloads.push(file.suggestedFilename()))
        const message = code === 401 ? '登录已过期' : '数据权限已变更，请重新导出'
        await page.route(endpoint, route => route.fulfill({
          status,
          contentType: 'application/json; charset=utf-8',
          body: JSON.stringify({ code, message }),
        }))
        await page.goto('/')
        await page.getByTestId(`${source}-download`).click()
        await expect(page.getByTestId(`${source}-status`)).toHaveText('failed')
        await expect(page.locator('.el-notification')).toContainText(message)
        await expect(page.getByTestId('logout')).toHaveText(code === 401 ? '1' : '0')
        expect(downloads).toEqual([])
        // 再次401不能遗留到永远等待刷新的队列。
        if (code === 401) {
          await page.getByTestId(`${source}-download`).click()
          await expect(page.getByTestId(`${source}-status`)).toHaveText('failed')
        }
      })
    }
  }

  for (const body of ['{invalid', '{"code":200,"data":{"url":"https://old.example/file.csv"}}']) {
    test(`${source}: 非文件JSON ${body.slice(0, 12)} 不悬空或误保存`, async ({ page }) => {
      const downloads: string[] = []
      page.on('download', file => downloads.push(file.suggestedFilename()))
      await page.route(endpoint, route => route.fulfill({ contentType: 'application/json', body }))
      await page.goto('/')
      await page.getByTestId(`${source}-download`).click()
      await expect(page.getByTestId(`${source}-status`)).toHaveText('failed')
      await expect(page.locator('.el-notification')).toContainText('下载响应格式错误')
      expect(downloads).toEqual([])
    })
  }

  test(`${source}: 网络失败不保存文件`, async ({ page }) => {
    const downloads: string[] = []
    page.on('download', file => downloads.push(file.suggestedFilename()))
    await page.route(endpoint, route => route.abort('connectionfailed'))
    await page.goto('/')
    await page.getByTestId(`${source}-download`).click()
    await expect(page.getByTestId(`${source}-status`)).toHaveText('failed')
    await expect(page.locator('.el-notification')).toContainText('下载失败，请重试')
    expect(downloads).toEqual([])
  })
}

test('HTTP401刷新成功后重新鉴权下载', async ({ page }) => {
  let calls = 0
  await page.route(endpoint, async route => {
    if (++calls === 1) {
      await route.fulfill({ status: 401, contentType: 'application/json; charset=utf-8', body: '{"code":401}' })
    }
    else {
      expect(route.request().headers().authorization).toBe('Bearer refreshed-token')
      await route.fulfill({ contentType: 'text/csv', headers: { 'Content-Disposition': 'attachment; filename="orders.csv"' }, body: csv })
    }
  })
  await page.route('**/admin/passport/refresh', async route => {
    expect(route.request().headers().authorization).toBe('Bearer refresh-token')
    await route.fulfill({ json: { code: 200, data: { access_token: 'refreshed-token', refresh_token: 'next-token', expire_at: 3600 } } })
  })
  await page.goto('/?refresh')
  const downloaded = page.waitForEvent('download')
  await page.getByTestId('web-download').click()
  expect((await downloaded).suggestedFilename()).toBe('orders.csv')
  await expect(page.getByTestId('web-status')).toHaveText('success')
  await expect(page.getByTestId('logout')).toHaveText('0')
  expect(calls).toBe(2)
})

test('并发401刷新失败时所有请求都结束，不保存错误文件', async ({ page }) => {
  const downloads: string[] = []
  page.on('download', file => downloads.push(file.suggestedFilename()))
  let rejectRefresh: (() => void) | undefined
  let downloadRequests = 0
  await page.route(endpoint, async route => {
    downloadRequests++
    await route.fulfill({ status: 401, json: { code: 401, message: '登录已过期' } })
  })
  await page.route('**/admin/passport/refresh', async route => {
    await new Promise<void>(resolve => rejectRefresh = resolve)
    await route.fulfill({ status: 401, json: { code: 401 } })
  })
  await page.goto('/?refresh')
  await page.getByTestId('web-download').click()
  await expect.poll(() => Boolean(rejectRefresh)).toBe(true)
  await page.getByTestId('plugin-download').click()
  await expect.poll(() => downloadRequests).toBe(2)
  await expect(page.getByTestId('plugin-status')).toHaveText('pending')
  rejectRefresh!()
  await expect(page.getByTestId('web-status')).toHaveText('failed')
  await expect(page.getByTestId('plugin-status')).toHaveText('failed')
  await expect(page.getByTestId('logout')).toHaveText('1')
  expect(downloads).toEqual([])
})

test('刷新后的令牌仍被拒绝时停止重试并退出登录', async ({ page }) => {
  let refreshes = 0
  await page.route(endpoint, route => route.fulfill({ status: 401, json: { code: 401 } }))
  await page.route('**/admin/passport/refresh', async route => {
    refreshes++
    await route.fulfill({ json: { code: 200, data: { access_token: 'still-invalid', refresh_token: 'next-token', expire_at: 3600 } } })
  })
  await page.goto('/?refresh')
  await page.getByTestId('web-download').click()
  await expect(page.getByTestId('web-status')).toHaveText('failed')
  await expect(page.getByTestId('logout')).toHaveText('1')
  expect(refreshes).toBe(1)
})
