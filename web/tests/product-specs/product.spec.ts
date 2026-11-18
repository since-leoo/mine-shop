import { expect, test, type Page } from '@playwright/test'

const sku = { id: 31, sku_code: 'SUPPLY-31', sku_name: '', cost_price: 1290, market_price: 2599, sale_price: 1999, stock: 100, warning_stock: 7, weight: 0.25, status: 'active' }

async function openProduct(page: Page, specValues: unknown, name = '', provider = 'merchant') {
  const errors: string[] = []
  page.on('pageerror', error => errors.push(error.message))
  await page.route('**/admin/**', async route => {
    const url = new URL(route.request().url())
    let data: unknown = []
    if (url.pathname === '/admin/product/product/8') {
      data = { id: 8, name: '测试', product_code: 'SUPPLY-8', category_id: 1, fulfillment_provider: provider, status: 'draft', min_price: 1999, max_price: 1999, skus: [{ ...sku, sku_name: name, spec_values: specValues }] }
    }
    if (url.pathname === '/admin/product/category/tree') data = [{ id: 1, name: '测试分类' }]
    await route.fulfill({ json: { code: 200, data } })
  })
  await page.goto('/')
  await expect(page.getByPlaceholder('商品名称', { exact: true })).toHaveValue('测试')
  await page.getByText('属性与规格', { exact: true }).click()
  return errors
}

function specSection(page: Page) {
  return page.locator('.section-card').filter({ has: page.getByText('规格配置', { exact: true }) })
}

async function saveProduct(page: Page) {
  const request = page.waitForRequest(request => request.method() === 'PUT' && request.url().endsWith('/admin/product/product/8'))
  await page.getByRole('button', { name: '保存商品', exact: true }).click()
  const payload = (await request).postDataJSON()
  await expect(page.getByText('保存完成', { exact: true })).toBeVisible()
  return payload
}

test('供应链对象规格及空 SKU 名称能直接保存并保留 SKU 身份与金额库存', async ({ page }) => {
  const errors = await openProduct(page, { 规格: '规格' }, '', 'supply_chain')
  const tags = specSection(page).locator('.el-tag__content')
  await expect(tags).toHaveText(['规格', '规格'])
  await expect(page.getByPlaceholder('SKU名称', { exact: true })).toHaveValue('测试')
  await expect(page.getByPlaceholder('红色,XL')).toHaveValue('规格')
  expect(await page.locator('input:visible').evaluateAll(inputs => inputs.map(input => (input as HTMLInputElement).value))).not.toContain('[object Object]')
  const payload = await saveProduct(page)
  expect(payload.skus).toEqual([{ ...sku, sku_name: '测试', spec_values: [{ name: '规格', value: '规格' }] }])
  expect(errors).toEqual([])
})

for (const [label, values] of [
  ['空白', [{ name: '  ', value: '规格' }]],
  ['缺失', [{ value: '规格' }]],
] as const) {
  test(`供应链规格名称${label}时使用默认，SKU 名称仍使用商品名`, async ({ page }) => {
    const errors = await openProduct(page, values, '', 'supply_chain')
    await expect(specSection(page).locator('.el-tag__content')).toHaveText(['默认', '规格'])
    await expect(page.getByPlaceholder('SKU名称', { exact: true })).toHaveValue('测试')
    const payload = await saveProduct(page)
    expect(payload.skus).toEqual([{ ...sku, sku_name: '测试', spec_values: [{ name: '默认', value: '规格' }] }])
    expect(errors).toEqual([])
  })
}

test('修改具名规格名称仍匹配原 SKU，并保存名称和规格图片', async ({ page }) => {
  const image = 'https://example.test/red.jpg'
  const errors = await openProduct(page, [{ name: '颜色', value: '红色', image }, { name: '尺码', value: 'L' }], ' 红色大号 ')
  const section = specSection(page)
  await expect(section.locator('.el-tag__content')).toHaveText(['颜色', '红色', '尺码', 'L'])
  const nameSelect = section.locator('.el-select').first()
  await nameSelect.locator('.el-tag__close').click()
  await nameSelect.locator('input').fill('色系')
  await page.getByRole('option', { name: '色系', exact: true }).click()
  await page.getByText('规格配置', { exact: true }).click()
  await expect(nameSelect.locator('.el-tag__content')).toHaveText(['色系'])
  const payload = await saveProduct(page)
  expect(payload.skus).toEqual([{ ...sku, sku_name: ' 红色大号 ', spec_values: [{ name: '色系', value: '红色', image }, { name: '尺码', value: 'L' }] }])
  expect(errors).toEqual([])
})

test('旧字符串数组可读取并保存为具名规格，编辑规格值不丢原 SKU', async ({ page }) => {
  const errors = await openProduct(page, ['蓝色', 'M'], '旧商品规格')
  await expect(specSection(page).locator('.el-tag__content')).toHaveText(['规格1', '蓝色', '规格2', 'M'])
  const valuesInput = page.getByPlaceholder('红色,XL')
  await valuesInput.fill('深蓝色,M')
  const payload = await saveProduct(page)
  expect(payload.skus).toEqual([{ ...sku, sku_name: '旧商品规格', spec_values: [{ name: '规格1', value: '深蓝色' }, { name: '规格2', value: 'M' }] }])
  expect(errors).toEqual([])
})
