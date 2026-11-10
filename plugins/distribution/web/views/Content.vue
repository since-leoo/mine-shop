<script setup lang="ts">
import type { DistributionMember, DistributionQuery, DistributionRow, DistributionWithdrawal, WithdrawalAction } from '../api/distribution'
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import hasRole from '@/utils/permission/hasRole'
import { getList, getOverview, getTeam, reviewWithdrawal } from '../api/distribution'
import Settings from './Settings.vue'
import { errorMessage, money, statusNames } from './format'

type Tab = 'overview' | 'members' | 'orders' | 'commissions' | 'withdrawals' | 'logs' | 'settings'
const definitions: Array<{ key: Tab, label: string }> = [
  { key: 'overview', label: '概览' },
  { key: 'members', label: '成员与团队' },
  { key: 'orders', label: '消费订单' },
  { key: 'commissions', label: '佣金明细' },
  { key: 'withdrawals', label: '提现审核' },
  { key: 'logs', label: '操作日志' },
  { key: 'settings', label: '佣金设置' },
]
const canAdmin = computed(() => hasRole('SuperAdmin'))
const tabs = computed(() => canAdmin.value ? definitions : [])
const active = ref<Tab>(tabs.value[0]?.key ?? 'overview')
const loading = ref(false)
const error = ref('')
const rows = ref<DistributionRow[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const overview = ref<Record<string, number>>({})
const filters = reactive({ member_id: undefined as number | undefined, scope: 'all', dates: [] as string[] })
const teamOwner = ref<DistributionMember>()
const canReadOrders = canAdmin
const canReview = canAdmin
const reviewing = ref<DistributionWithdrawal>()
const reviewAction = ref<WithdrawalAction>('approve')
const reviewForm = reactive({ remark: '', payment_no: '' })
const reviewVisible = ref(false)
const saving = ref(false)
const reviewError = ref('')
let requestVersion = 0

const metrics = [
  { key: 'member_count', label: '会员总数' },
  { key: 'invited_member_count', label: '受邀会员' },
  { key: 'order_count', label: '有效消费订单' },
  { key: 'net_consumption_amount', label: '团队净消费额', amount: true },
  { key: 'pending_commission_amount', label: '待结算佣金', amount: true },
  { key: 'available_commission', label: '可提现余额', amount: true },
  { key: 'frozen_commission', label: '提现冻结余额', amount: true },
  { key: 'debt_amount', label: '待追回佣金', amount: true },
  { key: 'settled_commission_amount', label: '累计已结算佣金', amount: true },
  { key: 'paid_withdrawal_amount', label: '累计已提现', amount: true },
]
const actionNames: Record<WithdrawalAction, string> = { approve: '审核通过', reject: '驳回申请', pay: '确认打款', fail: '登记打款失败' }
function query(): DistributionQuery {
  if (active.value === 'logs') {
    return { page: page.value, page_size: pageSize.value }
  }
  return { page: page.value, page_size: pageSize.value, member_id: active.value === 'overview' ? undefined : filters.member_id, scope: filters.scope,
    start_date: filters.dates?.[0], end_date: filters.dates?.[1] }
}
function logDetail(detail: string | Record<string, unknown>): string {
  if (typeof detail !== 'string') {
    return JSON.stringify(detail, null, 2)
  }
  try {
    return JSON.stringify(JSON.parse(detail), null, 2)
  }
  catch {
    return detail
  }
}
async function load() {
  const version = ++requestVersion
  if (active.value === 'settings' || !tabs.value.some(tab => tab.key === active.value)) {
    loading.value = false
    return
  }
  loading.value = true
  error.value = ''
  rows.value = []
  total.value = 0
  try {
    if (active.value === 'overview') {
      const { data } = await getOverview(query())
      if (version === requestVersion) {
        overview.value = data
      }
    }
    else {
      const { data } = active.value === 'members' && teamOwner.value
        ? await getTeam(teamOwner.value.id, query())
        : await getList(active.value, query())
      if (version === requestVersion) {
        rows.value = data.list ?? []
        total.value = data.total ?? 0
      }
    }
  }
  catch (failure) {
    if (version === requestVersion) {
      error.value = errorMessage(failure)
    }
  }
  finally {
    if (version === requestVersion) {
      loading.value = false
    }
  }
}
function search() {
  page.value = 1
  load()
}
function reset() {
  filters.member_id = undefined
  filters.scope = 'all'
  filters.dates = []
  teamOwner.value = undefined
  search()
}
function openTeam(row: DistributionMember) {
  teamOwner.value = row
  filters.member_id = undefined
  filters.scope = 'all'
  search()
}
function openOrders(id: number, scope = 'self') {
  filters.member_id = id
  filters.scope = scope
  page.value = 1
  if (active.value === 'orders') {
    load()
  }
  else {
    active.value = 'orders'
  }
}
function openReview(row: DistributionWithdrawal, action: WithdrawalAction) {
  reviewing.value = row
  reviewAction.value = action
  reviewForm.remark = ''
  reviewForm.payment_no = ''
  reviewError.value = ''
  reviewVisible.value = true
}
async function submitReview() {
  if (!reviewing.value || !canReview.value || saving.value) {
    return
  }
  if (reviewAction.value === 'pay' && !reviewForm.payment_no.trim()) {
    reviewError.value = '请填写真实打款流水号后再确认'
    return
  }
  if (['reject', 'fail'].includes(reviewAction.value) && !reviewForm.remark.trim()) {
    reviewError.value = '请填写处理原因'
    return
  }
  saving.value = true
  reviewError.value = ''
  try {
    await reviewWithdrawal(reviewing.value.id, { action: reviewAction.value, remark: reviewForm.remark.trim(), payment_no: reviewForm.payment_no.trim() || undefined })
    reviewVisible.value = false
    ElMessage.success(`${actionNames[reviewAction.value]}成功`)
    await load()
  }
  catch (failure) {
    reviewError.value = errorMessage(failure)
  }
  finally {
    saving.value = false
  }
}
watch(active, () => {
  page.value = 1
  teamOwner.value = undefined
  if (active.value === 'members' && filters.scope === 'self') {
    filters.scope = 'all'
  }
  load()
}, { immediate: true })
</script>

<template>
  <section class="distribution-content">
    <el-empty v-if="!tabs.length" description="分销管理仅限超级管理员" />
    <template v-else>
      <el-tabs v-model="active">
        <el-tab-pane v-for="tab in tabs" :key="tab.key" :name="tab.key" :label="tab.label" />
      </el-tabs>
      <Settings v-if="active === 'settings'" />
      <template v-else>
        <el-form v-if="active !== 'logs'" inline class="filters" @submit.prevent="search">
          <el-form-item v-if="active !== 'overview'" :label="active === 'members' ? '邀请人 ID' : '会员 ID'">
            <el-input-number v-model="filters.member_id" :min="1" :precision="0" :controls="false" placeholder="全部会员" data-testid="member-filter" />
          </el-form-item>
          <el-form-item v-if="active === 'members' || active === 'orders'" label="统计范围">
            <el-select v-model="filters.scope" style="width: 150px">
              <el-option v-if="active === 'orders'" label="会员本人" value="self" /><el-option label="直属粉丝" value="direct" /><el-option label="全部团队" value="all" />
            </el-select>
          </el-form-item>
          <el-form-item label="日期">
            <el-date-picker v-model="filters.dates" type="daterange" value-format="YYYY-MM-DD" start-placeholder="开始日期" end-placeholder="结束日期" />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" native-type="submit" :loading="loading">
              查询
            </el-button><el-button @click="reset">
              重置
            </el-button>
          </el-form-item>
        </el-form>
        <el-button v-else :loading="loading" @click="load">
          刷新操作日志
        </el-button>
        <p v-if="active !== 'logs'" class="hint">
          团队包含所有后代，不包含本人；净消费额扣除成功退款。只有买家的直属上级获得佣金。
        </p>
        <p v-if="active === 'overview'" class="hint">
          日期筛选用于消费订单及金额；会员人数为全量，佣金展示当前余额和累计金额。
        </p>
        <el-alert v-if="error" :title="error" type="error" show-icon :closable="false" />
        <div v-if="active === 'overview'" v-loading="loading" class="metrics">
          <article v-for="metric in metrics" :key="metric.key">
            <span>{{ metric.label }}</span><strong>{{ metric.amount ? money(overview[metric.key]) : (overview[metric.key] ?? '—') }}</strong>
          </article>
        </div>
        <template v-else>
          <div v-if="active === 'members' && teamOwner" class="team-heading">
            <strong>{{ teamOwner.nickname || `会员 ${teamOwner.id}` }} 的{{ filters.scope === 'direct' ? '直属粉丝' : '全部团队' }}</strong><el-button v-if="canReadOrders" link type="primary" @click="openOrders(teamOwner.id, filters.scope)">
              查看该团队全部订单
            </el-button><el-button link @click="reset">
              返回成员列表
            </el-button>
          </div>
          <el-table v-loading="loading" :data="rows" row-key="id" empty-text="暂无符合条件的数据" stripe>
            <template v-if="active === 'members'">
              <el-table-column prop="id" label="会员 ID" width="100" />
              <el-table-column prop="nickname" label="昵称" min-width="130" />
              <el-table-column label="上级" width="110">
                <template #default="{ row }">
                  {{ row.parent_id || '平台' }}
                </template>
              </el-table-column>
              <el-table-column prop="invite_code" label="邀请码" width="130" />
              <el-table-column label="直属粉丝" width="110">
                <template #default="{ row }">
                  {{ row.direct_count ?? 0 }} / 3
                </template>
              </el-table-column>
              <el-table-column prop="team_count" label="团队人数" width="110" />
              <el-table-column label="本人净消费额" width="130">
                <template #default="{ row }">
                  {{ money(row.net_consumption_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="本人有效订单" width="130">
                <template #default="{ row }">
                  <el-button v-if="canReadOrders" link type="primary" @click="openOrders(row.id)">
                    {{ row.order_count ?? 0 }}
                  </el-button><span v-else>{{ row.order_count ?? 0 }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="created_at" label="注册时间" width="170" />
              <el-table-column label="操作" fixed="right" width="100">
                <template #default="{ row }">
                  <el-button link type="primary" @click="openTeam(row as DistributionMember)">
                    查看团队
                  </el-button>
                </template>
              </el-table-column>
            </template>
            <template v-else-if="active === 'orders'">
              <el-table-column prop="order_no" label="订单编号" min-width="180" />
              <el-table-column prop="member_id" label="消费会员" width="100" />
              <el-table-column prop="member_nickname" label="昵称" min-width="120" />
              <el-table-column prop="product_summary" label="商品摘要" min-width="190" show-overflow-tooltip />
              <el-table-column label="实付金额" width="120">
                <template #default="{ row }">
                  {{ money(row.paid_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="退款金额" width="120">
                <template #default="{ row }">
                  {{ money(row.refund_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="净消费额" width="120">
                <template #default="{ row }">
                  {{ money(row.net_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="状态" width="100">
                <template #default="{ row }">
                  {{ statusNames[row.status] || row.status }}
                </template>
              </el-table-column>
              <el-table-column prop="paid_at" label="支付时间" width="170" />
            </template>
            <template v-else-if="active === 'commissions'">
              <el-table-column prop="member_id" label="佣金获得人" width="120" />
              <el-table-column prop="order_no" label="来源订单" min-width="180" />
              <el-table-column label="原始佣金" width="120">
                <template #default="{ row }">
                  {{ money(row.amount) }}
                </template>
              </el-table-column>
              <el-table-column label="退款冲回" width="120">
                <template #default="{ row }">
                  {{ money(row.refunded_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="净佣金" width="120">
                <template #default="{ row }">
                  {{ money(row.net_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="状态" width="100">
                <template #default="{ row }">
                  <el-tag :type="row.status === 'settled' ? 'success' : 'info'">
                    {{ row.status === 'pending' ? '待结算' : statusNames[row.status] || row.status }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="created_at" label="生成时间" width="170" /><el-table-column prop="settled_at" label="结算时间" width="170" />
            </template>
            <template v-else-if="active === 'withdrawals'">
              <el-table-column prop="withdrawal_no" label="提现单号" min-width="180" /><el-table-column prop="member_id" label="会员 ID" width="100" />
              <el-table-column label="申请金额" width="120">
                <template #default="{ row }">
                  {{ money(row.amount) }}
                </template>
              </el-table-column>
              <el-table-column label="手续费" width="110">
                <template #default="{ row }">
                  {{ money(row.fee_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="实付金额" width="120">
                <template #default="{ row }">
                  {{ money(row.net_amount) }}
                </template>
              </el-table-column>
              <el-table-column prop="channel" label="渠道" width="100" /><el-table-column prop="account_name" label="收款人" width="100" /><el-table-column prop="account_no" label="收款账号" min-width="160" />
              <el-table-column label="状态" width="100">
                <template #default="{ row }">
                  <el-tag>{{ row.status === 'pending' ? '待审核' : row.status === 'paid' ? '已打款' : statusNames[row.status] || row.status }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="payment_no" label="打款流水" min-width="160" /><el-table-column prop="remark" label="处理备注" min-width="150" show-overflow-tooltip /><el-table-column prop="created_at" label="申请时间" width="170" />
              <el-table-column v-if="canReview" label="操作" fixed="right" width="240">
                <template #default="{ row }">
                  <template v-if="row.status === 'pending'">
                    <el-button link type="primary" @click="openReview(row as DistributionWithdrawal, 'approve')">
                      通过
                    </el-button><el-button link type="danger" @click="openReview(row as DistributionWithdrawal, 'reject')">
                      驳回
                    </el-button>
                  </template><template v-else-if="row.status === 'approved'">
                    <el-button link type="primary" @click="openReview(row as DistributionWithdrawal, 'pay')">
                      确认打款
                    </el-button><el-button link type="danger" @click="openReview(row as DistributionWithdrawal, 'fail')">
                      打款失败
                    </el-button><el-button link type="warning" @click="openReview(row as DistributionWithdrawal, 'reject')">
                      撤销审核
                    </el-button>
                  </template><span v-else>—</span>
                </template>
              </el-table-column>
            </template>
            <template v-else-if="active === 'logs'">
              <el-table-column prop="id" label="日志 ID" width="100" />
              <el-table-column prop="operator_id" label="操作人 ID" width="120" />
              <el-table-column prop="action" label="操作" min-width="170" />
              <el-table-column prop="subject_id" label="对象 ID" width="120" />
              <el-table-column label="操作详情" min-width="280">
                <template #default="{ row }">
                  <pre class="log-detail">{{ logDetail(row.detail) }}</pre>
                </template>
              </el-table-column>
              <el-table-column prop="created_at" label="操作时间" width="180" />
            </template>
          </el-table>
          <el-pagination v-model:current-page="page" v-model:page-size="pageSize" :total="total" :page-sizes="[20, 50, 100]" layout="total, sizes, prev, pager, next" class="pagination" @current-change="load" @size-change="search" />
        </template>
      </template>
    </template>
    <el-dialog v-model="reviewVisible" :title="actionNames[reviewAction]" width="500px" :close-on-click-modal="false" :close-on-press-escape="!saving" :show-close="!saving" append-to-body>
      <template v-if="reviewing">
        <el-alert v-if="reviewError" :title="reviewError" type="error" :closable="false" />
        <p>提现单 {{ reviewing.withdrawal_no }} · 实付 {{ money(reviewing.net_amount) }}</p>
        <p class="hint">
          {{ reviewing.account_name }} · {{ reviewing.account_no }}（{{ reviewing.channel }}）
        </p>
        <el-alert v-if="reviewAction === 'pay'" title="请在完成真实转账后填写流水号；本操作仅登记打款结果，不自动转账。" type="warning" :closable="false" />
        <el-form label-position="top" :disabled="saving" @submit.prevent="submitReview">
          <el-form-item v-if="reviewAction === 'pay'" label="打款流水号" required>
            <el-input v-model="reviewForm.payment_no" maxlength="100" />
          </el-form-item>
          <el-form-item label="处理说明" :required="['reject', 'fail'].includes(reviewAction)">
            <el-input v-model="reviewForm.remark" type="textarea" maxlength="500" show-word-limit />
          </el-form-item>
        </el-form>
      </template>
      <template #footer>
        <el-button :disabled="saving" @click="reviewVisible = false">
          取消
        </el-button><el-button type="primary" :loading="saving" @click="submitReview">
          {{ actionNames[reviewAction] }}
        </el-button>
      </template>
    </el-dialog>
  </section>
</template>

<style scoped>
.distribution-content { padding: 16px 20px; border-radius: 12px; background: var(--el-bg-color); }
.filters { margin-top: 8px; }
.hint { color: var(--el-text-color-secondary); font-size: 13px; line-height: 1.7; }
.metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; padding: 16px 0; }
.metrics article { display: flex; flex-direction: column; gap: 16px; padding: 24px; border: 1px solid var(--el-border-color-lighter); border-radius: 12px; }
.metrics span { color: var(--el-text-color-secondary); font-size: 14px; }
.metrics strong { font-size: 27px; font-variant-numeric: tabular-nums; }
.team-heading { display: flex; align-items: center; flex-wrap: wrap; gap: 16px; padding: 18px 0; }
.pagination { margin-top: 20px; justify-content: flex-end; overflow: auto; }
.log-detail { margin: 0; white-space: pre-wrap; word-break: break-word; font-size: 12px; }
</style>
