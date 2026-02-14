<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<template>
  <div class="order-page">
    <el-row :gutter="16" class="mb-4">
      <el-col v-for="card in statCards" :key="card.key" :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div :class="['stat-icon', card.key]">
              <el-icon :size="24">
                <component :is="card.icon" />
              </el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats[card.key] ?? 0 }}</div>
              <div class="stat-label">{{ card.label }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never" class="mb-4">
      <el-form label-width="90px" :model="filters">
        <el-row :gutter="16">
          <el-col :span="6">
            <el-form-item :label="t('mall.order.orderNo')">
              <el-input v-model="filters.order_no" :placeholder="t('mall.order.orderNoPlaceholder')" clearable @keyup.enter="handleSearch">
                <template #prefix>
                  <el-icon><Document /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('mall.order.payNo')">
              <el-input v-model="filters.pay_no" :placeholder="t('mall.order.payNoPlaceholder')" clearable @keyup.enter="handleSearch">
                <template #prefix>
                  <el-icon><Tickets /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('mall.order.memberId')">
              <el-input v-model="filters.member_id" :placeholder="t('mall.order.memberIdPlaceholder')" clearable @keyup.enter="handleSearch">
                <template #prefix>
                  <el-icon><User /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('mall.order.phone')">
              <el-input v-model="filters.member_phone" :placeholder="t('mall.order.phonePlaceholder')" clearable @keyup.enter="handleSearch">
                <template #prefix>
                  <el-icon><Phone /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="6">
            <el-form-item :label="t('mall.order.productNameLabel')">
              <el-input v-model="filters.product_name" :placeholder="t('mall.order.productNamePlaceholder')" clearable @keyup.enter="handleSearch">
                <template #prefix>
                  <el-icon><Goods /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('mall.order.orderStatus')">
              <el-select v-model="filters.status" clearable :placeholder="t('mall.allStatus')" class="w-full" @change="handleSearch">
                <el-option value="pending" :label="t('mall.order.statusPending')" />
                <el-option value="paid" :label="t('mall.order.statusPaid')" />
                <el-option value="shipped" :label="t('mall.order.statusShipped')" />
                <el-option value="completed" :label="t('mall.order.statusCompleted')" />
                <el-option value="cancelled" :label="t('mall.order.statusCancelled')" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('mall.order.payStatus')">
              <el-select v-model="filters.pay_status" clearable :placeholder="t('mall.allStatus')" class="w-full" @change="handleSearch">
                <el-option value="pending" :label="t('mall.order.payPending')" />
                <el-option value="paid" :label="t('mall.order.payPaid')" />
                <el-option value="failed" :label="t('mall.order.payFailed')" />
                <el-option value="cancelled" :label="t('mall.order.statusCancelled')" />
                <el-option value="refunded" :label="t('mall.order.payRefunded')" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item :label="t('mall.order.orderTime')">
              <el-date-picker
                v-model="dateRange"
                type="daterange"
                value-format="YYYY-MM-DD"
                :range-separator="t('dashboard.dateRange.to')"
                :start-placeholder="t('mall.startDate')"
                :end-placeholder="t('mall.endDate')"
                class="w-full"
                @change="handleDateChange"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <div class="text-right">
          <el-button type="primary" @click="handleSearch">
            <template #icon><el-icon><Search /></el-icon></template>
            {{ t('mall.search') }}
          </el-button>
          <el-button @click="resetFilters">
            <template #icon><el-icon><Refresh /></el-icon></template>
            {{ t('mall.reset') }}
          </el-button>
          <el-button @click="handleExport">
            <template #icon><el-icon><Download /></el-icon></template>
            {{ t('mall.export') }}
          </el-button>
        </div>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <div class="flex items-center justify-between">
          <span class="font-medium">{{ t('mall.order.orderList') }}</span>
          <el-button size="small" @click="loadData">
            <template #icon><el-icon><Refresh /></el-icon></template>
            {{ t('mall.refresh') }}
          </el-button>
        </div>
      </template>

      <el-table
        :data="orderList"
        border
        stripe
        row-key="id"
        v-loading="loading"
        :header-cell-style="{ background: '#f5f7fa', textAlign: 'center' }"
        :cell-style="{ textAlign: 'center' }"
      >
        <el-table-column type="index" :label="t('mall.order.index')" width="60" fixed />
        <el-table-column :label="t('mall.order.orderNo')" width="180" fixed>
          <template #default="{ row }">
            <el-tag type="primary" size="small">{{ row.order_no }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.payNo')" width="190" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.pay_no || '-' }}
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.user')" width="150">
          <template #default="{ row }">
            <div class="flex items-center justify-center gap-1">
              <el-icon><User /></el-icon>
              <span>{{ row.address?.name || row.member?.nickname || '-' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.phone')" width="130">
          <template #default="{ row }">
            {{ row.address?.phone || row.member?.phone || '-' }}
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.productInfo')" min-width="220">
          <template #default="{ row }">
            <div v-if="row.items?.length" class="flex items-center justify-center gap-2">
              <el-image
                :src="row.items[0].product_image"
                fit="cover"
                class="w-12 h-12 rounded"
                :preview-src-list="row.items.map(item => item.product_image)"
              />
              <div class="text-left">
                <div class="text-sm">{{ row.items[0].product_name }}</div>
                <div class="text-xs text-gray-500">
                  {{ row.items[0].sku_name || t('mall.product.defaultSpec') }} x{{ row.items[0].quantity }}
                </div>
                <el-tag v-if="row.items.length > 1" size="small" class="mt-1" type="info">
                  +{{ row.items.length - 1 }}{{ t('mall.product.itemUnit') }}
                </el-tag>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.quantity')" width="90">
          <template #default="{ row }">
            <el-tag type="warning" size="small">{{ getTotalQuantity(row.items) }}{{ t('mall.product.itemUnit') }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.paidAmount')" width="120">
          <template #default="{ row }">
            <span class="text-red-500 font-semibold">¥{{ formatYuan(row.pay_amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.orderStatus')" width="110">
          <template #default="{ row }">
            <el-tag :type="statusTypeMap[row.status || 'pending']" size="small">
              {{ statusLabelMap[row.status || 'pending'] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.payStatus')" width="110">
          <template #default="{ row }">
            <el-tag :type="paymentStatusTypeMap[row.pay_status || 'pending']" size="small">
              {{ paymentStatusLabelMap[row.pay_status || 'pending'] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.shippingStatus')" width="120">
          <template #default="{ row }">
            <el-tag :type="shippingStatusTypeMap[row.shipping_status || 'pending']" size="small">
              {{ shippingStatusLabelMap[row.shipping_status || 'pending'] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.orderTime')" width="170">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }} {{ formatTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column :label="t('mall.order.operation')" fixed="right" width="200">
          <template #default="{ row }">
            <div class="flex items-center justify-center gap-2">
              <el-button type="primary" link size="small" @click="showDetail(row)">
                <el-icon><View /></el-icon>
                {{ t('mall.detail') }}
              </el-button>
              <el-button
                v-if="row.status === 'paid'"
                type="success"
                link
                size="small"
                @click="openShipDialog(row)"
              >
                <el-icon><Van /></el-icon>
                {{ t('mall.order.ship') }}
              </el-button>
              <el-popconfirm
                v-if="row.status === 'pending'"
                :title="t('mall.order.cancelConfirm')"
                @confirm="handleCancel(row.id)"
              >
                <template #reference>
                  <el-button type="danger" link size="small">
                    <el-icon><Close /></el-icon>
                    {{ t('mall.order.cancelOrder') }}
                  </el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <div class="flex justify-end mt-4">
        <el-pagination
          :current-page="pagination.page"
          :page-size="pagination.pageSize"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <OrderDetail v-model:visible="detailVisible" :order="currentOrder" />

    <el-dialog v-model="shipDialogVisible" :title="t('mall.order.shipDialog')" width="480px">
      <el-form ref="shipFormRef" :model="shipForm" :rules="shipRules" label-width="100px">
        <el-form-item :label="t('mall.order.expressCompany')" prop="shipping_company">
          <el-select v-model="shipForm.shipping_company" :placeholder="t('mall.order.expressCompanyPlaceholder')" class="w-full">
            <el-option v-for="option in expressOptions" :key="option.value" :label="option.label" :value="option.value">
              <span class="flex items-center gap-2">
                <el-icon><Van /></el-icon>{{ option.label }}
              </span>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label="t('mall.order.expressNo')" prop="shipping_no">
          <el-input v-model="shipForm.shipping_no" :placeholder="t('mall.order.expressNoPlaceholder')">
            <template #prefix>
              <el-icon><Tickets /></el-icon>
            </template>
          </el-input>
        </el-form-item>
        <el-form-item :label="t('mall.order.remark')">
          <el-input v-model="shipForm.remark" type="textarea" rows="3" :placeholder="t('mall.order.remarkPlaceholder')" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="shipDialogVisible = false">{{ t('mall.cancel') }}</el-button>
        <el-button type="primary" :loading="shipLoading" @click="handleShip">
          <template #icon><el-icon><Check /></el-icon></template>
          {{ t('mall.order.confirmShip') }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, computed } from 'vue'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import {
  Document,
  Clock,
  Wallet,
  Van,
  User,
  Phone,
  Goods,
  Search,
  Refresh,
  Download,
  View,
  Close,
  Check,
  Tickets,
} from '@element-plus/icons-vue'
import dayjs from 'dayjs'
import { useI18n } from 'vue-i18n'
import { formatYuan } from '@/utils/price'
import { orderApi, type OrderVo } from '~/mall/api/order'
import OrderDetail from './detail.vue'

defineOptions({ name: 'mall:order' })

const { t } = useI18n()
const loading = ref(false)
const orderList = ref<OrderVo[]>([])
const dateRange = ref<[string, string] | null>(null)

const filters = reactive({
  order_no: '',
  pay_no: '',
  member_id: '',
  member_phone: '',
  product_name: '',
  status: '',
  pay_status: '',
  start_date: '',
  end_date: '',
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0,
})

const stats = reactive({
  total: 0,
  pending: 0,
  paid: 0,
  shipped: 0,
  completed: 0,
})

const statCards = [
  { key: 'total', label: t('mall.order.totalOrders'), icon: Document },
  { key: 'pending', label: t('mall.order.pendingPayment'), icon: Clock },
  { key: 'paid', label: t('mall.order.paid'), icon: Wallet },
  { key: 'shipped', label: t('mall.order.shipped'), icon: Van },
] as const

const detailVisible = ref(false)
const currentOrder = ref<OrderVo | null>(null)

const shipDialogVisible = ref(false)
const shipLoading = ref(false)
const shipFormRef = ref<FormInstance>()
const shipOrderId = ref<number | null>(null)
const shipForm = reactive({
  shipping_company: '',
  shipping_no: '',
  remark: '',
})

const expressOptions = computed(() => [
  { label: t('mall.order.express.sf'), value: 'SF' },
  { label: t('mall.order.express.yto'), value: 'YTO' },
  { label: t('mall.order.express.zto'), value: 'ZTO' },
  { label: t('mall.order.express.yd'), value: 'YD' },
  { label: t('mall.order.express.sto'), value: 'STO' },
  { label: 'EMS', value: 'EMS' },
  { label: t('mall.order.express.jd'), value: 'JD' },
])

const shipRules = computed<FormRules>(() => ({
  shipping_company: [{ required: true, message: t('mall.order.expressCompanyPlaceholder'), trigger: 'change' }],
  shipping_no: [
    { required: true, message: t('mall.order.expressNoPlaceholder'), trigger: 'blur' },
    { min: 5, max: 50, message: t('mall.order.expressNoLength'), trigger: 'blur' },
  ],
}))

const statusLabelMap = computed<Record<string, string>>(() => ({
  pending: t('mall.order.statusPending'),
  paid: t('mall.order.statusPaid'),
  shipped: t('mall.order.statusShipped'),
  completed: t('mall.order.statusCompleted'),
  cancelled: t('mall.order.statusCancelled'),
}))

const statusTypeMap: Record<string, string> = {
  pending: 'warning',
  paid: 'primary',
  shipped: 'success',
  completed: 'success',
  cancelled: 'info',
}

const paymentStatusLabelMap = computed<Record<string, string>>(() => ({
  pending: t('mall.order.payPending'),
  paid: t('mall.order.payPaid'),
  failed: t('mall.order.payFailed'),
  cancelled: t('mall.order.payCancelled'),
  refunded: t('mall.order.payRefunded'),
}))

const paymentStatusTypeMap: Record<string, string> = {
  pending: 'warning',
  paid: 'success',
  failed: 'danger',
  cancelled: 'info',
  refunded: 'danger',
}

const shippingStatusLabelMap = computed<Record<string, string>>(() => ({
  pending: t('mall.order.shipPending'),
  partial_shipped: t('mall.order.shipPartial'),
  shipped: t('mall.order.shipShipped'),
  delivered: t('mall.order.shipDelivered'),
}))

const shippingStatusTypeMap: Record<string, string> = {
  pending: 'info',
  partial_shipped: 'warning',
  shipped: 'success',
  delivered: 'success',
}

const formatDate = (time?: string) => (time ? dayjs(time).format('YYYY-MM-DD') : '-')
const formatTime = (time?: string) => (time ? dayjs(time).format('HH:mm:ss') : '')

const getTotalQuantity = (items?: OrderVo['items']) => {
  if (!items || !items.length) {
    return 0
  }
  return items.reduce((sum, item) => sum + (item.quantity || 0), 0)
}

const buildQueryParams = () => {
  const params: Record<string, any> = {
    ...filters,
    page: pagination.page,
    page_size: pagination.pageSize,
  }
  Object.keys(params).forEach((key) => {
    if (params[key] === '' || params[key] === null || params[key] === undefined) {
      delete params[key]
    }
  })
  return params
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await orderApi.list(buildQueryParams())
    orderList.value = res.data.list
    pagination.total = res.data.total
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('mall.order.loadFailed'))
  }
  finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const res = await orderApi.stats({
      start_date: filters.start_date || undefined,
      end_date: filters.end_date || undefined,
    })
    Object.assign(stats, res.data)
  }
  catch (error) {
    console.error(t('mall.order.loadStatsFailed'), error)
  }
}

const handleSearch = () => {
  pagination.page = 1
  loadData()
  loadStats()
}

const resetFilters = () => {
  Object.assign(filters, {
    order_no: '',
    pay_no: '',
    member_id: '',
    member_phone: '',
    product_name: '',
    status: '',
    pay_status: '',
    start_date: '',
    end_date: '',
  })
  dateRange.value = null
  handleSearch()
}

const handleDateChange = (value: [string, string] | null) => {
  filters.start_date = value?.[0] || ''
  filters.end_date = value?.[1] || ''
  handleSearch()
}

const handlePageChange = (page: number) => {
  pagination.page = page
  loadData()
}

const handleSizeChange = (size: number) => {
  pagination.pageSize = size
  pagination.page = 1
  loadData()
}

const handleExport = async () => {
  try {
    const res = await orderApi.export(buildQueryParams())
    ElMessage.success(res.message || t('mall.order.exportTriggered'))
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('mall.order.exportFailed'))
  }
}

const showDetail = (order: OrderVo) => {
  currentOrder.value = order
  detailVisible.value = true
}

const openShipDialog = (order: OrderVo) => {
  shipOrderId.value = order.id
  shipForm.shipping_company = ''
  shipForm.shipping_no = ''
  shipForm.remark = ''
  shipDialogVisible.value = true
}

const handleShip = () => {
  if (!shipFormRef.value) {
    return
  }
  shipFormRef.value.validate(async (valid) => {
    if (!valid || !shipOrderId.value) {
      return
    }
    shipLoading.value = true
    try {
      await orderApi.ship(shipOrderId.value, { ...shipForm })
      ElMessage.success(t('mall.order.shipSuccess'))
      shipDialogVisible.value = false
      loadData()
      loadStats()
    }
    catch (error: any) {
      ElMessage.error(error?.message || t('mall.order.shipFailed'))
    }
    finally {
      shipLoading.value = false
    }
  })
}

const handleCancel = async (id: number) => {
  try {
    await orderApi.cancel(id)
    ElMessage.success(t('mall.order.cancelSuccess'))
    loadData()
    loadStats()
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('mall.order.cancelFailed'))
  }
}

onMounted(() => {
  loadData()
  loadStats()
})
</script>

<style scoped lang="scss">
.order-page {
  @apply p-3;

  .stat-card {
    @apply flex items-center gap-3;

    .stat-icon {
      @apply w-12 h-12 rounded-full flex items-center justify-center text-white;
      &.total { background-color: #409eff; }
      &.pending { background-color: #e6a23c; }
      &.paid { background-color: #67c23a; }
      &.shipped { background-color: #909399; }
    }

    .stat-info {
      .stat-value {
        @apply text-2xl font-semibold;
      }
      .stat-label {
        @apply text-sm text-gray-500;
      }
    }
  }
}

.el-table .cell {
  white-space: nowrap;
}
</style>
