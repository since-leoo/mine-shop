import { test, expect, type Page } from '@playwright/test'
import { setupPage, expectHealthy, expectWithinViewport, capturePage, productImage, type ApiCall, type ApiReply } from './helpers'
const order = (status = 'shipped', orderNo = 'ORDER-20261013-000000000000000001') => ({ id: 7, orderNo, status, payStatus: status === 'pending' ? 'pending' : 'paid', totalAmount: 19999998, payAmount: 19999998, shippingFee: 0, discountAmount: 0, couponAmount: 0, createdAt: '2026-10-13 10:00:00', paidAt: status === 'pending' ? '' : '2026-10-13 10:01:00', address: { name: '这是特别长的收货人姓名测试', phone: '13800138000', fullAddress: '浙江省杭州市西湖区文三路非常长的地址名称测试一二三四五六七八九号' }, items: [{ id: 71, productId: 21, skuId: 31, productName: '秋日限量手工保温杯长商品标题完整展示测试商品', skuName: '特大号 / 暖杏色 / 礼盒装', productImage, unitPrice: 9999999, quantity: 2 }], buttonVOs: status === 'shipped' ? [{ type: 8 }, { type: 3 }] : status === 'pending' ? [{ type: 2 }, { type: 1 }] : [] })
const afterSale = (id = 8, status = 'waiting_buyer_return', type = 'return_refund') => ({ id, afterSaleNo: `AFTER-SALE-20261013-00000000000000${id}`, orderId: 7, orderNo: 'ORDER-7', orderItemId: 71, status, type, applyAmount: 9999, refundAmount: status === 'completed' ? 9999 : 0, quantity: 1, reason: '商品与描述不符', description: '这是一段需要自然换行的问题描述'.repeat(3), product: { productId: 21, skuId: 31, productName: '秋日手工保温杯长商品标题测试'.repeat(2), skuName: '暖杏色 / 礼盒装', productImage }, reshipLogisticsNo: type === 'exchange' ? 'SF123456789012345678901234567890' : '', reshipLogisticsCompany: '顺丰速运', createdAt: '2026-10-13 10:00:00', updatedAt: '2026-10-13 12:00:00' })
const list = (items: any[], total = items.length) => ({ list: items, pagination: { total, currentPage: 1, perPage: 10 } })
function defaults(call: ApiCall): ApiReply | undefined {
  if (call.path === '/api/v1/order/statistics') return { data: {} }
  if (call.path === '/api/v1/order/list') return { data: list([order()]) }
  if (call.path === '/api/v1/after-sales' && call.method === 'GET') return { data: list([]) }
  if (call.path.startsWith('/api/v1/order/detail/')) return { data: order() }
  if (call.path === '/api/v1/products') return { data: list([]) }
  if (call.path === '/api/v1/customer-service/config') return { data: { enabled: false, offlineMessage: '测试客服暂未开启' } }
  if (/^\/api\/v1\/after-sales\/\d+$/.test(call.path)) return { data: afterSale(Number(call.path.split('/').at(-1))) }
}
const go = (page: Page, name: string, query = '') => page.goto(`/#/pages/order/${name}/index${query}`)
const width = (page: Page) => page.viewportSize()!.width
const deferred = () => { let resolve!: () => void; const promise = new Promise<void>(done => { resolve = done }); return { promise, resolve } }
// 使用生产页面、服务转换和Taro导航，仅模拟网络与登录，不发起真实交易。
test('订单筛选隔离慢响应，确认收货不再被售后按钮截掉', async ({ page }) => {
  const gate = deferred()
  const api = await setupPage(page, async call => {
    if (call.path === '/api/v1/order/list' && call.query.get('status') === 'pending') { await gate.promise; return { data: list([order('pending', 'OLD-PENDING')]) } }
    if (call.path === '/api/v1/order/confirm-receipt') return { data: {} }
    return defaults(call)
  })
  await go(page, 'order-list')
  await expect(page.locator('.order-card')).toHaveCount(1)
  await page.locator('.order-tabs__item').filter({ hasText: '待付款' }).click()
  await expect.poll(() => api.requests.some(r => r.query.get('status') === 'pending')).toBe(true)
  await page.locator('.order-tabs__item').filter({ hasText: '待收货' }).click()
  await expect(page.locator('.order-card__action-btn').filter({ hasText: '确认收货' })).toBeVisible()
  gate.resolve()
  await expect(page.getByText('OLD-PENDING', { exact: false })).toHaveCount(0)
  await expectWithinViewport(page.locator('.order-card, .order-card__actions, .order-card__goods'), width(page))
  await page.locator('.order-card__action-btn').filter({ hasText: '确认收货' }).click()
  await page.getByText('确定', { exact: true }).click()
  await expect.poll(() => api.requests.filter(r => r.path === '/api/v1/order/confirm-receipt').length).toBe(1)
  expect(api.requests.find(r => r.path === '/api/v1/order/confirm-receipt')?.body.order_no).toContain('ORDER-')
  if (width(page) === 375) { await expect(page.getByText('已确认收货', { exact: true })).not.toBeVisible(); await capturePage(page, 'h5-order-list-after-375.png') }
  expectHealthy(api)
})
test('订单详情缺编号可恢复，待支付不虚构配送，长内容不溢出且客服可进入', async ({ page }) => {
  const api = await setupPage(page, call => call.path.startsWith('/api/v1/order/detail/') ? { data: order('pending') } : defaults(call))
  await go(page, 'order-detail')
  await expect(page.getByText('缺少订单编号，请从订单列表进入')).toBeVisible()
  expect(api.requests.some(r => r.path.includes('/order/detail/'))).toBe(false)
  await go(page, 'order-detail', '?orderNo=ORDER-7')
  await expect(page.locator('.order-detail__status')).toHaveText('待付款')
  await expect(page.locator('.order-detail__logistics')).toHaveCount(0)
  await expect(page.getByText(/预计.*送达|倒计时结束/)).toHaveCount(0)
  await expectWithinViewport(page.locator('.order-detail__goods-card, .order-detail__info-card, .order-detail__bottom-bar'), width(page))
  if (width(page) === 375) await capturePage(page, 'h5-order-detail-after-375.png')
  await page.locator('.order-detail__service-row').click()
  await expect(page).toHaveURL(/pages\/customer-service\/index/)
  expectHealthy(api)
})
test('取消订单失败可重试，已取消不会误显示已退款', async ({ page }) => {
  let attempts = 0, cancelled = false
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/order/list') return { data: list([order(cancelled ? 'cancelled' : 'pending')]) }
    if (call.path === '/api/v1/order/cancel') { attempts++; if (attempts === 1) return { code: 422, message: '暂时无法取消' }; cancelled = true; return { data: {} } }
    return defaults(call)
  })
  await go(page, 'order-list')
  for (let i = 0; i < 2; i++) {
    await page.locator('.order-card__action-btn').filter({ hasText: '取消订单' }).click()
    await page.getByText('确定', { exact: true }).click()
    await expect.poll(() => attempts).toBe(i + 1)
    if (i === 0) await expect(page.getByText('暂时无法取消', { exact: true })).toBeVisible()
  }
  await expect(page.locator('.order-card__status')).toHaveText('已取消')
  await expect(page.locator('.order-card__action-btn').filter({ hasText: '查看售后' })).toHaveCount(0)
  expectHealthy(api)
})
test('售后申请真实金额数量提交且请求未完成时防连击', async ({ page }) => {
  const gate = deferred()
  const api = await setupPage(page, async call => {
    if (call.path === '/api/v1/after-sales/eligibility') return { data: { canApply: true, orderId: 7, orderItemId: 71, maxQuantity: 2, maxAmount: 19998, types: ['refund_only', 'return_refund', 'exchange'] } }
    if (call.path === '/api/v1/after-sales' && call.method === 'POST') { await gate.promise; return { data: afterSale(8, 'pending_review') } }
    return defaults(call)
  })
  await go(page, 'apply-service', '?orderId=7&orderItemId=71&orderNo=ORDER-7&productName=%E6%9A%96%E6%9D%8F%E4%BF%9D%E6%B8%A9%E6%9D%AF')
  await expect(page.locator('.apply-service__amount-input input')).toHaveValue('199.98')
  await page.locator('.apply-service__stepper-btn').first().click()
  await expect(page.locator('.apply-service__amount-input input')).toHaveValue('99.99')
  const inputBox = await page.locator('.apply-service__amount-input input').boundingBox()
  const amountBox = await page.locator('.apply-service__amount-box').boundingBox()
  expect(Math.abs(inputBox!.y + inputBox!.height / 2 - amountBox!.y - amountBox!.height / 2)).toBeLessThan(2)
  await page.locator('.apply-service__textarea textarea').fill('杯盖无法扣紧，需要退款')
  await expectWithinViewport(page.locator('.apply-service__card, .apply-service__submit'), width(page))
  if (width(page) === 375) { await page.locator('.apply-service .page-nav').scrollIntoViewIfNeeded(); await capturePage(page, 'h5-apply-service-after-375.png') }
  await page.locator('.apply-service__submit').click({ clickCount: 2 })
  await expect.poll(() => api.requests.filter(r => r.path === '/api/v1/after-sales' && r.method === 'POST').length).toBe(1)
  expect(api.requests.find(r => r.path === '/api/v1/after-sales' && r.method === 'POST')!.body).toMatchObject({ order_id: 7, order_item_id: 71, type: 'refund_only', apply_amount: 9999, quantity: 1, description: '杯盖无法扣紧，需要退款' })
  gate.resolve()
  await expect(page).toHaveURL(/after-service-detail\/index\?id=8/)
  expectHealthy(api)
})
test('新旧退货物流入口校验编号、调用真实接口且防重复提交', async ({ page }) => {
  const gate = deferred()
  const api = await setupPage(page, async call => { if (call.path === '/api/v1/after-sales/8/return-shipment') { await gate.promise; return { data: {} } } return defaults(call) })
  await go(page, 'fill-tracking-no')
  await expect(page.getByText('缺少有效售后编号，请从售后详情进入')).toBeVisible()
  await expect(page.locator('.fill-return-shipping__submit')).toHaveCount(0)
  await go(page, 'fill-tracking-no', '?afterSaleId=8')
  await page.locator('input[placeholder="请输入物流公司"]').fill('顺丰速运')
  await page.locator('input[placeholder="请输入物流单号"]').fill('SF123456789012345678901234567890')
  await expectWithinViewport(page.locator('.fill-return-shipping__card, .fill-return-shipping__submit'), width(page))
  await page.locator('.fill-return-shipping__submit').click({ clickCount: 2 })
  await expect.poll(() => api.requests.filter(r => r.path.endsWith('/return-shipment')).length).toBe(1)
  expect(api.requests.find(r => r.path.endsWith('/return-shipment'))?.body).toEqual({ logistics_company: '顺丰速运', logistics_no: 'SF123456789012345678901234567890' })
  gate.resolve()
  await expect(page).toHaveURL(/after-service-detail\/index\?id=8/)
  await page.locator('.after-service-detail__btn').filter({ hasText: '填写退货物流' }).click()
  await expect(page).toHaveURL(/fill-return-shipping\/index\?id=8/)
  expectHealthy(api)
})
test('售后列表真实分页，详情金额和换货时间线准确，客服可进入', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path === '/api/v1/after-sales') return { data: list(Number(call.query.get('page')) === 2 ? [afterSale(11)] : Array.from({ length: 10 }, (_, i) => afterSale(i + 1)), 11) }
    if (call.path === '/api/v1/after-sales/11') return { data: afterSale(11, 'completed', 'exchange') }
    return defaults(call)
  })
  await go(page, 'after-service-list')
  await expect(page.locator('.after-service-list__card')).toHaveCount(10)
  await page.getByText('加载更多', { exact: true }).click()
  await expect(page.locator('.after-service-list__card')).toHaveCount(11)
  await expectWithinViewport(page.locator('.after-service-list__card').last(), width(page))
  await page.locator('.after-service-list__card').last().click()
  await expect(page.locator('.after-service-detail__status')).toHaveText('已完成')
  await expect(page.locator('.after-service-detail__timeline-title').filter({ hasText: '退款已完成' })).toHaveCount(0)
  await expect(page.locator('.after-service-detail__row').filter({ hasText: '申请金额' })).toContainText('¥99.99')
  await expectWithinViewport(page.locator('.after-service-detail__card'), width(page))
  if (width(page) === 375) await capturePage(page, 'h5-after-service-detail-after-375.png')
  await page.locator('.after-service-detail__helper').click()
  await expect(page).toHaveURL(/pages\/customer-service\/index/)
  expectHealthy(api)
})
test('支付缺编号不报成功、金额来自订单、页面隐藏停止旧轮询', async ({ page }) => {
  const gate = deferred()
  let pendingCalls = 0
  const api = await setupPage(page, async call => {
    if (call.path === '/api/v1/order/detail/PENDING') { pendingCalls++; if (pendingCalls === 1) await gate.promise; return { data: order('pending', 'PENDING') } }
    if (call.path.startsWith('/api/v1/order/detail/')) return { data: { ...order('paid'), payAmount: 9999 } }
    return defaults(call)
  })
  await go(page, 'pay-result')
  await expect(page.locator('.pay-result__reason')).toContainText('缺少订单编号')
  await expect(page.getByText('支付成功', { exact: true })).toHaveCount(0)
  await go(page, 'pay-result', '?orderNo=PAID&totalPaid=9999999')
  await expect(page.locator('.pay-result__title')).toHaveText('支付成功')
  await expect(page.locator('.pay-result__price')).toContainText('99.99')
  await go(page, 'pay-result', '?orderNo=PENDING')
  await expect.poll(() => pendingCalls).toBe(1)
  await page.getByText('查看订单', { exact: true }).click()
  await expect(page).toHaveURL(/order-detail\/index\?orderNo=PENDING/)
  gate.resolve()
  // 第二次是新详情页请求，隐藏的支付页不能再启动第三次轮询。
  await expect.poll(() => pendingCalls).toBe(2)
  await page.waitForTimeout(1800)
  expect(pendingCalls).toBe(2)
  expectHealthy(api)
})
test('发票及旧抬头页本机保存、刷新回填，不伪造申请成功', async ({ page }) => {
  const api = await setupPage(page, defaults)
  await go(page, 'invoice')
  await expect(page.locator('.invoice__notice')).toContainText('不代表已申请开票')
  await page.getByText('公司', { exact: true }).click()
  await page.locator('input[placeholder="请输入发票抬头"]').fill('杭州暖物科技有限公司')
  await page.locator('input[placeholder="请输入纳税人识别号"]').fill('123')
  await page.locator('input[placeholder="请输入邮箱地址"]').fill('finance@example.com')
  await page.locator('.invoice__submit-btn').click()
  await expect(page.getByText('请填写正确的纳税人识别号', { exact: true })).toBeVisible()
  await page.locator('input[placeholder="请输入纳税人识别号"]').fill('91330100MA12345678')
  await page.locator('.invoice__submit-btn').click()
  await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('invoiceData') || '{}').data?.buyerName)).toBe('杭州暖物科技有限公司')
  expect(api.requests.filter(r => r.method === 'POST')).toHaveLength(0)
  await page.reload()
  await expect(page.locator('input[placeholder="请输入发票抬头"]')).toHaveValue('杭州暖物科技有限公司')
  await go(page, 'receipt')
  await expect(page.locator('input[placeholder="请输入纳税人识别号"]')).toHaveValue('91330100MA12345678')
  await expectWithinViewport(page.locator('.invoice__card, .invoice__submit-btn'), width(page))
  await expect(page.getByText('发票申请已提交', { exact: true })).toHaveCount(0)
  expectHealthy(api)
})
test('物流缺数据有说明，接口失败可重试且长单号与轨迹不溢出', async ({ page }) => {
  let attempts = 0
  const api = await setupPage(page, call => {
    if (call.path.startsWith('/api/v1/order/logistics/')) { attempts++; if (attempts === 1) return { code: 500, message: '物流服务暂不可用' }; return { data: { companyName: '顺丰速运', trackingNo: 'SF1234567890123456789012345678901234567890', traces: [{ status: '运输中', time: '2026-10-13 12:00:00', context: '快件已到达浙江省杭州市西湖区转运中心，正在等待下一段运输安排' }] } } }
    return defaults(call)
  })
  await go(page, 'delivery-detail')
  await expect(page.getByText('暂无物流轨迹，请稍后再查看')).toBeVisible()
  await go(page, 'delivery-detail', '?orderNo=ORDER-7')
  await expect(page.getByText('物流加载失败', { exact: true })).toBeVisible()
  await page.getByText('重新加载', { exact: true }).click()
  await expect(page.locator('.delivery-node')).toHaveCount(1)
  await expectWithinViewport(page.locator('.delivery-detail__info-card, .delivery-detail__timeline-card'), width(page))
  expectHealthy(api)
})

test('售后筛选慢响应不覆盖新筛选，退货接口拒绝时保留表单并允许重试', async ({ page }) => {
  const gate = deferred()
  let shipments = 0
  const api = await setupPage(page, async call => {
    if (call.path === '/api/v1/after-sales' && call.query.get('status') === 'pending_review') { await gate.promise; return { data: list([afterSale(99, 'pending_review')]) } }
    if (call.path === '/api/v1/after-sales') return { data: list([afterSale(8, call.query.get('status') === 'refunding' ? 'refunding' : 'waiting_buyer_return')]) }
    if (call.path.endsWith('/return-shipment')) { shipments++; return shipments === 1 ? { code: 422, message: '物流单号校验失败' } : { data: {} } }
    return defaults(call)
  })
  await go(page, 'after-service-list')
  await page.locator('.after-service-list__tab').filter({ hasText: '待审核' }).click()
  await expect.poll(() => api.requests.some(r => r.query.get('status') === 'pending_review')).toBe(true)
  await page.locator('.after-service-list__tab').filter({ hasText: '退款中' }).click()
  await expect(page.locator('.after-service-list__status')).toHaveText('退款中')
  gate.resolve()
  await expect(page.locator('.after-service-list__no')).not.toContainText('99')
  await go(page, 'fill-return-shipping', '?id=8')
  await page.locator('input[placeholder="请输入物流公司"]').fill('顺丰速运')
  await page.locator('input[placeholder="请输入物流单号"]').fill('SF123456')
  await page.locator('.fill-return-shipping__submit').click()
  await expect(page.getByText('物流单号校验失败', { exact: true })).toBeVisible()
  await expect(page).toHaveURL(/fill-return-shipping\/index\?id=8/)
  await expect(page.locator('input[placeholder="请输入物流单号"]')).toHaveValue('SF123456')
  await page.locator('.fill-return-shipping__submit').click()
  await expect(page).toHaveURL(/after-service-detail\/index\?id=8/)
  expect(shipments).toBe(2)
  expectHealthy(api)
})

test('详情三个底部操作保留，申请售后按钮正确携带订单商品编号', async ({ page }) => {
  const api = await setupPage(page, call => {
    if (call.path.startsWith('/api/v1/order/detail/')) return { data: { ...order(), buttonVOs: [{ type: 8 }, { type: 3 }, { type: 4 }] } }
    if (call.path === '/api/v1/after-sales/eligibility') return { data: { canApply: false, types: [] } }
    return defaults(call)
  })
  await go(page, 'order-detail', '?orderNo=ORDER-7')
  await expect(page.locator('.order-detail__bottom-bar .order-detail__btn')).toHaveCount(3)
  await expectWithinViewport(page.locator('.order-detail__bottom-bar, .order-detail__bottom-bar .order-detail__btn'), width(page))
  await page.locator('.order-detail__bottom-bar .order-detail__btn').filter({ hasText: '申请售后' }).click()
  await expect(page).toHaveURL(/apply-service\/index\?orderId=7&orderItemId=71/)
  await expect(page.getByText('暂不支持申请售后', { exact: true })).toBeVisible()
  expectHealthy(api)
})
