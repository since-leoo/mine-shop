import { test, expect } from '@playwright/test'
test('真实工作台：认证心跳、分页补拉、AI接管、幂等重试和转接后禁止回复', async ({ page }) => {
  await page.clock.install()
  await page.addInitScript(() => {
    class FakeSocket {
      static OPEN = 1; static CLOSED = 3; readyState = 1; onopen: any; onmessage: any; onclose: any; onerror: any;
      constructor() { (window as any).__socket = this; (window as any).__frames = []; setTimeout(() => this.onopen?.(), 0) }
      send(data: string) { (window as any).__frames.push(JSON.parse(data)) }
      close() { this.readyState = 3; this.onclose?.() }
      emit(event: string, payload = {}, context = {}) { this.onmessage?.({ data: JSON.stringify({ event, payload, ...context }) }) }
    }
    ;(window as any).WebSocket = FakeSocket
  })
  let conversation: any = { id: 1, no: 'C1', member_name: '测试客户', status: 'queued', reception_state: 'ai_active', assigned_agent_id: null }
  const cursors: number[] = [], sends: any[] = []
  await page.route('**/admin/customer-service/**', async route => {
    const url = new URL(route.request().url()), path = url.pathname; let data: any = []
    if (path.endsWith('/socket-ticket')) data = { ticket: 'once' }
    else if (path.endsWith('/queue')) data = conversation.status === 'queued' ? [conversation] : []
    else if (path.endsWith('/conversations')) data = { list: [conversation], total: 1 }
    else if (path.endsWith('/agents/me')) data = { id: 1, name: '坐席甲', status: 'online', enabled: true, max_conversations: 5 }
    else if (path.endsWith('/agents')) data = [{ id: 1, name: '坐席甲', status: 'online', enabled: true }, { id: 2, name: '坐席乙', status: 'online', enabled: true }]
    else if (path.endsWith('/statistics')) data = { queued_count: 1, active_count: 0, today_closed_count: 0 }
    else if (path.endsWith('/messages') && route.request().method() === 'GET') {
      const after = Number(url.searchParams.get('after_sequence')); cursors.push(after)
      data = Array.from({ length: Math.max(0, Math.min(100, 120 - after)) }, (_, i) => ({ id: after + i + 1, sequence: after + i + 1, sender_type: 'member', type: 'text', content: `历史消息${after + i + 1}`, created_at: '' }))
    } else if (path.endsWith('/messages')) {
      const payload = route.request().postDataJSON(); sends.push(payload)
      if (sends.length === 1) return route.fulfill({ status: 503, json: { message: '暂时断网' } })
      data = { id: 121, sequence: 121, sender_type: 'agent', type: 'text', content: payload.content, client_message_id: payload.client_message_id, created_at: '' }
    } else if (path.endsWith('/takeover')) { conversation = { ...conversation, status: 'active', reception_state: 'human_active', assigned_agent_id: 1, agent_name: '坐席甲' }; data = conversation }
    else if (path.endsWith('/transfer')) { conversation = { ...conversation, assigned_agent_id: 2, agent_name: '坐席乙' }; data = {} }
    return route.fulfill({ json: { data } })
  })
  await page.goto('/?page=workbench'); await page.clock.runFor(100)
  await expect(page.locator('.connection')).toContainText('正在认证'); expect(cursors.slice(0, 2)).toEqual([0, 100]); await expect(page.locator('.message-row')).toHaveCount(120)
  await page.evaluate(() => (window as any).__socket.emit('authenticated')); await expect(page.locator('.connection')).toContainText('已连接'); await page.clock.runFor(25000)
  expect(await page.evaluate(() => (window as any).__frames.some((f: any) => f.event === 'ping'))).toBe(true)
  await expect(page.locator('.conversation-meta')).toContainText('AI 接待中'); await page.getByRole('button', { name: /^排队/ }).click(); await expect(page.locator('.conversation-item')).toHaveCount(0); await page.getByRole('button', { name: 'AI', exact: true }).click(); await expect(page.locator('.conversation-item')).toHaveCount(1); await page.getByRole('button', { name: '全部', exact: true }).click()
  await expect(page.getByPlaceholder('输入回复内容，按 Enter 发送，Shift + Enter 换行')).toBeDisabled()
  await page.getByRole('button', { name: '接管 AI / 留言' }).click(); const draft = page.getByPlaceholder('输入回复内容，按 Enter 发送，Shift + Enter 换行'); await expect(draft).toBeEnabled(); await draft.fill('我来帮您处理'); await page.getByRole('button', { name: '发送 Enter' }).click()
  await page.getByRole('button', { name: '未确认，点击重试' }).click(); await expect.poll(() => sends.length).toBe(2); expect(sends[0].client_message_id).toBe(sends[1].client_message_id)
  await expect(page.locator('.message-content p').filter({ hasText: '我来帮您处理' })).toHaveCount(1)
  await page.evaluate((id: string) => (window as any).__socket.emit('message:created', { id: 121, sequence: 121, sender_type: 'agent', message_type: 'text', content_json: { text: '我来帮您处理' }, client_message_id: id }, { conversation_no: 'C1' }), sends[0].client_message_id)
  await expect(page.locator('.message-content p').filter({ hasText: '我来帮您处理' })).toHaveCount(1)
  await page.getByRole('button', { name: '转接', exact: true }).click(); await page.getByRole('button').filter({ hasText: '坐席乙' }).click(); await expect(draft).toBeDisabled()
})
