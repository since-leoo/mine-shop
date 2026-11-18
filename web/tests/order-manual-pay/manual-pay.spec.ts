import type { Page, Route } from '@playwright/test'
import { expect, test } from '@playwright/test'

const pendingOrder = {
  id: 1,
  order_no: 'OFFLINE-001',
  status: 'pending',
  pay_status: 'pending',
  pay_amount: 12550,
  created_at: '2026-10-01 12:00:00',
  expire_time: '2099-01-01 00:00:00',
  items: [],
}

async function mockOrders(page: Page, options: { failure?: string, failureStatus?: number, holdPayment?: boolean, failRefresh?: boolean, holdDetail?: boolean, detailFailure?: string } = {}) {
  let paid = false
  let pendingPaymentRoute: Route | undefined
  let pendingDetailRoute: Route | undefined
  const requests: { method: string, body: unknown }[] = []
  const listCalls: string[] = []
  const statsCalls: string[] = []
  const detailCalls: string[] = []
  const paidOrder = { ...pendingOrder, status: 'paid', pay_status: 'paid', pay_method: 'manual', pay_no: 'MANUAL-001' }
  const detailOrder = () => ({
    ...(paid ? paidOrder : pendingOrder),
    total_amount: 13050,
    shipping_fee: 500,
    discount_amount: 1000,
    seller_remark: `详情备注-${detailCalls.length}`,
    address: {
      name: '测试收货人', phone: '13800000000',
      province: '浙江省', city: '杭州市', district: '西湖区', detail: '测试路1号',
      full_address: detailCalls.length > 1 ? '浙江省杭州市西湖区测试路2号' : '',
    },
  })
  await page.route('**/admin/**', async (route) => {
    const path = new URL(route.request().url()).pathname
    if (path.endsWith('/manual-pay')) {
      requests.push({ method: route.request().method(), body: route.request().postDataJSON() })
      if (options.failure) {
        return route.fulfill({ status: options.failureStatus ?? 200, json: { code: 422, message: options.failure } })
      }
      paid = true
      if (options.holdPayment) {
        pendingPaymentRoute = route
        return
      }
      return route.fulfill({ json: { code: 200, data: paidOrder } })
    }
    if (path === '/admin/order/order/list') {
      listCalls.push(path)
      if (options.failRefresh && listCalls.length > 1) {
        return route.fulfill({ status: 503, json: { message: '订单刷新暂时失败' } })
      }
      return route.fulfill({ json: { code: 200, data: {
        list: [
          paid ? paidOrder : pendingOrder,
          { ...pendingOrder, id: 2, order_no: 'ALREADY-PAID', status: 'paid', pay_status: 'paid' },
          { ...pendingOrder, id: 3, order_no: 'CANCELLED', status: 'cancelled', pay_status: 'cancelled' },
          { ...pendingOrder, id: 4, order_no: 'EXPIRED', expire_time: '2020-01-01 00:00:00' },
          { ...pendingOrder, id: 5, order_no: 'REFUNDED', status: 'completed', pay_status: 'refunded' },
          { ...pendingOrder, id: 6, order_no: 'CANCELLED-UNPAID', status: 'cancelled' },
          { ...pendingOrder, id: 7, order_no: 'PENDING-PAID', pay_status: 'paid' },
        ],
        total: 7,
      } } })
    }
    if (path === '/admin/order/order/stats') {
      statsCalls.push(path)
      return route.fulfill({ json: { code: 200, data: { total: 7, pending: paid ? 1 : 2, paid: paid ? 2 : 1, shipped: 0 } } })
    }
    if (path === '/admin/order/order/1') {
      detailCalls.push(route.request().method())
      if (options.detailFailure) {
        return route.fulfill({ status: 404, json: { code: 404, message: options.detailFailure } })
      }
      if (options.holdDetail) {
        pendingDetailRoute = route
        return
      }
      return route.fulfill({ json: { code: 200, data: detailOrder() } })
    }
    return route.fulfill({ json: { code: 200, data: [] } })
  })
  return {
    requests,
    listCalls,
    statsCalls,
    detailCalls,
    releasePayment: () => pendingPaymentRoute!.fulfill({ json: { code: 200, data: paidOrder } }),
    releaseDetail: () => pendingDetailRoute!.fulfill({ json: { code: 200, data: detailOrder() } }),
  }
}

test('only a valid pending order with permission can be manually marked paid', async ({ page }) => {
  await mockOrders(page)
  await page.goto('/')
  await expect(page.getByRole('button', { name: '人工认款', exact: true })).toHaveCount(1)
  await page.goto('/?permission=denied')
  await expect(page.getByText('OFFLINE-001', { exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: '人工认款', exact: true })).toHaveCount(0)
  await expect(page.getByRole('button', { name: '详情', exact: true })).toHaveCount(0)
})

test('detail loads separately, prevents duplicate requests and refreshes on reopening', async ({ page }) => {
  const api = await mockOrders(page, { holdDetail: true })
  await page.goto('/')
  const row = page.getByRole('row').filter({ hasText: 'OFFLINE-001' })
  const button = row.getByRole('button', { name: '详情', exact: true })
  await expect(button).toBeVisible()
  expect(api.detailCalls).toHaveLength(0)
  await button.click()
  await expect.poll(() => api.detailCalls).toEqual(['GET'])
  await expect(button).toBeDisabled()
  const detail = page.getByRole('dialog', { name: '订单详情', exact: true })
  await expect(detail).not.toBeVisible()
  await api.releaseDetail()
  await expect(detail.getByText('详情备注-1', { exact: true })).toBeVisible()
  await expect(detail.getByText('¥130.50', { exact: true })).toBeVisible()
  await expect(detail.getByText('¥5.00', { exact: true })).toBeVisible()
  await expect(detail.getByText('-¥10.00', { exact: true })).toBeVisible()
  await expect(detail.getByText('浙江省杭州市西湖区测试路1号', { exact: true })).toBeVisible()
  await detail.getByRole('button', { name: '关闭', exact: true }).click()
  await expect(detail).not.toBeVisible()
  await button.click()
  await expect.poll(() => api.detailCalls.length).toBe(2)
  await api.releaseDetail()
  await expect(detail.getByText('详情备注-2', { exact: true })).toBeVisible()
  await expect(detail.getByText('浙江省杭州市西湖区测试路2号', { exact: true })).toBeVisible()
  expect(api.listCalls).toHaveLength(1)
})

test('detail request failure shows the server error and allows retrying', async ({ page }) => {
  const api = await mockOrders(page, { detailFailure: '订单不存在' })
  await page.goto('/')
  const button = page.getByRole('row').filter({ hasText: 'OFFLINE-001' }).getByRole('button', { name: '详情', exact: true })
  await button.click()
  await expect(page.getByText('订单不存在', { exact: true })).toBeVisible()
  await expect(page.getByRole('dialog', { name: '订单详情', exact: true })).not.toBeVisible()
  await expect(button).toBeEnabled()
  await button.click()
  await expect.poll(() => api.detailCalls.length).toBe(2)
})

test('confirmation shows exact amount, prevents duplicate submission and refreshes payment type in list and detail', async ({ page }) => {
  const api = await mockOrders(page, { holdPayment: true })
  await page.goto('/')
  await page.getByRole('button', { name: '人工认款', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: '人工认款', exact: true })
  const confirm = dialog.getByRole('button', { name: '确认已收款', exact: true })
  await expect(dialog.getByText('OFFLINE-001', { exact: true })).toBeVisible()
  await expect(dialog.getByText('¥125.50', { exact: true })).toBeVisible()
  await expect(dialog.getByText('线下支付', { exact: true })).toBeVisible()
  await expect(confirm).toBeDisabled()
  await page.screenshot({ path: test.info().outputPath('manual-payment-confirmation.png'), animations: 'disabled' })
  await dialog.getByPlaceholder('可选，填写线下收款方式或凭证信息').fill('  银行转账凭证 001  ')
  await dialog.getByText('我已确认收到该订单的全部线下款项', { exact: true }).click()
  await confirm.dblclick()
  await expect.poll(() => api.requests.length).toBe(1)
  expect(api.requests[0]).toEqual({ method: 'PUT', body: { remark: '银行转账凭证 001' } })
  await expect(confirm).toBeDisabled()
  await expect(dialog.getByRole('button', { name: '取消', exact: true })).toBeDisabled()
  await api.releasePayment()
  await expect(dialog).not.toBeVisible()
  await expect.poll(() => api.listCalls.length).toBe(2)
  await expect.poll(() => api.statsCalls.length).toBe(2)
  const row = page.getByRole('row').filter({ hasText: 'OFFLINE-001' })
  await expect(row.getByText('线下支付', { exact: true })).toBeVisible()
  await expect(row.getByRole('button', { name: '人工认款', exact: true })).toHaveCount(0)
  await expect(row.getByRole('button', { name: '发货', exact: true })).toBeVisible()
  await row.getByRole('button', { name: '详情', exact: true }).click()
  const detail = page.getByRole('dialog', { name: '订单详情', exact: true })
  await expect(detail.getByText('线下支付', { exact: true })).toBeVisible()
  await expect(detail.getByText('MANUAL-001', { exact: true })).toBeVisible()
})

test('failed confirmation keeps note and shows server error; cancelling sends no new payment request', async ({ page }) => {
  const api = await mockOrders(page, { failure: '订单存在进行中的微信支付，请先关闭支付后重试' })
  await page.goto('/')
  await page.getByRole('button', { name: '人工认款', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: '人工认款', exact: true })
  const note = dialog.getByPlaceholder('可选，填写线下收款方式或凭证信息')
  await note.fill('现金收讫')
  await dialog.getByText('我已确认收到该订单的全部线下款项', { exact: true }).click()
  await dialog.getByRole('button', { name: '确认已收款', exact: true }).click()
  await expect(page.getByText('订单存在进行中的微信支付，请先关闭支付后重试', { exact: true })).toBeVisible()
  await expect(dialog).toBeVisible()
  await expect(note).toHaveValue('现金收讫')
  await dialog.getByRole('button', { name: '取消', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(api.requests).toHaveLength(1)
  expect(api.listCalls).toHaveLength(1)
})

test('HTTP error shows the server message instead of a generic request error', async ({ page }) => {
  const api = await mockOrders(page, { failure: '订单已取消，无法人工认款', failureStatus: 422 })
  await page.goto('/')
  await page.getByRole('button', { name: '人工认款', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: '人工认款', exact: true })
  await dialog.getByText('我已确认收到该订单的全部线下款项', { exact: true }).click()
  await dialog.getByRole('button', { name: '确认已收款', exact: true }).click()
  await expect(page.getByText('订单已取消，无法人工认款', { exact: true })).toBeVisible()
  await expect(dialog).toBeVisible()
  expect(api.requests).toHaveLength(1)
})

test('an order expiring while the confirmation dialog is open cannot be submitted', async ({ page }) => {
  const api = await mockOrders(page)
  await page.clock.setFixedTime(new Date('2026-10-15T00:00:00Z'))
  await page.goto('/')
  await page.getByRole('button', { name: '人工认款', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: '人工认款', exact: true })
  await dialog.getByText('我已确认收到该订单的全部线下款项', { exact: true }).click()
  await page.clock.setFixedTime(new Date('2099-01-02T00:00:00Z'))
  await dialog.getByRole('button', { name: '确认已收款', exact: true }).click()
  await expect(page.getByText('订单已失效或状态已变更，请刷新后重试', { exact: true })).toBeVisible()
  await expect(dialog).not.toBeVisible()
  await expect.poll(() => api.listCalls.length).toBe(2)
  expect(api.requests).toHaveLength(0)
})

test('cancelling sends no payment request and reopening clears the confirmation', async ({ page }) => {
  const api = await mockOrders(page, { failRefresh: true })
  await page.goto('/')
  await page.getByRole('button', { name: '人工认款', exact: true }).click()
  const dialog = page.getByRole('dialog', { name: '人工认款', exact: true })
  const note = dialog.getByPlaceholder('可选，填写线下收款方式或凭证信息')
  await note.fill('作废备注')
  await dialog.getByText('我已确认收到该订单的全部线下款项', { exact: true }).click()
  await dialog.getByRole('button', { name: '取消', exact: true }).click()
  await expect(dialog).not.toBeVisible()
  expect(api.requests).toHaveLength(0)
  await page.getByRole('button', { name: '人工认款', exact: true }).click()
  await expect(note).toHaveValue('')
  const confirm = dialog.getByRole('button', { name: '确认已收款', exact: true })
  await expect(confirm).toBeDisabled()
  await dialog.getByText('我已确认收到该订单的全部线下款项', { exact: true }).click()
  await confirm.click()
  await expect(dialog).not.toBeVisible()
  expect(api.requests).toEqual([{ method: 'PUT', body: {} }])
  await expect.poll(() => api.listCalls.length).toBe(2)
  const row = page.getByRole('row').filter({ hasText: 'OFFLINE-001' })
  await expect(row.getByText('线下支付', { exact: true })).toBeVisible()
  await expect(row.getByRole('button', { name: '人工认款', exact: true })).toHaveCount(0)
})
