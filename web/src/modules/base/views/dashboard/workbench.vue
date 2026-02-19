<script setup lang="ts">
import type { Ref } from 'vue'
import type { DashboardAnalysis } from '~/base/api/dashboard.ts'
import { dashboardApi } from '~/base/api/dashboard.ts'
import { useEcharts } from '@/hooks/useEcharts.ts'
import { useI18n } from 'vue-i18n'

defineOptions({ name: 'dashboard:workbench' })

const { t } = useI18n()
const loading = ref(true)
const data = ref<DashboardAnalysis | null>(null)
const dateRange = ref<[string, string]>(['', ''])

const salesTrendEl = ref() as Ref<HTMLElement>
const membersTrendEl = ref() as Ref<HTMLElement>
const paymentPieEl = ref() as Ref<HTMLElement>
const orderTypePieEl = ref() as Ref<HTMLElement>
const productBarEl = ref() as Ref<HTMLElement>
const categoryBarEl = ref() as Ref<HTMLElement>

const salesTrendChart = useEcharts(salesTrendEl)
const membersTrendChart = useEcharts(membersTrendEl)
const paymentPieChart = useEcharts(paymentPieEl)
const orderTypePieChart = useEcharts(orderTypePieEl)
const productBarChart = useEcharts(productBarEl)
const categoryBarChart = useEcharts(categoryBarEl)

function formatAmount(cents: number): string {
  return (cents / 100).toFixed(2)
}

function formatGrowth(rate: number): string {
  return rate > 0 ? `+${rate}%` : `${rate}%`
}

async function fetchData() {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (dateRange.value[0]) params.start_date = dateRange.value[0]
    if (dateRange.value[1]) params.end_date = dateRange.value[1]
    const res = await dashboardApi.analysis(params)
    data.value = res.data ?? res as any
  }
  catch {
    data.value = null
  }
  finally {
    loading.value = false
  }
}

function renderCharts() {
  renderSalesTrend()
  renderMembersTrend()
  renderPaymentPie()
  renderOrderTypePie()
  renderProductBar()
  renderCategoryBar()
}

function renderSalesTrend() {
  const trend = data.value?.trends?.sales ?? []
  const labels = trend.length ? trend.map(d => d.date?.slice(5) || '') : [t('dashboard.chart.noData')]
  const salesData = trend.map(d => Number(((d.paid_amount || 0) / 100).toFixed(2)))
  const orderData = trend.map(d => d.paid_order_count || 0)

  salesTrendChart?.setOption({
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, data: [t('dashboard.chart.salesAmountYuan'), t('dashboard.summary.paidOrders')] },
    grid: { left: '3%', right: '4%', top: 20, bottom: 40, containLabel: true },
    xAxis: { type: 'category', data: labels, axisLabel: { color: '#86909C' } },
    yAxis: [
      { type: 'value', axisLabel: { color: '#86909C' } },
      { type: 'value', axisLabel: { color: '#86909C' } },
    ],
    series: [
      { name: t('dashboard.chart.salesAmountYuan'), type: 'line', smooth: true, data: salesData, itemStyle: { color: '#409EFF' }, areaStyle: { opacity: 0.08 } },
      { name: t('dashboard.summary.paidOrders'), type: 'bar', yAxisIndex: 1, data: orderData, itemStyle: { color: '#67C23A' }, barWidth: 14 },
    ],
  })
}

function renderMembersTrend() {
  const trend = data.value?.trends?.members ?? []
  const labels = trend.length ? trend.map(d => d.date?.slice(5) || '') : [t('dashboard.chart.noData')]
  const newData = trend.map(d => d.new_members || 0)
  const activeData = trend.map(d => d.active_members || 0)

  membersTrendChart?.setOption({
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, data: [t('dashboard.chart.newMember'), t('dashboard.chart.activeMember')] },
    grid: { left: '3%', right: '4%', top: 20, bottom: 40, containLabel: true },
    xAxis: { type: 'category', data: labels, axisLabel: { color: '#86909C' } },
    yAxis: { type: 'value', axisLabel: { color: '#86909C' } },
    series: [
      { name: t('dashboard.chart.newMember'), type: 'line', smooth: true, data: newData, itemStyle: { color: '#E6A23C' }, areaStyle: { opacity: 0.08 } },
      { name: t('dashboard.chart.activeMember'), type: 'line', smooth: true, data: activeData, itemStyle: { color: '#409EFF' }, areaStyle: { opacity: 0.08 } },
    ],
  })
}

const payMethodLabels = computed<Record<string, string>>(() => ({
  wechat: t('dashboard.payMethod.wechat'), alipay: t('dashboard.payMethod.alipay'), balance: t('dashboard.payMethod.balance'), unknown: t('dashboard.payMethod.unknown'),
}))
const orderTypeLabels = computed<Record<string, string>>(() => ({
  normal: t('dashboard.orderType.normal'), seckill: t('dashboard.orderType.seckill'), group_buy: t('dashboard.orderType.groupBuy'),
}))

function renderPaymentPie() {
  const bd = data.value?.breakdown?.payment_methods ?? []
  const pieData = bd.length
    ? bd.map(d => ({ name: payMethodLabels.value[d.payment_method] || d.payment_method, value: Number(((d.pay_amount || 0) / 100).toFixed(2)) }))
    : [{ name: t('dashboard.chart.noData'), value: 0 }]
  paymentPieChart?.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
    legend: { bottom: 0, icon: 'circle' },
    series: [{ type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'], label: { show: false }, data: pieData }],
  })
}

function renderOrderTypePie() {
  const bd = data.value?.breakdown?.order_types ?? []
  const pieData = bd.length
    ? bd.map(d => ({ name: orderTypeLabels.value[d.order_type] || d.order_type, value: d.order_count || 0 }))
    : [{ name: t('dashboard.chart.noData'), value: 0 }]
  orderTypePieChart?.setOption({
    tooltip: { trigger: 'item', formatter: `{b}: {c}${t('dashboard.chart.unitOrder')} ({d}%)` },
    legend: { bottom: 0, icon: 'circle' },
    series: [{ type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'], label: { show: false }, data: pieData }],
  })
}

function renderProductBar() {
  const products = data.value?.ranking?.products ?? []
  const names = products.length ? products.map(d => d.product_name).reverse() : [t('dashboard.chart.noData')]
  const values = products.length ? products.map(d => d.sales_count || 0).reverse() : [0]
  productBarChart?.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    grid: { left: '3%', right: '8%', top: 10, bottom: 10, containLabel: true },
    xAxis: { type: 'value', axisLabel: { color: '#86909C' } },
    yAxis: { type: 'category', data: names, axisLabel: { color: '#86909C', width: 80, overflow: 'truncate' } },
    series: [{ type: 'bar', data: values, itemStyle: { color: '#409EFF', borderRadius: [0, 4, 4, 0] }, barWidth: 14 }],
  })
}

function renderCategoryBar() {
  const cats = data.value?.ranking?.categories ?? []
  const names = cats.length ? cats.map(d => d.category_name).reverse() : [t('dashboard.chart.noData')]
  const values = cats.length ? cats.map(d => Number(((d.sales_amount || 0) / 100).toFixed(2))).reverse() : [0]
  categoryBarChart?.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' }, formatter: (p: any) => `${p[0]?.name}: ¥${p[0]?.value}` },
    grid: { left: '3%', right: '8%', top: 10, bottom: 10, containLabel: true },
    xAxis: { type: 'value', axisLabel: { color: '#86909C' } },
    yAxis: { type: 'category', data: names, axisLabel: { color: '#86909C', width: 80, overflow: 'truncate' } },
    series: [{ type: 'bar', data: values, itemStyle: { color: '#67C23A', borderRadius: [0, 4, 4, 0] }, barWidth: 14 }],
  })
}

function onDateChange(val: [string, string] | null) {
  if (!val) {
    dateRange.value = ['', '']
  }
  fetchData().then(() => nextTick(() => renderCharts()))
}

onMounted(async () => {
  await fetchData()
  nextTick(() => renderCharts())
})
</script>

<template>
  <div v-loading="loading" class="mine-layout">
    <div class="mine-card flex items-center justify-between">
      <div class="text-base font-medium">{{ t('dashboard.workbench') }}</div>
      <div class="w-260px flex-shrink-0">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          :range-separator="t('dashboard.dateRange.to')"
          :start-placeholder="t('dashboard.dateRange.start')"
          :end-placeholder="t('dashboard.dateRange.end')"
          value-format="YYYY-MM-DD"
          class="!w-full"
          @change="onDateChange"
        />
      </div>
    </div>

    <div v-if="data" class="grid grid-cols-2 gap-3 mt-3 mx-3 lg:grid-cols-4">
      <div class="stat-card bg-blue-50 dark-bg-blue-900/20">
        <div class="text-xs text-gray-5">{{ t('dashboard.summary.totalSalesAll') }}</div>
        <div class="text-xl font-bold text-blue-6 mt-1">¥{{ formatAmount(data.summary.total_sales) }}</div>
        <div class="text-xs mt-1" :class="data.comparison.sales_growth >= 0 ? 'text-red-5' : 'text-green-6'">
          {{ t('dashboard.summary.qoq') }} {{ formatGrowth(data.comparison.sales_growth) }}
        </div>
      </div>
      <div class="stat-card bg-green-50 dark-bg-green-900/20">
        <div class="text-xs text-gray-5">{{ t('dashboard.summary.paidOrdersShort') }}</div>
        <div class="text-xl font-bold text-green-6 mt-1">{{ data.summary.paid_orders }}</div>
        <div class="text-xs mt-1" :class="data.comparison.orders_growth >= 0 ? 'text-red-5' : 'text-green-6'">
          {{ t('dashboard.summary.qoq') }} {{ formatGrowth(data.comparison.orders_growth) }}
        </div>
      </div>
      <div class="stat-card bg-purple-50 dark-bg-purple-900/20">
        <div class="text-xs text-gray-5">{{ t('dashboard.summary.newMembersShort') }}</div>
        <div class="text-xl font-bold text-purple-6 mt-1">{{ data.summary.new_members }}</div>
        <div class="text-xs mt-1" :class="data.comparison.members_growth >= 0 ? 'text-red-5' : 'text-green-6'">
          {{ t('dashboard.summary.qoq') }} {{ formatGrowth(data.comparison.members_growth) }}
        </div>
      </div>
      <div class="stat-card bg-orange-50 dark-bg-orange-900/20">
        <div class="text-xs text-gray-5">{{ t('dashboard.summary.avgOrderAmount') }}</div>
        <div class="text-xl font-bold text-orange-6 mt-1">¥{{ formatAmount(data.summary.avg_order_amount) }}</div>
        <div class="text-xs text-gray-4 mt-1">{{ t('dashboard.summary.conversionRate') }} {{ data.summary.conversion_rate }}%</div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium">{{ t('dashboard.chart.salesTrend') }}</div>
        <div ref="salesTrendEl" class="mt-3 h-300px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium">{{ t('dashboard.chart.membersTrend') }}</div>
        <div ref="membersTrendEl" class="mt-3 h-300px" />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium">{{ t('dashboard.chart.paymentDistribution') }}</div>
        <div ref="paymentPieEl" class="mt-3 h-300px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium">{{ t('dashboard.chart.orderTypeDistribution') }}</div>
        <div ref="orderTypePieEl" class="mt-3 h-300px" />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium mb-3">{{ t('dashboard.chart.productSalesRanking') }}</div>
        <div ref="productBarEl" class="h-300px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium mb-3">{{ t('dashboard.chart.categorySalesRanking') }}</div>
        <div ref="categoryBarEl" class="h-300px" />
      </div>
    </div>

    <div v-if="data" class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium mb-3">{{ t('dashboard.ranking.productSalesDetailTop10') }}</div>
        <el-table :data="data.ranking?.products ?? []" stripe size="small">
          <template #empty><el-empty :description="t('dashboard.chart.noData')" :image-size="60" /></template>
          <el-table-column type="index" :label="t('dashboard.table.index')" width="50" align="center" />
          <el-table-column prop="product_name" :label="t('dashboard.table.productName')" show-overflow-tooltip />
          <el-table-column prop="sales_count" :label="t('dashboard.table.salesCount')" width="80" align="center" />
          <el-table-column :label="t('dashboard.table.salesAmount')" width="120" align="center">
            <template #default="{ row }">¥{{ formatAmount(row.sales_amount) }}</template>
          </el-table-column>
        </el-table>
      </div>
      <div class="mine-card">
        <div class="text-base font-medium mb-3">{{ t('dashboard.ranking.categorySalesDetailTop10') }}</div>
        <el-table :data="data.ranking?.categories ?? []" stripe size="small">
          <template #empty><el-empty :description="t('dashboard.chart.noData')" :image-size="60" /></template>
          <el-table-column type="index" :label="t('dashboard.table.index')" width="50" align="center" />
          <el-table-column prop="category_name" :label="t('dashboard.table.categoryName')" show-overflow-tooltip />
          <el-table-column prop="sales_count" :label="t('dashboard.table.salesCount')" width="80" align="center" />
          <el-table-column :label="t('dashboard.table.salesAmount')" width="120" align="center">
            <template #default="{ row }">¥{{ formatAmount(row.sales_amount) }}</template>
          </el-table-column>
        </el-table>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.stat-card {
  @apply p-4 rounded transition-all duration-300 hover-shadow;
}
</style>
