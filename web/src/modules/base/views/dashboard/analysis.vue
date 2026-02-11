<script setup lang="ts">
import type { Ref } from 'vue'
import type { DashboardAnalysis } from '~/base/api/dashboard.ts'
import { dashboardApi } from '~/base/api/dashboard.ts'
import { useEcharts } from '@/hooks/useEcharts.ts'

defineOptions({ name: 'dashboard:analysis' })

const loading = ref(true)
const data = ref<DashboardAnalysis | null>(null)
const dateRange = ref<[string, string]>(['', ''])

const salesTrendEl = ref() as Ref<HTMLElement>
const membersTrendEl = ref() as Ref<HTMLElement>
const paymentPieEl = ref() as Ref<HTMLElement>
const orderTypePieEl = ref() as Ref<HTMLElement>

const salesTrendChart = useEcharts(salesTrendEl)
const membersTrendChart = useEcharts(membersTrendEl)
const paymentPieChart = useEcharts(paymentPieEl)
const orderTypePieChart = useEcharts(orderTypePieEl)

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
  if (!data.value) return
  renderSalesTrend()
  renderMembersTrend()
  renderPaymentPie()
  renderOrderTypePie()
}

function renderSalesTrend() {
  const trend = data.value?.trends?.sales ?? []
  const labels = trend.map(d => d.date?.slice(5) || '')
  const salesData = trend.map(d => Number(((d.paid_amount || 0) / 100).toFixed(2)))
  const orderData = trend.map(d => d.paid_order_count || 0)

  salesTrendChart?.setOption({
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, data: ['销售额(元)', '支付订单数'] },
    grid: { left: '3%', right: '4%', top: 20, bottom: 40, containLabel: true },
    xAxis: { type: 'category', data: labels.length ? labels : ['暂无数据'], axisLabel: { color: '#86909C' } },
    yAxis: [
      { type: 'value', axisLabel: { color: '#86909C' } },
      { type: 'value', axisLabel: { color: '#86909C' } },
    ],
    series: [
      { name: '销售额(元)', type: 'line', smooth: true, data: salesData, itemStyle: { color: '#409EFF' }, areaStyle: { opacity: 0.08 } },
      { name: '支付订单数', type: 'bar', yAxisIndex: 1, data: orderData, itemStyle: { color: '#67C23A' }, barWidth: 14 },
    ],
  })
}

function renderMembersTrend() {
  const trend = data.value?.trends?.members ?? []
  const labels = trend.map(d => d.date?.slice(5) || '')
  const newData = trend.map(d => d.new_members || 0)
  const activeData = trend.map(d => d.active_members || 0)

  membersTrendChart?.setOption({
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, data: ['新增会员', '活跃会员'] },
    grid: { left: '3%', right: '4%', top: 20, bottom: 40, containLabel: true },
    xAxis: { type: 'category', data: labels.length ? labels : ['暂无数据'], axisLabel: { color: '#86909C' } },
    yAxis: { type: 'value', axisLabel: { color: '#86909C' } },
    series: [
      { name: '新增会员', type: 'line', smooth: true, data: newData, itemStyle: { color: '#E6A23C' }, areaStyle: { opacity: 0.08 } },
      { name: '活跃会员', type: 'line', smooth: true, data: activeData, itemStyle: { color: '#409EFF' }, areaStyle: { opacity: 0.08 } },
    ],
  })
}

const payMethodLabels: Record<string, string> = {
  wechat: '微信支付', alipay: '支付宝', balance: '余额支付', unknown: '未知',
}
const orderTypeLabels: Record<string, string> = {
  normal: '普通订单', seckill: '秒杀订单', group_buy: '拼团订单',
}

function renderPaymentPie() {
  const bd = data.value?.breakdown?.payment_methods ?? []
  const pieData = bd.length
    ? bd.map(d => ({ name: payMethodLabels[d.payment_method] || d.payment_method, value: Number(((d.pay_amount || 0) / 100).toFixed(2)) }))
    : [{ name: '暂无数据', value: 0 }]
  paymentPieChart?.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
    legend: { bottom: 0, icon: 'circle' },
    series: [{
      type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'],
      label: { show: false },
      data: pieData,
    }],
  })
}

function renderOrderTypePie() {
  const bd = data.value?.breakdown?.order_types ?? []
  const pieData = bd.length
    ? bd.map(d => ({ name: orderTypeLabels[d.order_type] || d.order_type, value: d.order_count || 0 }))
    : [{ name: '暂无数据', value: 0 }]
  orderTypePieChart?.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: {c}单 ({d}%)' },
    legend: { bottom: 0, icon: 'circle' },
    series: [{
      type: 'pie', radius: ['40%', '65%'], center: ['50%', '45%'],
      label: { show: false },
      data: pieData,
    }],
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
      <div class="text-base font-medium">商城数据分析</div>
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

    <!-- 核心指标卡片 -->
    <div v-if="data" class="grid grid-cols-2 gap-3 mt-3 mx-3 lg:grid-cols-4">
      <div class="stat-card">
        <div class="text-xs text-gray-5">销售额</div>
        <div class="text-xl font-bold mt-1">¥{{ formatAmount(data.summary.total_sales) }}</div>
        <div class="text-xs mt-1" :class="data.comparison.sales_growth >= 0 ? 'text-red-5' : 'text-green-6'">
          环比 {{ formatGrowth(data.comparison.sales_growth) }}
        </div>
      </div>
      <div class="stat-card">
        <div class="text-xs text-gray-5">支付订单数</div>
        <div class="text-xl font-bold mt-1">{{ data.summary.paid_orders }}</div>
        <div class="text-xs mt-1" :class="data.comparison.orders_growth >= 0 ? 'text-red-5' : 'text-green-6'">
          环比 {{ formatGrowth(data.comparison.orders_growth) }}
        </div>
      </div>
      <div class="stat-card">
        <div class="text-xs text-gray-5">访问人数(今日)</div>
        <div class="text-xl font-bold mt-1">{{ data.summary.total_visitors }}</div>
        <div class="text-xs text-gray-4 mt-1">累计会员 {{ data.summary.total_members }}</div>
      </div>
      <div class="stat-card">
        <div class="text-xs text-gray-5">新增用户</div>
        <div class="text-xl font-bold mt-1">{{ data.summary.new_members }}</div>
        <div class="text-xs mt-1" :class="data.comparison.members_growth >= 0 ? 'text-red-5' : 'text-green-6'">
          环比 {{ formatGrowth(data.comparison.members_growth) }}
        </div>
      </div>
    </div>

    <!-- 更多指标 -->
    <div v-if="data" class="grid grid-cols-2 gap-3 mt-3 mx-3 lg:grid-cols-5">
      <div class="mini-stat">
        <span class="text-xs text-gray-5">客单价</span>
        <span class="font-bold">¥{{ formatAmount(data.summary.avg_order_amount) }}</span>
      </div>
      <div class="mini-stat">
        <span class="text-xs text-gray-5">付费会员</span>
        <span class="font-bold">{{ data.summary.paying_members }}</span>
      </div>
      <div class="mini-stat">
        <span class="text-xs text-gray-5">转化率</span>
        <span class="font-bold">{{ data.summary.conversion_rate }}%</span>
      </div>
      <div class="mini-stat">
        <span class="text-xs text-gray-5">退款金额</span>
        <span class="font-bold text-red-5">¥{{ formatAmount(data.summary.refund_amount) }}</span>
      </div>
      <div class="mini-stat">
        <span class="text-xs text-gray-5">优惠总额</span>
        <span class="font-bold text-orange-5">¥{{ formatAmount(data.summary.discount_total) }}</span>
      </div>
    </div>

    <!-- 趋势图 -->
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

    <!-- 分布图 -->
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

    <!-- 排行榜 -->
    <div v-if="data" class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium mb-3">商品销售排行 Top10</div>
        <el-table :data="data.ranking?.products ?? []" stripe size="small">
          <template #empty><el-empty description="暂无数据" :image-size="60" /></template>
          <el-table-column type="index" label="#" width="50" align="center" />
          <el-table-column prop="product_name" label="商品名称" show-overflow-tooltip />
          <el-table-column prop="sales_count" label="销量" width="80" align="center" />
          <el-table-column label="销售额" width="120" align="center">
            <template #default="{ row }">¥{{ formatAmount(row.sales_amount) }}</template>
          </el-table-column>
        </el-table>
      </div>
      <div class="mine-card">
        <div class="text-base font-medium mb-3">分类销售排行 Top10</div>
        <el-table :data="data.ranking?.categories ?? []" stripe size="small">
          <template #empty><el-empty description="暂无数据" :image-size="60" /></template>
          <el-table-column type="index" label="#" width="50" align="center" />
          <el-table-column prop="category_name" label="分类名称" show-overflow-tooltip />
          <el-table-column prop="sales_count" label="销量" width="80" align="center" />
          <el-table-column label="销售额" width="120" align="center">
            <template #default="{ row }">¥{{ formatAmount(row.sales_amount) }}</template>
          </el-table-column>
        </el-table>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.stat-card {
  @apply p-4 bg-white dark-bg-dark-8 rounded shadow-sm transition-all duration-300 hover-shadow;
}
.mini-stat {
  @apply flex items-center justify-between p-3 bg-white dark-bg-dark-8 rounded shadow-sm;
}
</style>