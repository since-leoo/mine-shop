<script setup lang="ts">
import type { FaqVo } from '../../api/customer-service'
import { CirclePlus, Delete, EditPen, Search } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref } from 'vue'
import { getFaqs, removeFaq, saveFaq } from '../../api/customer-service'

defineOptions({ name: 'customer-service:faq' })

const loading = ref(false)
const keyword = ref('')
const items = ref<FaqVo[]>([])
const dialogVisible = ref(false)
const saving = ref(false)
const form = ref<Partial<FaqVo>>({ question: '', answer: '', category_name: '售后服务', enabled: true, sort: 0 })

const filteredItems = computed(() => items.value.filter(item => !keyword.value || `${item.question}${item.answer}${item.category_name || ''}`.toLowerCase().includes(keyword.value.toLowerCase())))
const categories = computed(() => [...new Set(items.value.map(item => item.category_name || '未分类'))])

function apiData<T>(response: { data: T }): T { return response.data }

async function loadFaqs() {
  loading.value = true
  try {
    const result = apiData(await getFaqs({ page: 1, page_size: 100 }))
    items.value = result.list || []
  }
  catch { ElMessage.warning('暂时无法加载常见问题，请确认客服插件接口已启用。') }
  finally { loading.value = false }
}

function openCreate() {
  form.value = { question: '', answer: '', category_name: categories.value[0] || '售后服务', enabled: true, sort: 0 }
  dialogVisible.value = true
}

function openEdit(item: FaqVo) {
  form.value = { ...item }
  dialogVisible.value = true
}

async function submit() {
  if (!form.value.question?.trim() || !form.value.answer?.trim()) {
    ElMessage.warning('请完整填写问题和答案')
    return
  }
  saving.value = true
  try {
    const saved = apiData(await saveFaq(form.value))
    const index = items.value.findIndex(item => item.id === saved.id)
    if (index === -1) items.value.unshift(saved)
    else items.value[index] = saved
    dialogVisible.value = false
    ElMessage.success(form.value.id ? '常见问题已更新' : '常见问题已创建')
  }
  catch { ElMessage.error('保存失败，请稍后重试') }
  finally { saving.value = false }
}

async function remove(item: FaqVo) {
  try {
    await ElMessageBox.confirm(`确定删除“${item.question}”吗？`, '删除常见问题', { type: 'warning', confirmButtonText: '删除', cancelButtonText: '取消' })
    await removeFaq(item.id)
    items.value = items.value.filter(candidate => candidate.id !== item.id)
    ElMessage.success('已删除')
  }
  catch {}
}

onMounted(loadFaqs)
</script>

<template>
  <main v-loading="loading" class="faq-page">
    <header class="page-header">
      <div><span class="eyebrow">KNOWLEDGE BASE</span><h1>常见问题</h1><p>整理标准答案，让每一次回复更快速、更一致。</p></div>
      <el-button type="primary" :icon="CirclePlus" @click="openCreate">新建问题</el-button>
    </header>

    <section class="faq-toolbar"><el-input v-model="keyword" :prefix-icon="Search" clearable placeholder="搜索问题、答案或分类" /><div class="faq-summary"><span>{{ items.length }} 条知识</span><i /> <span>{{ categories.length }} 个分类</span></div></section>

    <section v-if="filteredItems.length" class="faq-layout">
      <aside class="category-nav"><span class="nav-label">问题分类</span><button class="selected">全部问题 <b>{{ items.length }}</b></button><button v-for="category in categories" :key="category">{{ category }} <b>{{ items.filter(item => (item.category_name || '未分类') === category).length }}</b></button><div class="tip"><strong>使用建议</strong><p>保持答案简短清晰，并在政策变动后及时更新。</p></div></aside>
      <div class="faq-list"><article v-for="item in filteredItems" :key="item.id" class="faq-card"><div class="faq-category"><span>{{ item.category_name || '未分类' }}</span><em :class="{ disabled: !item.enabled }">{{ item.enabled ? '已启用' : '已停用' }}</em></div><h2>{{ item.question }}</h2><p>{{ item.answer }}</p><footer><span>更新于 {{ item.updated_at || '—' }}</span><div><el-button text :icon="EditPen" @click="openEdit(item)">编辑</el-button><el-button text type="danger" :icon="Delete" @click="remove(item)">删除</el-button></div></footer></article></div>
    </section>
    <el-empty v-else class="empty-state" :image-size="120" description="还没有匹配的常见问题"><el-button type="primary" @click="openCreate">创建第一条问题</el-button></el-empty>

    <el-dialog v-model="dialogVisible" :title="form.id ? '编辑常见问题' : '新建常见问题'" width="560px" destroy-on-close><el-form label-position="top"><el-form-item label="问题" required><el-input v-model="form.question" maxlength="100" show-word-limit placeholder="例如：订单发货后多久可以收到？" /></el-form-item><el-form-item label="标准答案" required><el-input v-model="form.answer" type="textarea" :autosize="{ minRows: 5, maxRows: 9 }" maxlength="2000" show-word-limit placeholder="请输入清晰、可直接发送给客户的答案" /></el-form-item><div class="form-row"><el-form-item label="分类"><el-input v-model="form.category_name" placeholder="例如：售后服务" /></el-form-item><el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" controls-position="right" /></el-form-item><el-form-item label="启用"><el-switch v-model="form.enabled" /></el-form-item></div></el-form><template #footer><el-button @click="dialogVisible = false">取消</el-button><el-button type="primary" :loading="saving" @click="submit">保存</el-button></template></el-dialog>
  </main>
</template>

<style scoped lang="scss">
.faq-page { min-height:100%; padding:28px; color:#19233d; background:#f6f8fc; }.page-header,.faq-toolbar,.faq-summary,.faq-category,.faq-card footer { display:flex; align-items:center; }.page-header { justify-content:space-between; max-width:1160px; margin:0 auto 23px; }.eyebrow { color:#7568e7; font-size:10px; font-weight:800; letter-spacing:1.5px; }.page-header h1 { margin:5px 0 5px; font-size:26px; letter-spacing:-.7px; }.page-header p { margin:0; color:#8290a8; font-size:13px; }.page-header :deep(.el-button--primary) { height:38px; padding:0 17px; border:0; border-radius:10px; background:#6559dc; box-shadow:0 8px 18px #6559dc2e; }.faq-toolbar { justify-content:space-between; gap:20px; max-width:1160px; padding:14px; margin:0 auto 14px; border:1px solid #eaedf4; border-radius:13px; background:#fff; }.faq-toolbar :deep(.el-input) { max-width:350px; }.faq-toolbar :deep(.el-input__wrapper) { background:#f7f8fc; box-shadow:none; }.faq-summary { gap:8px; color:#8490a5; font-size:11px; }.faq-summary i { width:3px; height:3px; border-radius:50%; background:#c5ccda; }.faq-layout { display:grid; grid-template-columns:205px minmax(0,1fr); gap:14px; max-width:1160px; margin:auto; }.category-nav,.faq-card { border:1px solid #e9edf5; border-radius:13px; background:#fff; }.category-nav { height:max-content; padding:11px; }.nav-label { display:block; padding:4px 7px 9px; color:#98a3b5; font-size:10px; font-weight:700; letter-spacing:.6px; }.category-nav button { display:flex; justify-content:space-between; width:100%; padding:9px 8px; cursor:pointer; color:#65718a; font-size:12px; text-align:left; border:0; border-radius:8px; background:transparent; }.category-nav button:hover { background:#f6f5ff; }.category-nav button.selected { color:#5d50d1; font-weight:700; background:#eeecff; }.category-nav b { font-size:10px; font-weight:500; }.tip { padding:12px; margin-top:15px; border-radius:9px; background:#f5f3ff; }.tip strong { color:#6256d3; font-size:11px; }.tip p { margin:5px 0 0; color:#7e86a2; font-size:10px; line-height:1.6; }.faq-list { display:grid; gap:10px; }.faq-card { padding:17px 18px 13px; transition:.2s; }.faq-card:hover { border-color:#d6d2ff; box-shadow:0 8px 22px #25335b0a; }.faq-category { gap:7px; }.faq-category span,.faq-category em { padding:3px 7px; color:#6960cc; font-size:10px; font-style:normal; border-radius:5px; background:#efeeff; }.faq-category em { color:#169a72; background:#e9f8f1; }.faq-category em.disabled { color:#8c96a8; background:#f1f3f6; }.faq-card h2 { margin:10px 0 7px; font-size:14px; }.faq-card p { display:-webkit-box; margin:0; overflow:hidden; color:#6f7b91; font-size:12px; line-height:1.7; -webkit-line-clamp:2; -webkit-box-orient:vertical; }.faq-card footer { justify-content:space-between; padding-top:11px; margin-top:12px; border-top:1px solid #f0f2f6; }.faq-card footer > span { color:#a3acbb; font-size:10px; }.faq-card :deep(.el-button) { height:24px; padding:0 5px; font-size:11px; }.empty-state { max-width:1160px; min-height:360px; margin:auto; border:1px solid #e9edf5; border-radius:13px; background:#fff; }.form-row { display:grid; grid-template-columns:1fr 120px 80px; gap:12px; }.form-row :deep(.el-form-item) { min-width:0; }
:global(html.dark) {
  .faq-page { color: #edf1fa; background: #0d1420; }
  .faq-toolbar,
  .category-nav,
  .faq-card,
  .empty-state { border-color: #2a3448; background: #161e2d; }
  .faq-toolbar :deep(.el-input__wrapper) { background: #202a3a; }
  .page-header p,
  .faq-summary,
  .nav-label,
  .tip p,
  .faq-card p,
  .faq-card footer > span { color: #aab5c8; }
  .faq-summary i { background: #56627a; }
  .category-nav button { color: #c3ccdc; }
  .category-nav button:hover { background: #202a3a; }
  .category-nav button.selected { color: #c4beff; background: rgb(101 89 220 / 28%); }
  .tip { background: #292544; }
  .tip strong { color: #c4beff; }
  .faq-card:hover { border-color: #6258ad; box-shadow: 0 10px 24px rgb(0 0 0 / 25%); }
  .faq-card footer { border-color: #2a3448; }
  .faq-category span { color: #c4beff; background: #302b58; }
  .faq-category em { color: #67d9b4; background: #183d39; }
  .faq-category em.disabled { color: #aab5c8; background: #202a3a; }
}
@media (max-width:900px) { .faq-page { padding:18px; }.faq-layout { grid-template-columns:1fr; }.category-nav { display:none; }.faq-toolbar { align-items:stretch; flex-direction:column; }.faq-toolbar :deep(.el-input) { max-width:none; } }
</style>
