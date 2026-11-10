<script setup lang="ts">
import type { AfterSale, AfterSaleInput } from '../api/supply-chain'
import { reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import hasAuth from '@/utils/permission/hasAuth'
import { getAfterSale, getAfterSales, getAfterSaleTypes, queryAfterSale, submitAfterSale } from '../api/supply-chain'
import { errorMessage, money, safeJson, statusLabel, statusType } from './format'

const props = defineProps<{ connectionId?: number }>()
const emit = defineEmits<{ task: [id?: number] }>()
const rows = ref<AfterSale[]>([])
const page = ref(1)
const total = ref(0)
const loading = ref(false)
const busy = ref(false)
const error = ref('')
const detailOpen = ref(false)
const submitting = ref(false)
const detailLoading = ref(false)
const current = ref<AfterSale>()
const policy = ref<unknown>()
const policyLoaded = ref(false)
const detailError = ref('')
const form = reactive<AfterSaleInput>({ external_order_item_id: '', quantity: 1, amount_cents: 0, freight_cents: 0, technical_service_fee_cents: 0, reason_type: undefined, reason: '', description: '', received_status: undefined, refund_type: undefined, refund_way: undefined })
let revision = 0

async function load(reset = false) {
  if (!hasAuth('supply-chain:after-sales/read')) return
  if (reset) { page.value = 1; rows.value = [] }
  const requestVersion = ++revision
  loading.value = true
  error.value = ''
  try {
    const { data } = await getAfterSales({ connection_id: props.connectionId, page: page.value, page_size: 20 })
    if (requestVersion !== revision) return
    rows.value = data.list
    total.value = data.total
  }
  catch (failure) { if (requestVersion === revision) error.value = errorMessage(failure) }
  finally { if (requestVersion === revision) loading.value = false }
}

async function open(row: AfterSale, submit = false) {
  detailOpen.value = true
  submitting.value = submit
  detailLoading.value = true
  detailError.value = ''
  current.value = undefined
  policy.value = undefined
  policyLoaded.value = false
  Object.assign(form, { external_order_item_id: '', quantity: 1, amount_cents: 0, freight_cents: 0, technical_service_fee_cents: 0, reason_type: undefined, reason: '', description: '', received_status: undefined, refund_type: undefined, refund_way: undefined })
  try {
    current.value = (await getAfterSale(row.id)).data
  }
  catch (failure) { detailError.value = errorMessage(failure) }
  finally { detailLoading.value = false }
}

async function loadPolicy() {
  const connectionId = current.value?.connection_id || props.connectionId
  if (!connectionId) { detailError.value = '请先选择此售后所属供应链连接，再读取上游售后政策'; return }
  if (!form.external_order_item_id.trim()) { detailError.value = '请先选用或填写上游订单行 ID，再读取该行售后政策'; return }
  const itemId = form.external_order_item_id.trim()
  const saleId = current.value?.id
  policyLoaded.value = false
  detailError.value = ''
  try {
    const { data } = await getAfterSaleTypes(connectionId, itemId)
    if (itemId !== form.external_order_item_id.trim() || saleId !== current.value?.id) return
    policy.value = data
    policyLoaded.value = true
  }
  catch (failure) { detailError.value = errorMessage(failure) }
}

async function submit() {
  if (!current.value || busy.value || !hasAuth('supply-chain:after-sales/submit')) return
  detailError.value = ''
  if (!policyLoaded.value) { detailError.value = '请成功读取上游售后政策后再提交'; return }
  if (form.refund_type === 2) { detailError.value = '当前不支持换货（refund_type=2），请按上游政策选择已支持的退款类型'; return }
  if (!form.external_order_item_id.trim() || !form.reason.trim()) { detailError.value = '请填写上游订单行 ID 和售后原因'; return }
  if (!Number.isSafeInteger(form.quantity) || form.quantity < 1 || [form.amount_cents, form.freight_cents, form.technical_service_fee_cents].some(value => !Number.isSafeInteger(value) || value < 0)) { detailError.value = '数量需为正整数，金额需为非负整数分'; return }
  if ([form.reason_type, form.received_status, form.refund_type, form.refund_way].some(value => !Number.isSafeInteger(value) || Number(value) < 0)) { detailError.value = '请根据上游政策填写原因类型、收货状态、退款类型和退款方式编码'; return }
  busy.value = true
  try {
    const { data } = await submitAfterSale(current.value.id, { ...form, external_order_item_id: form.external_order_item_id.trim(), reason: form.reason.trim() })
    detailOpen.value = false
    ElMessage.success('供应商售后任务已提交，客户退款仍需在商城售后中审核')
    emit('task', data.task_id || data.task_ids?.[0])
    await load()
  }
  catch (failure) { detailError.value = errorMessage(failure) }
  finally { busy.value = false }
}

async function query(row: AfterSale) {
  if (busy.value || !hasAuth('supply-chain:after-sales/query')) return
  busy.value = true
  error.value = ''
  try {
    const { data } = await queryAfterSale(row.id)
    ElMessage.success('供应商售后查询任务已提交')
    emit('task', data.task_id || data.task_ids?.[0])
  }
  catch (failure) { error.value = errorMessage(failure) }
  finally { busy.value = false }
}
watch(() => props.connectionId, () => { detailOpen.value = false; void load(true) }, { immediate: true })
watch(() => form.external_order_item_id, () => { policyLoaded.value = false; policy.value = undefined })
</script>

<template>
  <section>
    <el-alert title="这里处理供应商采购售后。客户退款继续由商城售后审核，不会因上游申请成功自动向客户退款。" type="info" :closable="false" show-icon />
    <el-alert v-if="error" :title="error" type="error" :closable="false" show-icon />
    <el-button :loading="loading" @click="load()">刷新采购售后</el-button>
    <el-table v-loading="loading" :data="rows" row-key="id" empty-text="暂无供应商售后记录">
      <el-table-column prop="id" label="ID" width="75" /><el-table-column prop="after_sale_id" label="商城售后 ID" width="130" /><el-table-column prop="supply_order_id" label="采购单 ID" width="120" />
      <el-table-column prop="external_after_sale_id" label="上游售后编号" min-width="180" /><el-table-column label="状态" width="140"><template #default="{ row }"><el-tag :type="statusType(row.status)">{{ statusLabel(row.status) }}</el-tag></template></el-table-column>
      <el-table-column prop="last_error" label="失败原因" min-width="220" /><el-table-column label="操作" min-width="260"><template #default="{ row }">
        <el-button link type="primary" @click="open(row as AfterSale)">售后详情</el-button>
        <el-button v-if="hasAuth('supply-chain:after-sales/submit') && row.status === 'pending_review'" link type="primary" @click="open(row as AfterSale, true)">申请采购售后</el-button>
        <el-button v-if="hasAuth('supply-chain:after-sales/query')" link type="primary" :disabled="busy" @click="query(row as AfterSale)">查询上游售后</el-button>
      </template></el-table-column>
    </el-table>
    <el-pagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next" @current-change="load()" />
    <el-drawer v-model="detailOpen" :title="submitting ? '申请采购售后' : '供应商售后详情'" size="min(850px, 100%)" :close-on-click-modal="!busy">
      <div v-loading="detailLoading">
        <el-alert v-if="detailError" :title="detailError" type="error" :closable="false" show-icon />
        <template v-if="current">
          <p>商城售后 #{{ current.after_sale_id }} · 采购单 #{{ current.supply_order_id }} · {{ statusLabel(current.status) }}</p>
          <el-alert v-if="current.candidate_external_after_sale_id" type="warning" :closable="false" show-icon data-testid="after-sale-candidate">
            <template #title>查到候选售后编号：{{ current.candidate_external_after_sale_id }}</template>
            此编号仅供人工向供应商核对归属，尚未认领为本次售后。结果未知期间不能重复申请，也不会按此候选号发起取消或退货。
          </el-alert>
          <template v-for="order in current.external_orders || []" :key="order.external_order_no">
            <h4>上游订单 {{ order.external_order_no }}</h4>
            <el-table :data="order.items"><el-table-column prop="external_order_item_id" label="上游订单行 ID" /><el-table-column prop="external_sku_id" label="上游 SKU" /><el-table-column prop="quantity" label="数量" width="75" /><el-table-column label="金额" width="100"><template #default="{ row }">{{ money(row.amount_cents) }}</template></el-table-column><el-table-column v-if="submitting" label="操作" width="85"><template #default="{ row }"><el-button link @click="form.external_order_item_id = String(row.external_order_item_id)">选用</el-button></template></el-table-column></el-table>
          </template>
          <el-collapse v-if="current.snapshot"><el-collapse-item title="售后业务快照"><pre>{{ safeJson(current.snapshot) }}</pre></el-collapse-item></el-collapse>
          <template v-if="submitting">
            <h3>上游售后政策</h3><p>请按当前供应商返回的政策填写编码，不支持换货（退款类型 2）。</p>
            <el-button :disabled="busy || !form.external_order_item_id.trim()" data-testid="load-after-sale-policy" @click="loadPolicy">读取该订单行售后政策</el-button>
            <pre v-if="policyLoaded" data-testid="after-sale-policy">{{ safeJson(policy) }}</pre>
            <el-form label-position="top" :disabled="busy" class="after-sale-form">
              <el-form-item label="上游订单行 ID" required><el-input v-model="form.external_order_item_id" data-testid="after-sale-item" /></el-form-item>
              <el-form-item label="申请数量" required><el-input-number v-model="form.quantity" :min="1" :precision="0" data-testid="after-sale-quantity" /></el-form-item>
              <el-form-item label="商品退款金额（分）" required><el-input-number v-model="form.amount_cents" :min="0" :precision="0" data-testid="after-sale-amount" /></el-form-item>
              <el-form-item label="运费退款金额（分）"><el-input-number v-model="form.freight_cents" :min="0" :precision="0" /></el-form-item>
              <el-form-item label="技术服务费（分）"><el-input-number v-model="form.technical_service_fee_cents" :min="0" :precision="0" /></el-form-item>
              <el-form-item label="原因类型编码（按上游政策）" required><el-input-number v-model="form.reason_type" :min="0" :precision="0" data-testid="after-sale-reason-type" /></el-form-item>
              <el-form-item label="收货状态编码（按上游政策）" required><el-input-number v-model="form.received_status" :min="0" :precision="0" data-testid="after-sale-received" /></el-form-item>
              <el-form-item label="退款类型编码（按上游政策）" required><el-input-number v-model="form.refund_type" :min="0" :precision="0" data-testid="after-sale-refund-type" /></el-form-item>
              <el-form-item label="退款方式编码（按上游政策）" required><el-input-number v-model="form.refund_way" :min="0" :precision="0" data-testid="after-sale-refund-way" /></el-form-item>
              <el-form-item label="售后原因" required><el-input v-model="form.reason" maxlength="500" data-testid="after-sale-reason" /></el-form-item>
              <el-form-item label="补充说明" class="wide"><el-input v-model="form.description" type="textarea" :rows="3" maxlength="2000" /></el-form-item>
            </el-form>
            <el-button type="primary" :loading="busy" :disabled="!policyLoaded" data-testid="submit-after-sale" @click="submit">提交供应商售后任务</el-button>
          </template>
        </template>
      </div>
    </el-drawer>
  </section>
</template>

<style scoped>
.el-alert { margin-bottom: 16px; }
.el-pagination { margin-top: 20px; justify-content: flex-end; }
.after-sale-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 20px; margin-top: 20px; }
.wide { grid-column: 1 / -1; }
pre { padding: 14px; background: var(--el-fill-color-light); border-radius: 6px; white-space: pre-wrap; overflow-wrap: anywhere; max-height: 350px; overflow-y: auto; }
@media (max-width: 600px) { .after-sale-form { grid-template-columns: 1fr; } }
</style>
