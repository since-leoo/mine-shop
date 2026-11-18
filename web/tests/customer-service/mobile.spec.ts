import { test, expect } from '@playwright/test'
test('真实小程序页面：v2接待、AI标识、主动转人工、消息状态和商品卡', async ({ page }) => {
  await page.addInitScript(() => {
    class MiniSocket {
      opened: any; received: any; closed: any;
      constructor() { (window as any).__miniSocket = this }
      onOpen(fn: any) { this.opened = fn; setTimeout(fn, 0) }
      onMessage(fn: any) { this.received = fn }
      onClose(fn: any) { this.closed = fn }
      onError() {}
      send({ data }: any) { const frame = JSON.parse(data); if (frame.event === 'auth') setTimeout(() => this.emit('authenticated'), 0); if (frame.event === 'message:send') setTimeout(() => this.emit('message:created', { id: 4, sequence: 4, sender_type: 'member', message_type: frame.payload.message_type, content_json: { url: frame.payload.url }, client_message_id: frame.payload.client_message_id }, { conversation_no: 'M1' }), 0) }
      close() { this.closed?.() }
      emit(event: string, payload = {}, context = {}) { this.received?.({ data: JSON.stringify({ event, payload, ...context }) }) }
    }
    ;(window as any).__MiniSocket = MiniSocket
  })
  let conversation: any = { conversation_no: 'M1', reception_state: 'ai_active', status: 'waiting' }; let opening: any; let transferred = false; const sends: any[] = []
  await page.route('**/api/v1/customer-service/**', async route => {
    const path = new URL(route.request().url()).pathname; let data: any = []
    if (path.endsWith('/config')) data = { enabled: true, allow_member_image: true, gateway_url: 'wss://example.com', socket_path: '/customer-service' }
    else if (path.endsWith('/socket-ticket')) data = { ticket: 'one-time' }
    else if (path.endsWith('/conversations')) { opening = route.request().postDataJSON(); data = conversation }
    else if (path.endsWith('/categories')) data = [{ id: 1, parent_id: 0, name: '售后' }, { id: 2, parent_id: 1, name: '退款' }]
    else if (path.endsWith('/request-human')) { transferred = true; conversation = { ...conversation, reception_state: 'human_waiting' }; data = conversation }
    else if (path.endsWith('/category')) { conversation = { ...conversation, category_id: route.request().postDataJSON().category_id }; data = conversation }
    else if (path.endsWith('/messages') && route.request().method() === 'POST') { const payload = route.request().postDataJSON(); sends.push(payload); if (sends.length === 1) return route.fulfill({ status: 503, json: { message: '连接断开' } }); data = { id: 3, sequence: 3, sender_type: 'member', message_type: 'text', content_json: { text: payload.text }, client_message_id: payload.client_message_id } }
    else if (path.endsWith('/messages')) data = Number(new URL(route.request().url()).searchParams.get('after_sequence')) > 0 ? [] : [{ id: 1, sequence: 1, sender_type: 'ai', message_type: 'text', content_json: { text: '您好，请描述问题' } }, { id: 2, sequence: 2, sender_type: 'member', message_type: 'product', content_json: { product_id: 7, title: '测试商品', image: 'https://images.example.com/product.jpg' } }]
    else data = conversation
    return route.fulfill({ json: { data } })
  })
  await page.goto('/?page=mobile'); await expect(page.locator('.customer-service__title')).toHaveText('AI 客服'); expect(opening.protocol_version).toBe(2); await expect(page.getByText('您好，请描述问题')).toBeVisible()
  await page.getByText('查看商品 ›').click(); expect(await page.evaluate(() => (window as any).__navigation)).toBe('/pages/goods/details/index?id=7')
  await page.getByRole('combobox', { name: '问题类目' }).selectOption('1'); await expect(page.getByText('问题类目：售后 / 退款 ▾')).toBeVisible()
  await page.getByText('＋', { exact: true }).click(); await page.getByText('相册', { exact: true }).click(); await expect(page.locator('img[src="https://images.example.com/member.jpg"]')).toHaveCount(1)
  await page.getByRole('button', { name: '转人工客服' }).click(); await expect.poll(() => transferred).toBe(true); await expect(page.locator('.customer-service__title')).toHaveText('在线客服'); await expect(page.getByText('已转人工，正在排队').first()).toBeVisible()
  await page.getByPlaceholder('输入消息…').fill('请帮我退款'); await page.getByRole('button', { name: '发送', exact: true }).click(); await page.getByText('发送未确认，点击重试').click(); await expect.poll(() => sends.length).toBe(2); expect(sends[0].client_message_id).toBe(sends[1].client_message_id); await expect(page.getByText('请帮我退款', { exact: true })).toHaveCount(1); await expect(page.getByText('发送未确认，点击重试')).toHaveCount(0)
})
