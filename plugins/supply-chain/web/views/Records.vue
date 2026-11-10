<script setup lang="ts">
import type { ApiLog, ProductLink, Purchase, PurchaseDetail, Task, TaskDetail } from '../api/supply-chain'
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import hasAuth from '@/utils/permission/hasAuth'
import { getLog, getLogs, getProducts, getPurchase, getPurchases, getTask, getTasks, queryPurchase, retryTask, syncProduct } from '../api/supply-chain'
import { canRetry, errorMessage, money, statusLabel, statusType } from './format'
import ExecutionDetails from './ExecutionDetails.vue'

type RecordType = 'products' | 'purchases' | 'tasks' | 'logs'
const props = defineProps<{ type: RecordType, connectionId?: number, taskId?: number }>()
const emit = defineEmits<{ task: [id?: number] }>()
const rows = ref<Array<ProductLink | Purchase | Task | ApiLog>>([])
const loading = ref(false)
const busy = ref<number>()
const error = ref('')
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const query = ref('')
const status = ref('')
const traceId = ref('')
const from = ref('')
const to = ref('')
const detailOpen = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const task = ref<TaskDetail>()
const log = ref<ApiLog>()
const purchase = ref<PurchaseDetail>()
const retrying = ref(false)
const retryOpen = ref(false)
const retryTarget = ref<Task>()
const reason = ref('')
const retryError = ref('')
const canParams = computed(() => hasAuth('supply-chain:logs/params'))
const detailTitle = computed(() => log.value ? `接口日志 #${log.value.id}` : task.value ? `任务 #${task.value.id}` : purchase.value ? `采购单 #${purchase.value.id}` : '执行详情')
const statuses = computed(() => props.type === 'products'
  ? ['active', 'disabled', 'failed']
  : props.type === 'purchases' ? ['pending', 'submitting', 'accepted', 'unknown', 'failed', 'partial_shipped', 'shipped', 'completed', 'cancelled']
    : props.type === 'logs' ? ['started', 'succeeded', 'failed', 'unknown']
      : ['pending', 'running', 'succeeded', 'failed', 'retry_wait', 'unknown', 'cancelled', 'skipped'])
let revision = 0
let detailRevision = 0

async function load(reset = false) {
  if (!hasAuth(`supply-chain:${props.type}/read`)) return
  if (reset) { page.value = 1; rows.value = []; total.value = 0 }
  const current = ++revision
  loading.value = true
  error.value = ''
  try {
    const params = { connection_id: props.connectionId, page: page.value, page_size: pageSize.value, q: query.value.trim() || undefined, status: status.value || undefined, trace_id: traceId.value.trim() || undefined, created_from: from.value || undefined, created_to: to.value || undefined }
    const response = props.type === 'products' ? await getProducts(params)
      : props.type === 'purchases' ? await getPurchases(params)
        : props.type === 'tasks' ? await getTasks(params) : await getLogs(params)
    if (current !== revision) return
    rows.value = response.data.list
    total.value = response.data.total
  }
  catch (failure) { if (current === revision) error.value = errorMessage(failure) }
  finally { if (current === revision) loading.value = false }
}

async function openTask(id: number) {
  if (!hasAuth('supply-chain:tasks/read')) return
  const current = ++detailRevision
  detailOpen.value = true
  detailLoading.value = true
  detailError.value = ''
  task.value = undefined
  log.value = undefined
  purchase.value = undefined
  try {
    const { data } = await getTask(id, canParams.value)
    if (current === detailRevision) task.value = data
  }
  catch (failure) { if (current === detailRevision) detailError.value = errorMessage(failure) }
  finally { if (current === detailRevision) detailLoading.value = false }
}

async function openLog(id: number) {
  if (!canParams.value) return
  const current = ++detailRevision
  detailOpen.value = true
  detailLoading.value = true
  detailError.value = ''
  task.value = undefined
  log.value = undefined
  purchase.value = undefined
  try {
    const { data } = await getLog(id)
    if (current === detailRevision) log.value = data
  }
  catch (failure) { if (current === detailRevision) detailError.value = errorMessage(failure) }
  finally { if (current === detailRevision) detailLoading.value = false }
}

async function openPurchase(id: number) {
  if (!hasAuth('supply-chain:purchases/read')) return
  const current = ++detailRevision
  detailOpen.value = true
  detailLoading.value = true
  detailError.value = ''
  task.value = undefined
  log.value = undefined
  purchase.value = undefined
  try {
    const { data } = await getPurchase(id)
    if (current === detailRevision) purchase.value = data
  }
  catch (failure) { if (current === detailRevision) detailError.value = errorMessage(failure) }
  finally { if (current === detailRevision) detailLoading.value = false }
}

async function action(row: ProductLink | Purchase, kind: 'sync' | 'query') {
  if (busy.value || !hasAuth(`supply-chain:${kind === 'sync' ? 'products/sync' : 'purchases/query'}`)) return
  busy.value = row.id
  error.value = ''
  try {
    const { data } = await (kind === 'sync' ? syncProduct(row.id) : queryPurchase(row.id))
    ElMessage.success(kind === 'sync' ? '同步任务已提交，请查看任务执行结果' : '上游查询任务已提交，请查看任务执行结果')
    emit('task', data.task_id || data.task_ids?.[0])
    await load()
  }
  catch (failure) { error.value = errorMessage(failure) }
  finally { busy.value = undefined }
}

function startRetry(row: Task) {
  if (!hasAuth('supply-chain:tasks/retry') || !canRetry(row)) return
  retryTarget.value = row
  reason.value = ''
  retryError.value = ''
  retryOpen.value = true
}

async function submitRetry() {
  if (!retryTarget.value || !canRetry(retryTarget.value) || retrying.value || !hasAuth('supply-chain:tasks/retry')) return
  if (!reason.value.trim()) { retryError.value = '请填写本次重试原因'; return }
  retrying.value = true
  retryError.value = ''
  try {
    await retryTask(retryTarget.value.id, reason.value.trim())
    retryOpen.value = false
    ElMessage.success('重试已提交，将新增执行记录并保留历史失败原因')
    if (task.value?.id === retryTarget.value.id) await openTask(task.value.id)
    await load()
  }
  catch (failure) { retryError.value = errorMessage(failure) }
  finally { retrying.value = false }
}

watch(() => [props.type, props.connectionId], () => { status.value = ''; void load(true) }, { immediate: true })
watch(() => props.taskId, (id) => { if (id && props.type === 'tasks') void openTask(id) }, { immediate: true })
</script>

<template>
  <section>
    <el-alert v-if="error" :title="error" type="error" :closable="false" show-icon />
    <el-alert v-if="type === 'purchases'" title="结果待确认的采购请先查询上游结果，避免重复采购。客户退款与供应商退款分别跟踪。" type="info" :closable="false" show-icon />
    <div class="toolbar">
      <el-input v-model="query" :placeholder="type === 'products' ? '商品名称 / 上游商品编号' : type === 'purchases' ? '商城订单号 / 商户采购号' : type === 'logs' ? '接口路径 / 操作 / 错误关键词' : '任务类型 / 业务键 / 错误关键词'" clearable @keyup.enter="load(true)" />
      <el-select v-model="status" placeholder="全部状态" clearable aria-label="筛选状态"><el-option v-for="item in statuses" :key="item" :label="statusLabel(item)" :value="item" /></el-select>
      <el-input v-if="type === 'tasks' || type === 'logs'" v-model="traceId" placeholder="Trace ID" clearable @keyup.enter="load(true)" />
      <el-date-picker v-if="type === 'tasks' || type === 'logs'" v-model="from" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="开始时间" />
      <el-date-picker v-if="type === 'tasks' || type === 'logs'" v-model="to" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="结束时间" />
      <el-button type="primary" @click="load(true)">查询</el-button><el-button @click="load()">刷新</el-button>
    </div>
    <el-table v-loading="loading" :data="rows" row-key="id" empty-text="暂无符合条件的记录">
      <el-table-column prop="id" label="ID" width="80" />
      <template v-if="type === 'products'">
        <el-table-column prop="product_id" label="本地商品 ID" width="120" />
        <el-table-column prop="external_product_id" label="上游商品编号" min-width="160" />
        <el-table-column prop="name" label="商品名称" min-width="200" />
        <el-table-column prop="last_synced_at" label="最近同步" min-width="175" />
      </template>
      <template v-if="type === 'purchases'">
        <el-table-column label="商城订单" min-width="170"><template #default="{ row }">{{ row.order_no || row.order_id }}</template></el-table-column>
        <el-table-column prop="merchant_order_no" label="商户采购号" min-width="185" />
        <el-table-column prop="external_order_no" label="上游订单号" min-width="165" />
      </template>
      <template v-if="type === 'tasks'">
        <el-table-column prop="type" label="任务类型" min-width="140" />
        <el-table-column prop="attempt_count" label="尝试次数" width="100" />
        <el-table-column prop="next_retry_at" label="下次重试" min-width="175" />
      </template>
      <template v-if="type === 'logs'">
        <el-table-column label="接口" min-width="230"><template #default="{ row }">{{ row.method }} {{ row.path }}</template></el-table-column>
        <el-table-column prop="http_status" label="HTTP 状态" width="100" />
        <el-table-column prop="provider_code" label="业务码" width="100" />
        <el-table-column prop="duration_ms" label="耗时 ms" width="100" />
      </template>
      <el-table-column label="状态" width="125"><template #default="{ row }"><el-tag :type="statusType(row.status)">{{ statusLabel(row.status) }}</el-tag></template></el-table-column>
      <el-table-column label="失败原因" min-width="230"><template #default="{ row }">{{ row.error_message || row.last_error || '—' }}</template></el-table-column>
      <el-table-column v-if="type !== 'products'" prop="created_at" label="创建时间" min-width="175" />
      <el-table-column label="操作" min-width="190" fixed="right"><template #default="{ row }">
        <el-button v-if="type === 'products' && hasAuth('supply-chain:products/sync')" link type="primary" :loading="busy === row.id" :disabled="!!busy" @click="action(row as ProductLink, 'sync')">同步商品</el-button>
        <template v-if="type === 'purchases'">
          <el-button link type="primary" @click="openPurchase(row.id)">采购明细</el-button>
          <el-button v-if="hasAuth('supply-chain:purchases/query')" link type="primary" :loading="busy === row.id" :disabled="!!busy" @click="action(row as Purchase, 'query')">查询上游结果</el-button>
          <el-button v-if="row.task_id && hasAuth('supply-chain:tasks/read')" link @click="emit('task', row.task_id)">任务日志</el-button>
        </template>
        <template v-if="type === 'tasks'">
          <el-button link type="primary" @click="openTask(row.id)">执行详情</el-button>
          <el-button v-if="hasAuth('supply-chain:tasks/retry') && canRetry(row as Task)" link type="warning" @click="startRetry(row as Task)">重试</el-button>
          <span v-if="row.status === 'unknown'" class="muted">请在采购单查单</span>
        </template>
        <template v-if="type === 'logs'">
          <el-button v-if="canParams" link type="primary" @click="openLog(row.id)">请求参数与结果</el-button>
          <el-button v-if="row.task_id && hasAuth('supply-chain:tasks/read')" link @click="emit('task', row.task_id)">关联任务</el-button>
          <span v-if="!canParams" class="muted">无参数查看权限</span>
        </template>
      </template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="page" v-model:page-size="pageSize" :page-sizes="[20, 50, 100]" :total="total" layout="total, sizes, prev, pager, next" @current-change="load()" @size-change="load(true)" />
    <el-drawer v-model="detailOpen" :title="detailTitle" size="min(1000px, 100%)" class="supply-record-drawer" append-to-body>
      <template #header="{ titleId, titleClass }">
        <div class="detail-header"><span class="detail-eyebrow">供应链 · {{ log ? '接口日志' : purchase ? '采购订单' : '任务与重试' }}</span><h2 :id="titleId" :class="titleClass">{{ detailTitle }}</h2><p>{{ log ? '查看接口调用结果与请求、响应内容' : purchase ? '查看采购信息与上游订单明细' : '追踪任务进度、执行历史与关联接口' }}</p></div>
      </template>
      <div v-loading="detailLoading" class="detail-content">
        <el-alert v-if="detailError" :title="detailError" type="error" :closable="false" show-icon />
        <template v-if="purchase">
          <el-descriptions :column="2" border><el-descriptions-item label="商城订单">{{ purchase.order_no || purchase.order_id }}</el-descriptions-item><el-descriptions-item label="商户采购号">{{ purchase.merchant_order_no }}</el-descriptions-item><el-descriptions-item label="状态">{{ statusLabel(purchase.status) }}</el-descriptions-item><el-descriptions-item label="失败原因">{{ purchase.last_error || '—' }}</el-descriptions-item></el-descriptions>
          <el-empty v-if="!purchase.external_orders?.length" description="暂无已确认的上游订单明细，可查询上游结果后刷新" />
          <template v-for="order in purchase.external_orders || []" :key="order.external_order_no"><h3>上游订单 {{ order.external_order_no }}</h3><el-table :data="order.items"><el-table-column prop="external_order_item_id" label="上游订单行 ID" min-width="170" /><el-table-column prop="external_sku_id" label="上游 SKU" min-width="130" /><el-table-column prop="quantity" label="数量" width="80" /><el-table-column label="金额" width="120"><template #default="{ row }">{{ money(row.amount_cents) }}</template></el-table-column></el-table></template>
        </template>
        <ExecutionDetails :task="task" :log="log" :can-params="canParams" @log="openLog" />
      </div>
      <template #footer><div class="detail-footer"><span>{{ log ? '请求与响应中的敏感字段已隐藏' : task ? '每次执行记录均保留，可追溯处理过程' : '采购记录与商城订单关联' }}</span><div class="detail-footer-actions"><el-button @click="detailOpen = false">关闭</el-button><el-button v-if="task && hasAuth('supply-chain:tasks/retry') && canRetry(task)" type="primary" @click="startRetry(task)">重试此任务</el-button><el-button v-if="log?.task_id && hasAuth('supply-chain:tasks/read')" type="primary" @click="openTask(log.task_id)">查看关联任务 #{{ log.task_id }}</el-button></div></div></template>
    </el-drawer>
    <el-dialog v-model="retryOpen" title="重试失败任务" width="min(520px, calc(100% - 32px))" class="supply-retry-dialog" :close-on-click-modal="false" append-to-body>
      <div class="retry-summary"><div><span>任务 #{{ retryTarget?.id }}</span><strong>{{ retryTarget?.type }}</strong></div><el-tag v-if="retryTarget" :type="statusType(retryTarget.status)" round>{{ statusLabel(retryTarget.status) }}</el-tag></div>
      <p class="retry-hint">将使用原任务参数重新执行，历史执行记录会保留。</p>
      <el-alert v-if="retryError" :title="retryError" type="error" :closable="false" show-icon />
      <label class="retry-label" for="supply-retry-reason">重试原因 <span>必填</span></label>
      <el-input id="supply-retry-reason" v-model="reason" type="textarea" :rows="4" maxlength="500" show-word-limit placeholder="例如：已处理供应商限流，重新同步商品" data-testid="retry-reason" />
      <template #footer><el-button :disabled="retrying" @click="retryOpen = false">取消</el-button><el-button type="primary" :loading="retrying" data-testid="confirm-retry" @click="submitRetry">提交重试</el-button></template>
    </el-dialog>
  </section>
</template>

<style scoped>
.toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.toolbar .el-input { width: 230px; }
.toolbar .el-select { width: 150px; }
.el-alert { margin-bottom: 16px; }
.el-pagination { margin-top: 20px; justify-content: flex-end; }
.muted { color: var(--el-text-color-secondary); font-size: 12px; }
.detail-content { min-height: 180px; }
.detail-header { min-width: 0; }
.detail-eyebrow { color: var(--el-color-primary); font-size: 12px; font-weight: 500; }
.detail-header h2 { margin: 8px 0 7px; color: var(--el-text-color-primary); font-size: 21px; line-height: 1.4; font-weight: 600; }
.detail-header p { margin: 0; color: var(--el-text-color-secondary); font-size: 13px; line-height: 1.6; }
.detail-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.detail-footer > span { color: var(--el-text-color-secondary); font-size: 12px; text-align: left; }
.detail-footer-actions { display: flex; align-items: center; flex-shrink: 0; gap: 10px; }
.detail-footer-actions .el-button + .el-button { margin-left: 0; }
.retry-summary { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px; border: 1px solid var(--el-border-color-lighter); border-radius: 10px; background: var(--el-fill-color-light); }
.retry-summary > div { min-width: 0; }
.retry-summary > div > span { font-size: 12px; color: var(--el-text-color-secondary); }
.retry-summary strong { display: block; margin-top: 7px; color: var(--el-text-color-primary); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 15px; overflow-wrap: anywhere; }
.retry-hint { margin: 16px 0 24px; color: var(--el-text-color-secondary); font-size: 13px; line-height: 1.7; }
.retry-label { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; color: var(--el-text-color-primary); font-size: 13px; font-weight: 500; }
.retry-label span { color: var(--el-text-color-secondary); font-size: 11px; font-weight: 400; }
@media (max-width: 540px) {
  .detail-footer { align-items: stretch; flex-direction: column; }
  .detail-footer-actions { justify-content: flex-end; }
}
</style>

<style>
.supply-record-drawer, .supply-retry-dialog { font-family: var(--el-font-family); }
.supply-record-drawer .el-drawer__header { align-items: flex-start; gap: 16px; margin-bottom: 0; padding: 26px 28px 22px; border-bottom: 1px solid var(--el-border-color-lighter); }
.supply-record-drawer .el-drawer__close-btn { margin-top: 3px; }
.supply-record-drawer .el-drawer__body { padding: 24px 28px; background: var(--el-fill-color-lighter); }
.supply-record-drawer .el-drawer__footer { padding: 16px 28px; border-top: 1px solid var(--el-border-color-lighter); }
.supply-retry-dialog.el-dialog { padding: 24px; border-radius: 16px; }
.supply-retry-dialog .el-dialog__header { padding-bottom: 22px; }
.supply-retry-dialog .el-dialog__title { font-size: 19px; font-weight: 600; }
.supply-retry-dialog .el-dialog__footer { padding-top: 24px; }
@media (max-width: 540px) {
  .supply-record-drawer .el-drawer__header { padding: 22px 18px 18px; }
  .supply-record-drawer .el-drawer__body { padding: 18px 14px; }
  .supply-record-drawer .el-drawer__footer { padding: 14px 18px; }
}
</style>
