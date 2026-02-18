<script setup lang="ts">
import type { Ref } from 'vue'
import type { DashboardReport } from '~/base/api/dashboard.ts'
import { dashboardApi } from '~/base/api/dashboard.ts'
import { useEcharts } from '@/hooks/useEcharts.ts'
import { useI18n } from 'vue-i18n'

defineOptions({ name: 'dashboard:report' })

const { t } = useI18n()
const loading = ref(true)
const data = ref<DashboardReport | null>(null)
const dateRange = ref<[string, string]>(['', ''])

const salesTrendEl = ref() as Ref<HTMLElement>
const membersTrendEl = ref() as Ref<HTMLElement>
const paymentPieEl = ref() as Ref<HTMLElement>
const orderTypePieEl = ref() as Ref<HTMLElement>
const regionBarEl = ref() as Ref<HTMLElement>
const memberLevelPieEl = ref() as Ref<HTMLElement>
const orderAmountDistEl = ref() as Ref<HTMLElement>
const categoryBarEl = ref() as Ref<HTMLElement>

const salesTrendChart = useEcharts(salesTrendEl)
const membersTrendChart = useEcharts(membersTrendEl)
const paymentPieChart = useEcharts(paymentPieEl)
const orderTypePieChart = useEcharts(orderTypePieEl)
const regionBarChart = useEcharts(regionBarEl)
const memberLevelPieChart = useEcharts(memberLevelPieEl)
const orderAmountDistChart = useEcharts(orderAmountDistEl)
const categoryBarChart = useEcharts(categoryBarEl)

function formatAmount(cents: number): string {
  return (cents / 100).toFixed(2)
}

async function fetchData() {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (dateRange.value[0]) params.start_date = dateRange.value[0]
    if (dateRange.value[1]) params.end_date = dateRange.value[1]
    const res = await dashboardApi.report(params)
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
  renderRegionBar()
  renderMemberLevelPie()
  renderOrderAmountDist()
  renderCategoryBar()
}

const payMethodLabels = computed<Record<string, string>>(() => ({
  wechat: t('dashboard.payMethod.wechat'), alipay: t('dashboard.payMethod.alipay'), balance: t('dashboard.payMethod.balance'), unknown: t('dashboard.payMethod.unknown'),
}))
const orderTypeLabels = computed<Record<string, string>>(() => ({
  normal: t('dashboard.orderType.normal'), seckill: t('dashboard.orderType.seckill'), group_buy: t('dashboard.orderType.groupBuy'),
}))
const levelLabels = computed<Record<string, string>>(() => ({
  bronze: t('member.level.bronze'), silver: t('member.level.silver'), gold: t('member.level.gold'), diamond: t('member.level.diamond'),
}))

function renderSalesTrend() {
  const trend = data.value?.sales_trend ?? []
  const labels = trend.length ? trend.map((d: any) => d.date?.slice(5) || '') : [t('dashboard.chart.noData')]
  const salesData = trend.map((d: any) => Number(((d.paid_amount || 0) / 100).toFixed(2)))
  const orderData = trend.map((d: any) => d.paid_order_count || 0)

  salesTrendChart?.setOption({
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, data: [t('dashboard.chart.salesAmountYuan'), t('dashboard.chart.orderCount')] },
    grid: { left: '3%', right: '4%', top: 20, bottom: 40, containLabel: true },
    xAxis: { type: 'category', data: labels, axisLabel: { color: '#86909C' } },
    yAxis: [
      { type: 'value', name: t('dashboard.chart.salesAmount'), axisLabel: { color: '#86909C' } },
      { type: 'value', name: t('dashboard.chart.orderCount'), axisLabel: { color: '#86909C' } },
    ],
    series: [
      { name: t('dashboard.chart.salesAmountYuan'), type: 'line', smooth: true, data: salesData, itemStyle: { color: '#409EFF' }, areaStyle: { opacity: 0.08 } },
      { name: t('dashboard.chart.orderCount'), type: 'bar', yAxisIndex: 1, data: orderData, itemStyle: { color: '#67C23A' }, barWidth: 14 },
    ],
  })
}

function renderMembersTrend() {
  const trend = data.value?.members_trend ?? []
  const labels = trend.length ? trend.map((d: any) => d.date?.slice(5) || '') : [t('dashboard.chart.noData')]
  const newData = trend.map((d: any) => d.new_members || 0)
  const activeData = trend.map((d: any) => d.active_members || 0)

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

function renderPaymentPie() {
  const bd = data.value?.payment_breakdown ?? []
  const pieData = bd.length
    ? bd.map((d: any) => ({ name: payMethodLabels.value[d.payment_method] || d.payment_method, value: Number(((d.pay_amount || 0) / 100).toFixed(2)) }))
    : [{ name: t('dashboard.chart.noData'), value: 0 }]
  paymentPieChart?.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
    legend: { bottom: 0, icon: 'circle' },
    series: [{ type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'], label: { show: false }, data: pieData }],
  })
}

function renderOrderTypePie() {
  const bd = data.value?.order_type_breakdown ?? []
  const pieData = bd.length
    ? bd.map((d: any) => ({ name: orderTypeLabels.value[d.order_type] || d.order_type, value: d.order_count || 0 }))
    : [{ name: t('dashboard.chart.noData'), value: 0 }]
  orderTypePieChart?.setOption({
    tooltip: { trigger: 'item', formatter: `{b}: {c}${t('dashboard.chart.unitOrder')} ({d}%)` },
    legend: { bottom: 0, icon: 'circle' },
    series: [{ type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'], label: { show: false }, data: pieData }],
  })
}

function renderRegionBar() {
  const regions = data.value?.region_ranking ?? []
  const names = regions.length ? regions.slice(0, 15).map((d: any) => d.province || t('dashboard.payMethod.unknown')).reverse() : [t('dashboard.chart.noData')]
  const values = regions.length ? regions.slice(0, 15).map((d: any) => Number(((d.order_amount || 0) / 100).toFixed(2))).reverse() : [0]
  regionBarChart?.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' }, formatter: (p: any) => `${p[0]?.name}: ¥${p[0]?.value}` },
    grid: { left: '3%', right: '8%', top: 10, bottom: 10, containLabel: true },
    xAxis: { type: 'value', axisLabel: { color: '#86909C' } },
    yAxis: { type: 'category', data: names, axisLabel: { color: '#86909C', width: 60, overflow: 'truncate' } },
    series: [{ type: 'bar', data: values, itemStyle: { color: '#409EFF', borderRadius: [0, 4, 4, 0] }, barWidth: 14 }],
  })
}

function renderMemberLevelPie() {
  const levels = data.value?.member_level_breakdown ?? []
  const pieData = levels.length
    ? levels.map((d: any) => ({ name: levelLabels.value[d.level] || d.level, value: d.member_count || 0 }))
    : [{ name: t('dashboard.chart.noData'), value: 0 }]
  const colors = ['#8CC5FF', '#FDBA74', '#F7797D', '#BB8FCE', '#67C23A']
  memberLevelPieChart?.setOption({
    tooltip: { trigger: 'item', formatter: `{b}: {c}${t('dashboard.chart.unitPerson')} ({d}%)` },
    legend: { bottom: 0, icon: 'circle' },
    color: colors,
    series: [{ type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'], label: { show: false }, data: pieData }],
  })
}

function renderOrderAmountDist() {
  const dist = data.value?.order_amount_distribution ?? []
  const labels = dist.length ? dist.map((d: any) => d.label) : [t('dashboard.chart.noData')]
  const values = dist.map((d: any) => d.count || 0)
  orderAmountDistChart?.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    grid: { left: '3%', right: '4%', top: 20, bottom: 10, containLabel: true },
    xAxis: { type: 'category', data: labels, axisLabel: { color: '#86909C', rotate: 20 } },
    yAxis: { type: 'value', axisLabel: { color: '#86909C' } },
    series: [{ type: 'bar', data: values, itemStyle: { color: '#E6A23C', borderRadius: [4, 4, 0, 0] }, barWidth: 24 }],
  })
}

function renderCategoryBar() {
  const cats = data.value?.category_ranking ?? []
  const names = cats.length ? cats.slice(0, 15).map((d: any) => d.category_name || t('dashboard.payMethod.unknown')).reverse() : [t('dashboard.chart.noData')]
  const values = cats.length ? cats.slice(0, 15).map((d: any) => Number(((d.sales_amount || 0) / 100).toFixed(2))).reverse() : [0]
  categoryBarChart?.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' }, formatter: (p: any) => `${p[0]?.name}: ¥${p[0]?.value}` },
    grid: { left: '3%', right: '8%', top: 10, bottom: 10, containLabel: true },
    xAxis: { type: 'value', axisLabel: { color: '#86909C' } },
    yAxis: { type: 'category', data: names, axisLabel: { color: '#86909C', width: 80, overflow: 'truncate' } },
    series: [{ type: 'bar', data: values, itemStyle: { color: '#67C23A', borderRadius: [0, 4, 4, 0] }, barWidth: 14 }],
  })
}

function onDateChange() {
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
      <div class="text-base font-medium">{{ t('dashboard.statisticsReport') }}</div>
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
        <div class="text-xl font-bold text-blue-6 mt-1">¥{{ formatAmount(data.sales_summary?.paid_amount ?? 0) }}</div>
      </div>
      <div class="stat-card bg-green-50 dark-bg-green-900/20">
        <div class="text-xs text-gray-5">{{ t('dashboard.summary.paidOrders') }}</div>
        <div class="text-xl font-bold text-green-6 mt-1">{{ data.sales_summary?.paid_order_count ?? 0 }}</div>
      </div>
      <div class="stat-card bg-purple-50 dark-bg-purple-900/20">
        <div class="text-xs text-gray-5">{{ t('dashboard.summary.newMembersShort') }}</div>
        <div class="text-xl font-bold text-purple-6 mt-1">{{ data.members_summary?.new_members ?? 0 }}</div>
      </div>
      <div class="stat-card bg-orange-50 dark-bg-orange-900/20">
        <div class="text-xs text-gray-5">{{ t('dashboard.summary.refundRate') }}</div>
        <div class="text-xl font-bold text-orange-6 mt-1">{{ data.refund_analysis?.refund_rate ?? 0 }}%</div>
        <div class="text-xs text-gray-4 mt-1">{{ t('dashboard.summary.refundCount', { count: data.refund_analysis?.refund_count ?? 0 }) }}</div>
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
        <div class="text-base font-medium">{{ t('dashboard.chart.regionSalesRanking') }}</div>
        <div ref="regionBarEl" class="mt-3 h-360px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium">{{ t('dashboard.chart.memberLevelDistribution') }}</div>
        <div ref="memberLevelPieEl" class="mt-3 h-360px" />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium">{{ t('dashboard.chart.orderAmountDistribution') }}</div>
        <div ref="orderAmountDistEl" class="mt-3 h-300px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium">{{ t('dashboard.chart.categorySalesRanking') }}</div>
        <div ref="categoryBarEl" class="mt-3 h-300px" />
      </div>
    </div>

    <div v-if="data" class="mine-card mt-3">
      <div class="text-base font-medium mb-3">{{ t('dashboard.ranking.productSalesTop20') }}</div>
      <el-table :data="data.product_ranking ?? []" stripe size="small">
        <template #empty><el-empty :description="t('dashboard.chart.noData')" :image-size="60" /></template>
        <el-table-column type="index" :label="t('dashboard.table.index')" width="50" align="center" />
        <el-table-column prop="product_name" :label="t('dashboard.table.productName')" show-overflow-tooltip />
        <el-table-column prop="sales_count" :label="t('dashboard.table.salesCount')" width="100" align="center" />
        <el-table-column :label="t('dashboard.table.salesAmount')" width="140" align="center">
          <template #default="{ row }">¥{{ formatAmount(row.sales_amount) }}</template>
        </el-table-column>
      </el-table>
    </div>

    <div v-if="data" class="mine-card mt-3">
      <div class="text-base font-medium mb-3">{{ t('dashboard.ranking.categorySalesTop20') }}</div>
      <el-table :data="data.category_ranking ?? []" stripe size="small">
        <template #empty><el-empty :description="t('dashboard.chart.noData')" :image-size="60" /></template>
        <el-table-column type="index" :label="t('dashboard.table.index')" width="50" align="center" />
        <el-table-column prop="category_name" :label="t('dashboard.table.categoryName')" show-overflow-tooltip />
        <el-table-column prop="sales_count" :label="t('dashboard.table.salesCount')" width="100" align="center" />
        <el-table-column :label="t('dashboard.table.salesAmount')" width="140" align="center">
          <template #default="{ row }">¥{{ formatAmount(row.sales_amount) }}</template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.stat-card {
  @apply p-4 rounded transition-all duration-300 hover-shadow;
}
</style>
