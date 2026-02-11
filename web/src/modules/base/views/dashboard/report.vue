<script setup lang="ts">
import type { Ref } from 'vue'
import type { DashboardReport } from '~/base/api/dashboard.ts'
import { dashboardApi } from '~/base/api/dashboard.ts'
import { useEcharts } from '@/hooks/useEcharts.ts'

defineOptions({ name: 'dashboard:report' })

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

const payMethodLabels: Record<string, string> = {
  wechat: '微信支付', alipay: '支付宝', balance: '余额支付', unknown: '未知',
}
const orderTypeLabels: Record<string, string> = {
  normal: '普通订单', seckill: '秒杀订单', group_buy: '拼团订单',
}
const levelLabels: Record<string, string> = {
  bronze: '青铜', silver: '白银', gold: '黄金', diamond: '钻石',
}

function renderSalesTrend() {
  const trend = data.value?.sales_trend ?? []
  const labels = trend.length ? trend.map((d: any) => d.date?.slice(5) || '') : ['暂无数据']
  const salesData = trend.map((d: any) => Number(((d.paid_amount || 0) / 100).toFixed(2)))
  const orderData = trend.map((d: any) => d.paid_order_count || 0)

  salesTrendChart?.setOption({
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, data: ['销售额(元)', '订单数'] },
    grid: { left: '3%', right: '4%', top: 20, bottom: 40, containLabel: true },
    xAxis: { type: 'category', data: labels, axisLabel: { color: '#86909C' } },
    yAxis: [
      { type: 'value', name: '销售额', axisLabel: { color: '#86909C' } },
      { type: 'value', name: '订单数', axisLabel: { color: '#86909C' } },
    ],
    series: [
      { name: '销售额(元)', type: 'line', smooth: true, data: salesData, itemStyle: { color: '#409EFF' }, areaStyle: { opacity: 0.08 } },
      { name: '订单数', type: 'bar', yAxisIndex: 1, data: orderData, itemStyle: { color: '#67C23A' }, barWidth: 14 },
    ],
  })
}

function renderMembersTrend() {
  const trend = data.value?.members_trend ?? []
  const labels = trend.length ? trend.map((d: any) => d.date?.slice(5) || '') : ['暂无数据']
  const newData = trend.map((d: any) => d.new_members || 0)
  const activeData = trend.map((d: any) => d.active_members || 0)

  membersTrendChart?.setOption({
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, data: ['新增会员', '活跃会员'] },
    grid: { left: '3%', right: '4%', top: 20, bottom: 40, containLabel: true },
    xAxis: { type: 'category', data: labels, axisLabel: { color: '#86909C' } },
    yAxis: { type: 'value', axisLabel: { color: '#86909C' } },
    series: [
      { name: '新增会员', type: 'line', smooth: true, data: newData, itemStyle: { color: '#E6A23C' }, areaStyle: { opacity: 0.08 } },
      { name: '活跃会员', type: 'line', smooth: true, data: activeData, itemStyle: { color: '#409EFF' }, areaStyle: { opacity: 0.08 } },
    ],
  })
}

function renderPaymentPie() {
  const bd = data.value?.payment_breakdown ?? []
  const pieData = bd.length
    ? bd.map((d: any) => ({ name: payMethodLabels[d.payment_method] || d.payment_method, value: Number(((d.pay_amount || 0) / 100).toFixed(2)) }))
    : [{ name: '暂无数据', value: 0 }]
  paymentPieChart?.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
    legend: { bottom: 0, icon: 'circle' },
    series: [{ type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'], label: { show: false }, data: pieData }],
  })
}

function renderOrderTypePie() {
  const bd = data.value?.order_type_breakdown ?? []
  const pieData = bd.length
    ? bd.map((d: any) => ({ name: orderTypeLabels[d.order_type] || d.order_type, value: d.order_count || 0 }))
    : [{ name: '暂无数据', value: 0 }]
  orderTypePieChart?.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: {c}单 ({d}%)' },
    legend: { bottom: 0, icon: 'circle' },
    series: [{ type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'], label: { show: false }, data: pieData }],
  })
}

function renderRegionBar() {
  const regions = data.value?.region_ranking ?? []
  const names = regions.length ? regions.slice(0, 15).map((d: any) => d.region || '未知').reverse() : ['暂无数据']
  const values = regions.length ? regions.slice(0, 15).map((d: any) => Number(((d.sales_amount || 0) / 100).toFixed(2))).reverse() : [0]
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
    ? levels.map((d: any) => ({ name: levelLabels[d.level] || d.level, value: d.member_count || 0 }))
    : [{ name: '暂无数据', value: 0 }]
  const colors = ['#8CC5FF', '#FDBA74', '#F7797D', '#BB8FCE', '#67C23A']
  memberLevelPieChart?.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: {c}人 ({d}%)' },
    legend: { bottom: 0, icon: 'circle' },
    color: colors,
    series: [{ type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'], label: { show: false }, data: pieData }],
  })
}

function renderOrderAmountDist() {
  const dist = data.value?.order_amount_distribution ?? []
  const labels = dist.length ? dist.map((d: any) => d.label) : ['暂无数据']
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
  const names = cats.length ? cats.slice(0, 15).map((d: any) => d.category_name || '未知').reverse() : ['暂无数据']
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
    <!-- 日期筛选 -->
    <div class="mine-card flex items-center justify-between">
      <div class="text-base font-medium">统计报表</div>
      <div class="w-260px flex-shrink-0">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始"
          end-placeholder="结束"
          value-format="YYYY-MM-DD"
          class="!w-full"
          @change="onDateChange"
        />
      </div>
    </div>

    <!-- 汇总卡片 -->
    <div v-if="data" class="grid grid-cols-2 gap-3 mt-3 mx-3 lg:grid-cols-4">
      <div class="stat-card bg-blue-50 dark-bg-blue-900/20">
        <div class="text-xs text-gray-5">总销售额</div>
        <div class="text-xl font-bold text-blue-6 mt-1">¥{{ formatAmount(data.sales_summary?.paid_amount ?? 0) }}</div>
      </div>
      <div class="stat-card bg-green-50 dark-bg-green-900/20">
        <div class="text-xs text-gray-5">支付订单数</div>
        <div class="text-xl font-bold text-green-6 mt-1">{{ data.sales_summary?.paid_order_count ?? 0 }}</div>
      </div>
      <div class="stat-card bg-purple-50 dark-bg-purple-900/20">
        <div class="text-xs text-gray-5">新增会员</div>
        <div class="text-xl font-bold text-purple-6 mt-1">{{ data.members_summary?.new_members ?? 0 }}</div>
      </div>
      <div class="stat-card bg-orange-50 dark-bg-orange-900/20">
        <div class="text-xs text-gray-5">退款率</div>
        <div class="text-xl font-bold text-orange-6 mt-1">{{ data.refund_analysis?.refund_rate ?? 0 }}%</div>
        <div class="text-xs text-gray-4 mt-1">退款 {{ data.refund_analysis?.refund_count ?? 0 }} 笔</div>
      </div>
    </div>

    <!-- 销售趋势 + 会员趋势 -->
    <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium">销售趋势</div>
        <div ref="salesTrendEl" class="mt-3 h-300px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium">会员趋势</div>
        <div ref="membersTrendEl" class="mt-3 h-300px" />
      </div>
    </div>

    <!-- 支付方式 + 订单类型 -->
    <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium">支付方式分布</div>
        <div ref="paymentPieEl" class="mt-3 h-300px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium">订单类型分布</div>
        <div ref="orderTypePieEl" class="mt-3 h-300px" />
      </div>
    </div>

    <!-- 地区排行 + 会员等级 -->
    <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium">地区销售排行</div>
        <div ref="regionBarEl" class="mt-3 h-360px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium">会员等级分布</div>
        <div ref="memberLevelPieEl" class="mt-3 h-360px" />
      </div>
    </div>

    <!-- 客单价分布 + 分类排行 -->
    <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium">客单价分布</div>
        <div ref="orderAmountDistEl" class="mt-3 h-300px" />
      </div>
      <div class="mine-card">
        <div class="text-base font-medium">分类销售额排行</div>
        <div ref="categoryBarEl" class="mt-3 h-300px" />
      </div>
    </div>

    <!-- 商品排行表格 -->
    <div v-if="data" class="mine-card mt-3">
      <div class="text-base font-medium mb-3">商品销售排行 Top20</div>
      <el-table :data="data.product_ranking ?? []" stripe size="small">
        <template #empty><el-empty description="暂无数据" :image-size="60" /></template>
        <el-table-column type="index" label="#" width="50" align="center" />
        <el-table-column prop="product_name" label="商品名称" show-overflow-tooltip />
        <el-table-column prop="sales_count" label="销量" width="100" align="center" />
        <el-table-column label="销售额" width="140" align="center">
          <template #default="{ row }">¥{{ formatAmount(row.sales_amount) }}</template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 分类排行表格 -->
    <div v-if="data" class="mine-card mt-3">
      <div class="text-base font-medium mb-3">分类销售排行 Top20</div>
      <el-table :data="data.category_ranking ?? []" stripe size="small">
        <template #empty><el-empty description="暂无数据" :image-size="60" /></template>
        <el-table-column type="index" label="#" width="50" align="center" />
        <el-table-column prop="category_name" label="分类名称" show-overflow-tooltip />
        <el-table-column prop="sales_count" label="销量" width="100" align="center" />
        <el-table-column label="销售额" width="140" align="center">
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
