<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <div class="member-wallet-page p-3">
    <el-card shadow="never" class="mb-4">
      <el-form :model="filters" label-width="90px" inline>
        <el-form-item label="会员ID">
          <el-input v-model.number="filters.member_id" placeholder="请输入会员ID" clearable @keyup.enter="loadLogs">
            <template #prefix><el-icon><User /></el-icon></template>
          </el-input>
        </el-form-item>
        <el-form-item label="钱包类型">
          <el-select v-model="filters.wallet_type" placeholder="全部" class="w-40" @change="loadLogs">
            <el-option label="余额钱包" value="balance" />
            <el-option label="积分钱包" value="points" />
          </el-select>
        </el-form-item>
        <el-form-item label="来源">
          <el-select
            v-model="filters.source"
            placeholder="全部"
            clearable
            filterable
            allow-create
            class="w-40"
            @change="loadLogs"
          >
            <el-option
              v-for="option in sourceOptions"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="操作类型">
          <el-select v-model="filters.operator_type" placeholder="全部" clearable class="w-36" @change="loadLogs">
            <el-option label="系统" value="system" />
            <el-option label="管理员" value="admin" />
            <el-option label="会员" value="member" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            value-format="YYYY-MM-DD"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            @change="handleDateChange"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadLogs">
            <template #icon><el-icon><Search /></el-icon></template>
            搜索
          </el-button>
          <el-button @click="resetFilters">
            <template #icon><el-icon><Refresh /></el-icon></template>
            重置
          </el-button>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="openAdjust">
            <template #icon><el-icon><Coin /></el-icon></template>
            调整钱包
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="logList" v-loading="loading" border stripe>
        <el-table-column type="index" width="60" label="#" />
        <el-table-column label="会员ID" prop="member_id" width="100" />
        <el-table-column label="钱包类型" width="120">
          <template #default="{ row }">
            <el-tag size="small" :type="row.wallet_type === 'balance' ? 'primary' : 'success'">
              {{ walletTypeLabel(row.wallet_type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="变动金额" width="140">
          <template #default="{ row }">
            <span :class="isIncome(row.type) ? 'text-green-600' : 'text-red-500'">
              {{ isIncome(row.type) ? '+' : '-' }}{{ row.wallet_type === 'balance' ? formatYuan(row.amount) : row.amount }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="变动前/后" min-width="220">
          <template #default="{ row }">
            <template v-if="row.wallet_type === 'balance'">
              {{ formatYuan(row.balance_before) }} → {{ formatYuan(row.balance_after) }}
            </template>
            <template v-else>
              {{ row.balance_before }} → {{ row.balance_after }}
            </template>
          </template>
        </el-table-column>
        <el-table-column label="来源" prop="source" width="140" />
        <el-table-column label="操作人" width="160">
          <template #default="{ row }">
            {{ row.operator_name || row.operator_type }}
          </template>
        </el-table-column>
        <el-table-column label="备注" prop="remark" min-width="200" show-overflow-tooltip />
        <el-table-column label="时间" prop="created_at" width="180" />
      </el-table>

      <div class="flex justify-end mt-4">
        <el-pagination
          :current-page="pagination.page"
          :page-size="pagination.pageSize"
          :total="pagination.total"
          layout="total, prev, pager, next, jumper"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <el-dialog v-model="adjustDialogVisible" title="调整会员钱包" width="480px">
      <el-form ref="adjustFormRef" :model="adjustForm" :rules="adjustRules" label-width="90px">
        <el-form-item label="会员ID" prop="member_id">
          <el-input v-model="adjustForm.member_id" placeholder="请输入会员ID" />
        </el-form-item>
        <el-form-item label="钱包类型" prop="type">
          <el-select v-model="adjustForm.type" placeholder="请选择钱包类型" class="w-full">
            <el-option label="余额钱包" value="balance" />
            <el-option label="积分钱包" value="points" />
          </el-select>
        </el-form-item>
        <el-form-item label="变动金额" prop="value">
          <el-input-number
            v-model="adjustForm.value"
            :min="-1000000"
            :max="1000000"
            :precision="adjustForm.type === 'balance' ? 2 : 0"
            class="w-full"
          />
          <div v-if="adjustForm.type === 'balance'" class="text-xs text-gray-400 mt-1">单位：元</div>
        </el-form-item>
        <el-form-item label="来源" prop="source">
          <el-select
            v-model="adjustForm.source"
            placeholder="请选择来源"
            clearable
            filterable
            allow-create
            class="w-full"
          >
            <el-option
              v-for="option in sourceOptions"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="adjustForm.remark" type="textarea" rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="adjustLoading" @click="submitAdjust">
          <template #icon><el-icon><Check /></el-icon></template>
          提交
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { Check, Coin, Link, Refresh, Search, User } from '@element-plus/icons-vue'
import {
  memberAccountApi,
  type MemberAccountAdjustPayload,
  type MemberAccountLogParams,
  type MemberWalletLog,
} from '~/member/api/member'
import { formatYuan, yuanToCents } from '@/utils/price'

defineOptions({ name: 'member:wallet' })

const loading = ref(false)
const logList = ref<MemberWalletLog[]>([])
const filters = reactive<MemberAccountLogParams>({
  member_id: undefined,
  wallet_type: 'balance',
  source: '',
  operator_type: '',
  start_date: '',
  end_date: '',
})
const sourceOptions = [
  { label: '系统', value: 'system' },
  { label: '订单', value: 'order' },
  { label: '手动', value: 'manual' },
]
const dateRange = ref<[string, string] | null>(null)
const pagination = reactive({
  page: 1,
  pageSize: 15,
  total: 0,
})

const adjustDialogVisible = ref(false)
const adjustFormRef = ref<FormInstance>()
const adjustLoading = ref(false)
const adjustForm = reactive<MemberAccountAdjustPayload>({
  member_id: 0,
  value: 0,
  type: 'balance',
  source: 'manual',
  remark: '',
})

const adjustRules: FormRules = {
  member_id: [{ required: true, message: '请输入会员ID', trigger: 'blur' }],
  type: [{ required: true, message: '请选择钱包类型', trigger: 'change' }],
  value: [{ required: true, message: '请输入变动值', trigger: 'blur' }],
  source: [{ required: true, message: '请选择来源', trigger: ['change', 'blur'] }],
}

const buildParams = () => ({
  ...filters,
  page: pagination.page,
  page_size: pagination.pageSize,
})

const loadLogs = async () => {
  loading.value = true
  try {
    const res = await memberAccountApi.walletLogs(buildParams())
    logList.value = res.data.list
    pagination.total = res.data.total
  }
  catch (error: any) {
    ElMessage.error(error?.message || '加载失败')
  }
  finally {
    loading.value = false
  }
}

const handlePageChange = (page: number) => {
  pagination.page = page
  loadLogs()
}

const handleDateChange = (range: [string, string] | null) => {
  if (range) {
    filters.start_date = range[0]
    filters.end_date = range[1]
  }
  else {
    filters.start_date = ''
    filters.end_date = ''
  }
  loadLogs()
}

const resetFilters = () => {
  Object.assign(filters, {
    member_id: undefined,
    wallet_type: 'balance',
    source: '',
    operator_type: '',
    start_date: '',
    end_date: '',
  })
  dateRange.value = null
  pagination.page = 1
  loadLogs()
}

const openAdjust = () => {
  Object.assign(adjustForm, {
    member_id: filters.member_id ?? 0,
    type: filters.wallet_type || 'balance',
    value: 0,
    source: 'manual',
    remark: '',
  })
  adjustDialogVisible.value = true
}

const submitAdjust = async () => {
  if (!adjustFormRef.value) return
  await adjustFormRef.value.validate()
  adjustLoading.value = true
  try {
    const payload = { ...adjustForm }
    // 余额钱包：表单输入元，提交转换为分
    if (payload.type === 'balance') {
      payload.value = yuanToCents(payload.value)
    }
    await memberAccountApi.adjustWallet(payload)
    ElMessage.success('操作成功')
    adjustDialogVisible.value = false
    loadLogs()
  }
  catch (error: any) {
    ElMessage.error(error?.message || '操作失败')
  }
  finally {
    adjustLoading.value = false
  }
}

const isIncome = (type: string) => ['recharge', 'refund', 'adjust_in'].includes(type)
const walletTypeLabel = (type: string) => (type === 'points' ? '积分钱包' : '余额钱包')

loadLogs()
</script>
