import { test, expect } from '@playwright/test'
test('真实FAQ发布契约：published_revision_id及保存草稿保留旧发布版，显式发布新版本', async ({ page }) => {
  let faq: any = { id: 1, question: '如何退款', answer: '原标准答案', category_id: 2, category_name: '退款', enabled: true, sort: 0, alternate_questions: ['退钱'], published_revision_id: 12 }
  const saves: any[] = []
  await page.route('**/admin/customer-service/**', async route => {
    const path = new URL(route.request().url()).pathname
    if (path.endsWith('/categories')) return route.fulfill({ json: { data: [{ id: 2, name: '退款', parent_id: 0 }] } })
    if (route.request().method() === 'PUT') {
      const payload = route.request().postDataJSON(); saves.push(payload)
      faq = { ...faq, answer: payload.answer, enabled: payload.enabled, published_revision_id: payload.publish ? 13 : faq.published_revision_id }
      return route.fulfill({ json: { data: faq } })
    }
    return route.fulfill({ json: { data: { list: [faq], total: 1 } } })
  })
  await page.goto('/?page=faq'); await expect(page.getByText('已有发布版本 #12', { exact: true })).toBeVisible(); await page.getByRole('button', { name: '编辑', exact: true }).click()
  const dialog = page.getByRole('dialog'); const publish = dialog.locator('.el-form-item').filter({ hasText: '同时发布新版本到 AI 知识库' }).locator('.el-switch')
  await expect(publish).not.toHaveClass(/is-checked/); await expect(dialog.getByText('已有发布版本 #12。仅保存草稿时，旧发布版继续使用；停用问题可下架。')).toBeVisible()
  await dialog.getByPlaceholder('请输入清晰、可直接发送给客户的答案').fill('编辑后的草稿答案'); await dialog.getByRole('button', { name: '保存', exact: true }).click(); await expect.poll(() => saves.length).toBe(1); expect(saves[0].publish).toBe(false); await expect(page.getByText('已有发布版本 #12', { exact: true })).toBeVisible()
  await page.getByRole('button', { name: '编辑', exact: true }).click(); await publish.click(); await expect(dialog.getByText('本次新版本生效时间（留空立即生效）', { exact: true })).toBeVisible(); await dialog.getByRole('button', { name: '保存', exact: true }).click(); await expect.poll(() => saves.length).toBe(2); expect(saves[1].publish).toBe(true); await expect(page.getByText('已有发布版本 #13', { exact: true })).toBeVisible()
})
