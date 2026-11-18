import { expect, type Locator, type Page } from '@playwright/test'
import { join } from 'node:path'

export interface ApiCall { path: string; query: URLSearchParams; method: string; body: any; headers: Record<string, string> }
export interface ApiReply { data?: any; code?: number; message?: string; status?: number }
export type ApiHandler = (call: ApiCall) => ApiReply | undefined | Promise<ApiReply | undefined>
export const productImage = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160"><rect width="160" height="160" rx="16" fill="#f1ded4"/><path d="M50 54h60l-6 66H56z" fill="#e8836b"/><path d="M65 54V40h30v14" fill="none" stroke="#6b5248" stroke-width="4"/></svg>')

/** 仅模拟网络和登录状态；页面、路由、DIY schema 与点击逻辑使用生产代码。 */
export async function setupPage(page: Page, handler: ApiHandler, options: { guest?: boolean; storage?: Record<string, any> } = {}) {
  const requests: ApiCall[] = []
  const errors: string[] = []
  await page.addInitScript(({ guest, storage }) => {
    if (!guest) {
      localStorage.setItem('accessToken', JSON.stringify({ data: 'site-test-token' }))
      localStorage.setItem('memberProfile', JSON.stringify({ data: { id: 1, nickname: '测试会员', phone: '13800138000', avatar: '' } }))
    }
    Object.entries(storage).forEach(([key, value]) => localStorage.setItem(key, JSON.stringify({ data: value })))
  }, { guest: options.guest ?? false, storage: options.storage ?? {} })
  page.on('pageerror', error => errors.push(error.message))
  await page.route('**/*', async route => {
    const request = route.request()
    const url = new URL(request.url())
    if (request.method() === 'OPTIONS') return route.fulfill({ status: 204, headers: { 'access-control-allow-origin': 'http://127.0.0.1:4003', 'access-control-allow-credentials': 'true', 'access-control-allow-headers': request.headers()['access-control-request-headers'] || 'content-type,authorization', 'access-control-allow-methods': 'GET,POST,PUT,DELETE,OPTIONS' } })
    if (!url.pathname.startsWith('/api/')) {
      if (url.origin === 'http://127.0.0.1:4003') return route.continue()
      return route.abort()
    }
    let body: any = {}
    try { body = request.postDataJSON() ?? {} } catch { body = request.postData() }
    const call = { path: url.pathname, query: url.searchParams, method: request.method(), body, headers: request.headers() }
    requests.push(call)
    const distributionDisabled = call.method === 'GET' && call.path === '/api/v1/distribution/status'
      ? { data: { enabled: false, canInvite: false, inviteCode: '', directCount: 0, directLimit: 0, remainingSlots: 0, h5Enabled: false, miniProgramEnabled: false } }
      : call.method === 'POST' && call.path === '/api/v1/distribution/share' ? { data: { enabled: false } } : undefined
    const reply = requests.length < 100 ? (await handler(call) ?? distributionDisabled) : undefined
    if (!reply) errors.push(`未声明接口或请求循环: ${call.method} ${call.path}`)
    await route.fulfill({ status: reply?.status ?? (reply ? 200 : 501), headers: { 'access-control-allow-origin': 'http://127.0.0.1:4003', 'access-control-allow-credentials': 'true' }, json: { code: reply?.code ?? (reply ? 200 : 501), data: reply?.data, message: reply?.message ?? (reply ? '' : errors.at(-1)) } })
  })
  return { requests, errors }
}

export function expectHealthy(api: { errors: string[] }) { expect(api.errors).toEqual([]) }

export async function expectWithinViewport(locator: Locator, width: number) {
  await expect(async () => {
    const boxes = await locator.evaluateAll(elements => elements.map(element => {
      const rect = element.getBoundingClientRect()
      return { left: rect.left, right: rect.right, width: rect.width, scroll: element.scrollWidth, client: element.clientWidth }
    }))
    expect(boxes.length).toBeGreaterThan(0)
    for (const box of boxes) {
      expect(box.left).toBeGreaterThanOrEqual(-1)
      expect(box.right).toBeLessThanOrEqual(width + 1)
      expect(box.width).toBeGreaterThan(0)
      expect(box.scroll).toBeLessThanOrEqual(box.client + 1)
    }
  }).toPass({ timeout: 5000 })
}

export async function capturePage(page: Page, name: string) {
  if (process.env.H5_SITE_SCREENSHOTS) await page.screenshot({ path: join(process.env.H5_SITE_SCREENSHOTS, name), fullPage: true, animations: 'disabled' })
}
