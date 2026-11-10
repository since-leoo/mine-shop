<script setup lang="ts">
import type { CatalogProduct } from '../api/supply-chain'
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import hasAuth from '@/utils/permission/hasAuth'
import { getCatalog, getCatalogProduct, importProducts } from '../api/supply-chain'
import { errorMessage, money, safeJson } from './format'

const props = defineProps<{ connectionId?: number, mode?: string }>()
const emit = defineEmits<{ task: [id?: number] }>()
const rows = ref<CatalogProduct[]>([])
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const query = ref('')
const loading = ref(false)
const error = ref('')
const selection = ref<string[]>([])
const detail = ref<CatalogProduct>()
const detailOpen = ref(false)
const detailLoading = ref(false)
const importOpen = ref(false)
const importing = ref(false)
const categoryId = ref<number>()
const multiplier = ref(1)
const importError = ref('')
const canImport = computed(() => hasAuth('supply-chain:catalog/import') && (!props.mode || props.mode === 'active'))
let revision = 0
let previewRevision = 0

async function load(reset = false) {
  if (reset) { page.value = 1; rows.value = []; total.value = 0 }
  const current = ++revision
  if (!props.connectionId || !hasAuth('supply-chain:catalog/read')) { rows.value = []; total.value = 0; loading.value = false; return }
  loading.value = true
  error.value = ''
  try {
    const { data } = await getCatalog({ connection_id: props.connectionId, q: query.value.trim() || undefined, page: page.value, page_size: pageSize.value })
    if (current !== revision) return
    rows.value = data.list
    total.value = data.total
  }
  catch (failure) { if (current === revision) error.value = errorMessage(failure) }
  finally { if (current === revision) loading.value = false }
}

function toggleSelection(row: CatalogProduct, checked: boolean) {
  if (checked) {
    if (selection.value.length >= 50) { ElMessage.warning('每批最多导入 50 件商品'); return }
    selection.value = [...new Set([...selection.value, row.external_product_id])]
  }
  else selection.value = selection.value.filter(id => id !== row.external_product_id)
}

async function preview(row: CatalogProduct) {
  if (!props.connectionId) return
  const current = ++previewRevision
  detail.value = undefined
  detailOpen.value = true
  detailLoading.value = true
  error.value = ''
  try {
    const response = await getCatalogProduct(row.external_product_id, props.connectionId)
    if (current === previewRevision) detail.value = response.data
  }
  catch (failure) { if (current === previewRevision) error.value = errorMessage(failure) }
  finally { if (current === previewRevision) detailLoading.value = false }
}

async function submitImport() {
  if (!props.connectionId || !canImport.value || importing.value) return
  importError.value = ''
  if (!Number.isSafeInteger(categoryId.value) || Number(categoryId.value) < 1) { importError.value = '请填写本系统有效的商品分类 ID'; return }
  if (!Number.isFinite(multiplier.value) || multiplier.value < 1 || multiplier.value > 10) { importError.value = '售价倍率需在 1 至 10 之间'; return }
  if (selection.value.length < 1 || selection.value.length > 50) { importError.value = '每批请选择 1 至 50 件商品'; return }
  importing.value = true
  try {
    const { data } = await importProducts({ connection_id: props.connectionId, external_product_ids: [...selection.value], category_id: categoryId.value!, price_multiplier: multiplier.value })
    importOpen.value = false
    selection.value = []
    ElMessage.success('导入任务已提交，请在任务日志查看执行结果')
    emit('task', data.task_id || data.task_ids?.[0])
  }
  catch (failure) { importError.value = errorMessage(failure) }
  finally { importing.value = false }
}

watch(() => props.connectionId, () => { ++previewRevision; selection.value = []; detailOpen.value = false; importOpen.value = false; void load(true) }, { immediate: true })
</script>

<template>
  <section>
    <el-alert v-if="error" :title="error" type="error" :closable="false" show-icon />
    <el-empty v-if="!connectionId" description="请先选择供应链连接" />
    <template v-else>
      <el-alert v-if="mode && mode !== 'active'" title="当前连接暂停新业务，仅可浏览商品，暂不接受导入。" type="warning" :closable="false" />
      <div class="toolbar">
        <el-input v-model="query" placeholder="搜索上游商品名称或编号" clearable @keyup.enter="load(true)" />
        <el-button type="primary" @click="load(true)">搜索商品</el-button>
        <el-button @click="load()">刷新</el-button>
        <span class="selection">已选 {{ selection.length }} / 50 件（跨页保留）</span>
        <el-button v-if="selection.length" link @click="selection = []">清空选择</el-button>
        <el-button v-if="hasAuth('supply-chain:catalog/import')" type="primary" :disabled="!selection.length || !canImport" data-testid="open-import" @click="importError = ''; importOpen = true">导入所选</el-button>
      </div>
      <el-table v-loading="loading" :data="rows" row-key="external_product_id" empty-text="没有符合条件的上游商品">
        <el-table-column label="选择" width="65"><template #default="{ row }"><el-checkbox :model-value="selection.includes(row.external_product_id)" :disabled="row.imported || !canImport" :aria-label="`选择商品 ${row.external_product_id}`" @change="toggleSelection(row as CatalogProduct, Boolean($event))" /></template></el-table-column>
        <el-table-column prop="external_product_id" label="上游商品编号" min-width="145" />
        <el-table-column prop="name" label="商品名称" min-width="230" />
        <el-table-column label="采购价" width="110"><template #default="{ row }">{{ money(row.cost_price) }}</template></el-table-column>
        <el-table-column prop="stock" label="上游库存" width="110" />
        <el-table-column label="选品状态" width="135"><template #default="{ row }"><el-tag :type="row.imported ? 'success' : 'info'">{{ row.imported ? '本地已导入' : row.selected === true ? '上游已选' : row.selected === false ? '尚未选品' : '可选商品' }}</el-tag></template></el-table-column>
        <el-table-column label="操作" width="100"><template #default="{ row }"><el-button link type="primary" @click="preview(row as CatalogProduct)">详情预览</el-button></template></el-table-column>
      </el-table>
      <el-pagination v-model:current-page="page" v-model:page-size="pageSize" :page-sizes="[20, 50]" :total="total" layout="total, sizes, prev, pager, next" @current-change="load()" @size-change="load(true)" />
    </template>
    <el-drawer v-model="detailOpen" title="上游商品预览" size="720px">
      <div v-loading="detailLoading" class="preview">
        <el-alert v-if="error" :title="error" type="error" :closable="false" />
        <template v-if="detail">
          <h3>{{ detail.name }}</h3>
          <el-descriptions :column="2" border><el-descriptions-item label="上游编号">{{ detail.external_product_id }}</el-descriptions-item><el-descriptions-item label="采购价">{{ money(detail.cost_price) }}</el-descriptions-item><el-descriptions-item label="库存">{{ detail.stock ?? '—' }}</el-descriptions-item><el-descriptions-item label="本地状态">{{ detail.imported ? '已导入' : '未导入' }}</el-descriptions-item></el-descriptions>
          <p>预览不会自动选品或导入；导入结果以任务记录为准。</p>
          <el-table :data="detail.skus || []" empty-text="暂无规格数据">
            <el-table-column prop="external_sku_id" label="上游 SKU" min-width="130" />
            <el-table-column label="规格" min-width="180"><template #default="{ row }">{{ row.name || safeJson(row.spec_values) }}</template></el-table-column>
            <el-table-column label="采购价" width="100"><template #default="{ row }">{{ money(row.cost_price) }}</template></el-table-column>
            <el-table-column prop="stock" label="库存" width="90" />
          </el-table>
          <pre v-if="detail.description" class="plain-detail">{{ detail.description }}</pre>
        </template>
      </div>
    </el-drawer>
    <el-dialog v-model="importOpen" title="确认导入所选商品" width="560px" :close-on-click-modal="false">
      <el-alert v-if="importError" :title="importError" type="error" :closable="false" show-icon />
      <p>本批 {{ selection.length }} 件商品将创建本地商品与 SKU。已存在的映射由后端校验，避免重复导入。</p>
      <el-form label-position="top" :disabled="importing">
        <el-form-item label="本地商品分类 ID" required><el-input-number v-model="categoryId" :min="1" :precision="0" :controls="false" data-testid="import-category" /><div class="hint">填写主项目商品分类的 ID，后端会验证分类是否存在。</div></el-form-item>
        <el-form-item label="售价倍率" required><el-input-number v-model="multiplier" :min="1" :max="10" :precision="2" :step="0.1" data-testid="import-multiplier" /><div class="hint">以采购价乘以倍率生成初始售价；导入后按商品同步策略维护。</div></el-form-item>
      </el-form>
      <template #footer><el-button :disabled="importing" @click="importOpen = false">取消</el-button><el-button type="primary" :loading="importing" data-testid="confirm-import" @click="submitImport">提交导入任务</el-button></template>
    </el-dialog>
  </section>
</template>

<style scoped>
.toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.toolbar .el-input { width: 280px; }
.selection { margin-left: auto; color: var(--el-text-color-secondary); }
.el-alert { margin-bottom: 16px; }
.el-pagination { margin-top: 20px; justify-content: flex-end; }
.hint { width: 100%; color: var(--el-text-color-secondary); font-size: 12px; margin-top: 6px; }
.preview { min-height: 180px; }
.plain-detail { white-space: pre-wrap; word-break: break-word; font-family: inherit; }
</style>
