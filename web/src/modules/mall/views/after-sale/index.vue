<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { AfterSaleStatus, AfterSaleType, AfterSaleVo } from '~/mall/api/after-sale'

import {
  afterSaleApprove,
  afterSaleCompleteExchange,
  afterSaleDetail,
  afterSalePage,
  afterSaleReceive,
  afterSaleRefund,
  afterSaleReject,
  afterSaleReship,
} from '~/mall/api/after-sale'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import { useMessage } from '@/hooks/useMessage'
import { ResultCode } from '@/utils/ResultCode'

defineOptions({ name: 'mall:after-sale' })

const msg = useMessage()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const drawerVisible = ref(false)
const detailLoading = ref(false)
const currentRow = ref<AfterSaleVo | null>(null)
const reviewDialogVisible = ref(false)
const reviewAction = ref<'approve' | 'reject'>('approve')
const reviewLoading = ref(false)
const reviewRow = ref<AfterSaleVo | null>(null)
const reviewForm = reactive({
  approved_refund_amount: 0,
  reject_reason: '',
  remark: '',
})
const reshipDialogVisible = ref(false)
const reshipLoading = ref(false)
const reshipRow = ref<AfterSaleVo | null>(null)
const reshipForm = reactive({
  logistics_company: '',
  logistics_no: '',
})

const typeTextMap: Record<AfterSaleType, string> = {
  refund_only: '仅退款',
  return_refund: '退货退款',
  exchange: '换货',
}

const statusTextMap: Record<AfterSaleStatus, string> = {
  pending_review: '待审核',
  waiting_buyer_return: '待买家退货',
  waiting_seller_receive: '待商家收货',
  waiting_refund: '待退款',
  refunding: '退款中',
  waiting_reship: '待补发',
  reshipped: '已补发',
  completed: '已完成',
  closed: '已关闭',
}

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 140,
  header: {
    mainTitle: () => '售后管理',
    subTitle: () => '集中处理退款、退货退款与换货申请',
  },
  searchOptions: {
    fold: false,
    text: {
      searchBtn: () => '搜索',
      resetBtn: () => '重置',
    },
  },
  searchFormOptions: {
    labelWidth: '100px',
  },
  requestOptions: {
    api: afterSalePage,
  },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(
    handleView,
    handleApprove,
    handleReject,
    handleReceive,
    handleRefund,
    handleReship,
    handleCompleteExchange,
  ),
})

function formatYuan(amount?: number): string {
  return `￥${((amount ?? 0) / 100).toFixed(2)}`
}

function buildTimeline(row: AfterSaleVo): Array<{
  title: string
  time?: string
  type?: 'primary' | 'success' | 'warning' | 'danger' | 'info'
  description?: string
}> {
  const items: Array<{
    title: string
    time?: string
    type?: 'primary' | 'success' | 'warning' | 'danger' | 'info'
    description?: string
  }> = [
    {
      title: '用户提交售后申请',
      time: row.created_at,
      type: 'primary',
      description: `申请原因：${row.reason || '--'}`,
    },
  ]

  if (row.status !== 'pending_review') {
    items.push({
      title: row.status === 'closed' ? '售后申请已关闭' : '售后申请审核通过',
      time: row.updated_at,
      type: row.status === 'closed' ? 'danger' : 'success',
      description: row.status === 'closed' ? '当前售后申请已结束' : '售后单已进入后续处理流程',
    })
  }

  if (row.buyer_return_logistics_no) {
    items.push({
      title: '买家已回寄商品',
      time: row.updated_at,
      type: 'info',
      description: `${row.buyer_return_logistics_company || '--'} / ${row.buyer_return_logistics_no}`,
    })
  }

  if (row.status === 'waiting_refund' || row.status === 'refunding' || row.status === 'completed') {
    items.push({
      title: row.status === 'completed' ? '退款已完成' : '退款处理中',
      time: row.updated_at,
      type: row.status === 'completed' ? 'success' : 'warning',
      description: `退款金额：${formatYuan(row.refund_amount)}`,
    })
  }

  if (row.reship_logistics_no) {
    items.push({
      title: row.status === 'completed' ? '换货流程已完成' : '商家已补发商品',
      time: row.updated_at,
      type: row.status === 'completed' ? 'success' : 'warning',
      description: `${row.reship_logistics_company || '--'} / ${row.reship_logistics_no}`,
    })
  }

  return items
}

function resetReviewForm(row: AfterSaleVo) {
  reviewForm.approved_refund_amount = row.apply_amount ?? 0
  reviewForm.reject_reason = ''
  reviewForm.remark = ''
}

function resetReshipForm() {
  reshipForm.logistics_company = ''
  reshipForm.logistics_no = ''
}

async function loadDetail(id: number) {
  detailLoading.value = true
  try {
    const res = await afterSaleDetail(id)
    currentRow.value = res.data
  }
  catch (error: any) {
    msg.alertError(error)
  }
  finally {
    detailLoading.value = false
  }
}

async function refreshCurrentDetail(id: number) {
  await proTableRef.value?.refresh()
  if (drawerVisible.value && currentRow.value?.id === id) {
    await loadDetail(id)
  }
}

async function handleView(row: AfterSaleVo) {
  drawerVisible.value = true
  currentRow.value = row
  await loadDetail(row.id)
}

function handleApprove(row: AfterSaleVo) {
  reviewRow.value = row
  reviewAction.value = 'approve'
  resetReviewForm(row)
  reviewDialogVisible.value = true
}

function handleReject(row: AfterSaleVo) {
  reviewRow.value = row
  reviewAction.value = 'reject'
  resetReviewForm(row)
  reviewDialogVisible.value = true
}

async function handleReceive(row: AfterSaleVo) {
  try {
    await msg.confirm(`确认已收到售后单 ${row.after_sale_no} 的退货商品吗？`)
    const res = await afterSaleReceive(row.id)
    if (res.code === ResultCode.SUCCESS) {
      msg.success('确认收货成功')
      await refreshCurrentDetail(row.id)
    }
  }
  catch (error: any) {
    if (error !== 'cancel') {
      msg.alertError(error)
    }
  }
}

async function handleRefund(row: AfterSaleVo) {
  try {
    await msg.confirm(`确认已完成售后单 ${row.after_sale_no} 的退款吗？`)
    const res = await afterSaleRefund(row.id)
    if (res.code === ResultCode.SUCCESS) {
      msg.success('确认退款成功')
      await refreshCurrentDetail(row.id)
    }
  }
  catch (error: any) {
    if (error !== 'cancel') {
      msg.alertError(error)
    }
  }
}

function handleReship(row: AfterSaleVo) {
  reshipRow.value = row
  resetReshipForm()
  reshipDialogVisible.value = true
}

async function handleCompleteExchange(row: AfterSaleVo) {
  try {
    await msg.confirm(`确认售后单 ${row.after_sale_no} 的换货流程已完成吗？`)
    const res = await afterSaleCompleteExchange(row.id)
    if (res.code === ResultCode.SUCCESS) {
      msg.success('确认换货完成成功')
      await refreshCurrentDetail(row.id)
    }
  }
  catch (error: any) {
    if (error !== 'cancel') {
      msg.alertError(error)
    }
  }
}

async function submitReview() {
  if (!reviewRow.value) {
    return
  }

  if (reviewAction.value === 'reject' && !reviewForm.reject_reason.trim()) {
    msg.warning('请输入拒绝原因')
    return
  }

  reviewLoading.value = true
  try {
    const request = reviewAction.value === 'approve'
      ? afterSaleApprove(reviewRow.value.id, {
          approved_refund_amount: reviewForm.approved_refund_amount,
          remark: reviewForm.remark,
        })
      : afterSaleReject(reviewRow.value.id, {
          reject_reason: reviewForm.reject_reason,
          remark: reviewForm.remark,
        })

    const res = await request
    if (res.code === ResultCode.SUCCESS) {
      msg.success(reviewAction.value === 'approve' ? '审核通过成功' : '审核拒绝成功')
      reviewDialogVisible.value = false
      await refreshCurrentDetail(reviewRow.value.id)
    }
  }
  catch (error: any) {
    msg.alertError(error)
  }
  finally {
    reviewLoading.value = false
  }
}

async function submitReship() {
  if (!reshipRow.value) {
    return
  }

  if (!reshipForm.logistics_company.trim()) {
    msg.warning('请输入补发物流公司')
    return
  }

  if (!reshipForm.logistics_no.trim()) {
    msg.warning('请输入补发物流单号')
    return
  }

  reshipLoading.value = true
  try {
    const res = await afterSaleReship(reshipRow.value.id, {
      logistics_company: reshipForm.logistics_company,
      logistics_no: reshipForm.logistics_no,
    })

    if (res.code === ResultCode.SUCCESS) {
      msg.success('补发成功')
      reshipDialogVisible.value = false
      await refreshCurrentDetail(reshipRow.value.id)
    }
  }
  catch (error: any) {
    msg.alertError(error)
  }
  finally {
    reshipLoading.value = false
  }
}
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #empty>
        <el-empty description="暂无售后数据" />
      </template>
    </MaProTable>

    <el-drawer v-model="drawerVisible" title="售后详情" size="720px" destroy-on-close>
      <el-skeleton :loading="detailLoading" animated>
        <template #default>
          <div v-if="currentRow" class="space-y-4">
            <el-card shadow="never">
              <template #header>
                <span>基础信息</span>
              </template>
              <el-descriptions :column="2" border>
                <el-descriptions-item label="售后单号">{{ currentRow.after_sale_no }}</el-descriptions-item>
                <el-descriptions-item label="订单号">{{ currentRow.order_no || '--' }}</el-descriptions-item>
                <el-descriptions-item label="售后类型">{{ typeTextMap[currentRow.type] }}</el-descriptions-item>
                <el-descriptions-item label="售后状态">{{ statusTextMap[currentRow.status] }}</el-descriptions-item>
                <el-descriptions-item label="申请金额">{{ formatYuan(currentRow.apply_amount) }}</el-descriptions-item>
                <el-descriptions-item label="退款金额">{{ formatYuan(currentRow.refund_amount) }}</el-descriptions-item>
                <el-descriptions-item label="申请数量">{{ currentRow.quantity }}</el-descriptions-item>
                <el-descriptions-item label="会员ID">{{ currentRow.member_id }}</el-descriptions-item>
                <el-descriptions-item label="申请原因" :span="2">{{ currentRow.reason }}</el-descriptions-item>
                <el-descriptions-item label="问题描述" :span="2">{{ currentRow.description || '--' }}</el-descriptions-item>
              </el-descriptions>
            </el-card>

            <el-card shadow="never">
              <template #header>
                <span>商品信息</span>
              </template>
              <div class="flex items-start gap-3">
                <el-image :src="currentRow.product?.productImage" fit="cover" class="h-20 w-20 rounded-md border border-[var(--el-border-color)]">
                  <template #error>
                    <div class="flex h-20 w-20 items-center justify-center text-xs text-gray-400">无图</div>
                  </template>
                </el-image>
                <div class="flex-1">
                  <div class="font-medium">{{ currentRow.product?.productName || '--' }}</div>
                  <div class="mt-1 text-sm text-gray-500">{{ currentRow.product?.skuName || '--' }}</div>
                </div>
              </div>
            </el-card>

            <el-card shadow="never">
              <template #header>
                <span>物流信息</span>
              </template>
              <el-descriptions :column="2" border>
                <el-descriptions-item label="买家退货物流公司">{{ currentRow.buyer_return_logistics_company || '--' }}</el-descriptions-item>
                <el-descriptions-item label="买家退货单号">{{ currentRow.buyer_return_logistics_no || '--' }}</el-descriptions-item>
                <el-descriptions-item label="商家补发物流公司">{{ currentRow.reship_logistics_company || '--' }}</el-descriptions-item>
                <el-descriptions-item label="商家补发单号">{{ currentRow.reship_logistics_no || '--' }}</el-descriptions-item>
              </el-descriptions>
            </el-card>

            <el-card v-if="currentRow.images?.length" shadow="never">
              <template #header>
                <span>凭证图片</span>
              </template>
              <div class="flex flex-wrap gap-3">
                <el-image
                  v-for="item in currentRow.images"
                  :key="item"
                  :src="item"
                  fit="cover"
                  class="h-24 w-24 rounded-md border border-[var(--el-border-color)]"
                  preview-teleported
                  :preview-src-list="currentRow.images"
                />
              </div>
            </el-card>

            <el-card shadow="never">
              <template #header>
                <span>处理时间线</span>
              </template>
              <el-timeline>
                <el-timeline-item
                  v-for="(item, index) in buildTimeline(currentRow)"
                  :key="`${item.title}-${index}`"
                  :timestamp="item.time || '--'"
                  :type="item.type"
                >
                  <div class="font-medium">{{ item.title }}</div>
                  <div v-if="item.description" class="mt-1 text-sm text-gray-500">{{ item.description }}</div>
                </el-timeline-item>
              </el-timeline>
            </el-card>
          </div>
        </template>
      </el-skeleton>
    </el-drawer>

    <el-dialog v-model="reviewDialogVisible" :title="reviewAction === 'approve' ? '审核通过' : '审核拒绝'" width="520px" destroy-on-close>
      <el-form label-width="110px">
        <el-form-item v-if="reviewAction === 'approve'" label="退款金额">
          <el-input-number v-model="reviewForm.approved_refund_amount" :min="0" :step="100" class="w-full" />
          <div class="mt-1 text-xs text-gray-400">单位：分</div>
        </el-form-item>
        <el-form-item v-if="reviewAction === 'reject'" label="拒绝原因" required>
          <el-input
            v-model="reviewForm.reject_reason"
            type="textarea"
            :rows="3"
            maxlength="200"
            show-word-limit
            placeholder="请输入拒绝原因"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="reviewForm.remark"
            type="textarea"
            :rows="3"
            maxlength="200"
            show-word-limit
            placeholder="请输入审核备注"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="reviewDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="reviewLoading" @click="submitReview">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="reshipDialogVisible" title="填写补发物流" width="520px" destroy-on-close>
      <el-form label-width="110px">
        <el-form-item label="物流公司" required>
          <el-input v-model="reshipForm.logistics_company" maxlength="100" placeholder="请输入补发物流公司" />
        </el-form-item>
        <el-form-item label="物流单号" required>
          <el-input v-model="reshipForm.logistics_no" maxlength="100" placeholder="请输入补发物流单号" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="reshipDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="reshipLoading" @click="submitReship">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>
