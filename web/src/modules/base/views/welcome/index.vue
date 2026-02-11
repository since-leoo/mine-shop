<script setup lang="ts">
import type { Ref } from 'vue'
import type { DashboardWelcome } from '~/base/api/dashboard.ts'
import { dashboardApi } from '~/base/api/dashboard.ts'
import { useEcharts } from '@/hooks/useEcharts.ts'

defineOptions({ name: 'welcome' })

const userinfo = useUserStore().getUserInfo()
const loading = ref(true)
const data = ref<DashboardWelcome | null>(null)
const salesChartEl = ref() as Ref<HTMLElement>
const salesChart = useEcharts(salesChartEl)

const greetingText = computed(() => {
  const hour = new Date().getHours()
  if (hour < 9) return '早上好'
  if (hour < 12) return '上午好'
  if (hour < 14) return '中午好'
  if (hour < 18) return '下午好'
  return '晚上好'
})

function formatAmount(cents: number): string {
  return (cents / 100).toFixed(2)
}

async function fetchData() {
  loading.value = true
  try {
    const res = await dashboardApi.welcome()
    data.value = res.data ?? res as any
  }
  catch {
    data.value = null
  }
  finally {
    loading.value = false
  }
}

function renderSalesChart() {
  const trend = data.value?.sales_trend ?? []
  const labels = trend.map(d => d.date?.slice(5) || '')
  const salesData = trend.map(d => Number(((d.paid_amount || 0) / 100).toFixed(2)))
  const orderData = trend.map(d => d.paid_order_count || 0)

  salesChart?.setOption({
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, data: ['销售额(元)', '订单数'] },
    grid: { left: '3%', right: '4%', top: 20, bottom: 40, containLabel: true },
    xAxis: { type: 'category', data: labels.length ? labels : ['暂无数据'], axisLabel: { color: '#86909C' } },
    yAxis: [
      { type: 'value', name: '销售额', axisLabel: { color: '#86909C' } },
      { type: 'value', name: '订单数', axisLabel: { color: '#86909C' } },
    ],
    series: [
      { name: '销售额(元)', type: 'line', smooth: true, data: salesData, itemStyle: { color: '#409EFF' }, areaStyle: { opacity: 0.1 } },
      { name: '订单数', type: 'bar', yAxisIndex: 1, data: orderData, itemStyle: { color: '#67C23A' }, barWidth: 16 },
    ],
  })
}

onMounted(async () => {
  await fetchData()
  nextTick(() => renderSalesChart())
})
</script>

<template>
  <div v-loading="loading" class="mine-layout">
    <!-- 欢迎横幅 -->
    <div class="mine-card flex justify-between p-4">
      <div class="flex gap-x-5">
        <el-avatar :src="userinfo?.avatar" :size="70">
          <span v-if="!userinfo?.avatar" class="text-4xl">{{ userinfo?.username?.[0]?.toUpperCase() }}</span>
        </el-avatar>
        <div class="flex flex-col justify-center gap-y-1">
          <span class="text-xl">{{ greetingText }}，{{ userinfo?.nickname || userinfo?.username }}！欢迎回到商城管理后台</span>
          <span class="text-sm text-gray-4">MineShop 商城管理系统 — 让运营更高效</span>
        </div>
      </div>
    </div>

    <!-- 今日实时数据 -->
    <div v-if="data" class="grid grid-cols-2 gap-3 mt-3 mx-3 lg:grid-cols-4">
      <div class="stat-card bg-blue-50 dark-bg-blue-900/20">
        <div class="text-sm text-gray-5">今日订单</div>
        <div class="text-2xl font-bold text-blue-6 mt-1">{{ data.today.orders }}</div>
      </div>
      <div class="stat-card bg-green-50 dark-bg-green-900/20">
        <div class="text-sm text-gray-5">今日销售额</div>
        <div class="text-2xl font-bold text-green-6 mt-1">¥{{ formatAmount(data.today.sales) }}</div>
      </div>
      <div class="stat-card bg-purple-50 dark-bg-purple-900/20">
        <div class="text-sm text-gray-5">今日新用户</div>
        <div class="text-2xl font-bold text-purple-6 mt-1">{{ data.today.new_members }}</div>
      </div>
      <div class="stat-card bg-orange-50 dark-bg-orange-900/20">
        <div class="text-sm text-gray-5">今日活跃用户</div>
        <div class="text-2xl font-bold text-orange-6 mt-1">{{ data.today.active_members }}</div>
      </div>
    </div>

    <!-- 待处理事项 + 总览 -->
    <div v-if="data" class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
      <div class="mine-card">
        <div class="text-base font-medium mb-3">待处理事项</div>
        <div class="grid grid-cols-2 gap-3">
          <div class="flex items-center gap-3 p-3 bg-red-50 dark-bg-red-900/10 rounded">
            <ma-svg-icon name="heroicons:clock" :size="28" class="text-red-5" />
            <div>
              <div class="text-xs text-gray-5">待付款订单</div>
              <div class="text-lg font-bold text-red-5">{{ data.pending.pending_payment }}</div>
            </div>
          </div>
          <div class="flex items-center gap-3 p-3 bg-yellow-50 dark-bg-yellow-900/10 rounded">
            <ma-svg-icon name="heroicons:truck" :size="28" class="text-yellow-6" />
            <div>
              <div class="text-xs text-gray-5">待发货订单</div>
              <div class="text-lg font-bold text-yellow-6">{{ data.pending.pending_shipment }}</div>
            </div>
          </div>
          <div class="flex items-center gap-3 p-3 bg-orange-50 dark-bg-orange-900/10 rounded">
            <ma-svg-icon name="heroicons:exclamation-triangle" :size="28" class="text-orange-5" />
            <div>
              <div class="text-xs text-gray-5">库存预警</div>
              <div class="text-lg font-bold text-orange-5">{{ data.pending.low_stock }}</div>
            </div>
          </div>
          <div class="flex items-center gap-3 p-3 bg-gray-50 dark-bg-gray-900/10 rounded">
            <ma-svg-icon name="heroicons:x-circle" :size="28" class="text-gray-5" />
            <div>
              <div class="text-xs text-gray-5">已售罄商品</div>
              <div class="text-lg font-bold text-gray-6">{{ data.pending.out_of_stock }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="mine-card">
        <div class="text-base font-medium mb-3">商城总览</div>
        <div class="grid grid-cols-2 gap-3">
          <div class="text-center p-3">
            <div class="text-2xl font-bold text-blue-6">{{ data.overview.total_members }}</div>
            <div class="text-xs text-gray-5 mt-1">累计会员</div>
          </div>
          <div class="text-center p-3">
            <div class="text-2xl font-bold text-green-6">{{ data.overview.total_products }}</div>
            <div class="text-xs text-gray-5 mt-1">在售商品</div>
          </div>
          <div class="text-center p-3">
            <div class="text-2xl font-bold text-purple-6">{{ data.overview.total_orders }}</div>
            <div class="text-xs text-gray-5 mt-1">累计订单</div>
          </div>
          <div class="text-center p-3">
            <div class="text-2xl font-bold text-orange-6">¥{{ formatAmount(data.overview.total_sales) }}</div>
            <div class="text-xs text-gray-5 mt-1">累计销售额</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 近7天销售趋势 -->
    <div class="mine-card mt-3">
      <div class="text-base font-medium">近7天销售趋势</div>
      <div ref="salesChartEl" class="mt-3 h-300px" />
    </div>

    <!-- 热销商品 Top5 -->
    <div v-if="data" class="mine-card mt-3">
      <div class="text-base font-medium mb-3">近7天热销商品 Top5</div>
      <el-table :data="data.hot_products ?? []" stripe>
        <template #empty><el-empty description="暂无数据" :image-size="60" /></template>
        <el-table-column type="index" label="排名" width="70" align="center" />
        <el-table-column prop="product_name" label="商品名称" />
        <el-table-column prop="sales_count" label="销量" width="120" align="center" />
        <el-table-column label="销售额" width="150" align="center">
          <template #default="{ row }">
            ¥{{ formatAmount(row.sales_amount) }}
          </template>
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