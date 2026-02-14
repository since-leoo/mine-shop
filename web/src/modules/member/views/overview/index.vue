<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <div class="mine-layout member-analysis">
    <div class="mine-card">
      <div class="analysis-header">
        <div>
          <div class="title">{{ t('member.overview.title') }}</div>
          <div class="subtitle">{{ t('member.overview.subtitle') }}</div>
        </div>
        <el-button text type="primary" size="small" @click="loadOverview">
          <template #icon><el-icon><Refresh /></el-icon></template>
          {{ t('mall.refreshData') }}
        </el-button>
      </div>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in statCards" :key="card.key" class="kpi-card">
          <div class="kpi-label">{{ card.label }}</div>
          <div class="kpi-value">{{ formatNumber(stats[card.key]) }}</div>
          <div class="kpi-meta">
            <span :class="['trend', card.trend >= 0 ? 'trend-up' : 'trend-down']">
              {{ card.trend >= 0 ? '+' : '' }}{{ card.trend }}%
            </span>
            <span class="kpi-desc">{{ card.desc }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="analysis-row">
      <div class="mine-card flex-1">
        <div class="card-title">{{ t('member.overview.growthTrend') }}</div>
        <div class="card-subtitle">{{ t('member.overview.growthTrendDesc') }}</div>
        <div ref="growthChartRef" class="chart-panel growth-chart" />
      </div>
      <div class="mine-card channel-card">
        <div class="card-title">{{ t('member.overview.regionBreakdown') }}</div>
        <div class="card-subtitle">{{ t('member.overview.regionBreakdownDesc') }}</div>
        <div class="channel-chart">
          <div ref="regionChartRef" class="chart-panel channel-pie" />
        </div>
        <div v-if="!regionBreakdown.length" class="empty-text">{{ t('mall.noData') }}</div>
        <div v-for="region in regionBreakdown" :key="region.label" class="channel-item">
          <div class="channel-header">
            <span>{{ region.label }}</span>
            <span class="font-medium">{{ region.percent }}%</span>
          </div>
          <el-progress :percentage="region.percent" :stroke-width="10" :color="region.color" />
          <div class="channel-remark">{{ t('member.overview.regionTotal') }}：{{ formatNumber(region.value) }}</div>
        </div>
      </div>
    </div>

    <div class="analysis-row">
      <div class="mine-card flex-1">
        <div class="card-title">{{ t('member.overview.todoTitle') }}</div>
        <div class="card-subtitle">{{ t('member.overview.todoDesc') }}</div>
        <ul class="todo-list">
          <li v-for="todo in todoList" :key="todo.title">
            <div class="todo-main">
              <span class="todo-title">{{ todo.title }}</span>
              <span class="todo-priority" :class="todo.priority">{{ todo.priorityLabel }}</span>
            </div>
            <div class="todo-desc">{{ todo.desc }}</div>
            <div class="todo-meta">
              <span>{{ t('member.overview.owner') }}：{{ todo.owner }}</span>
              <span>{{ t('member.overview.deadline') }}：{{ todo.deadline }}</span>
            </div>
          </li>
        </ul>
      </div>
      <div class="mine-card flex-1">
        <div class="card-title">{{ t('member.overview.levelStructure') }}</div>
        <div class="card-subtitle">{{ t('member.overview.levelStructureDesc') }}</div>
        <div ref="levelChartRef" class="chart-panel level-chart" />
      </div>
    </div>

    <div class="analysis-row">
      <div class="mine-card flex-1">
        <div class="card-title">{{ t('member.overview.behaviorInsight') }}</div>
        <div class="card-subtitle">{{ t('member.overview.behaviorInsightDesc') }}</div>
        <el-table :data="behaviorInsights" size="small" border>
          <el-table-column prop="scenario" :label="t('member.overview.scenarioColumn')" min-width="140" />
          <el-table-column prop="conversion" :label="t('member.overview.conversionColumn')" width="120">
            <template #default="{ row }">
              <el-tag :type="row.conversion > 30 ? 'success' : 'info'">{{ row.conversion }}%</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="remark" :label="t('member.overview.remarkColumn')" />
        </el-table>
      </div>
    </div>

    <div class="mine-card">
      <div class="table-header">
        <div>
          <div class="card-title">{{ t('member.overview.recentMembers') }}</div>
          <div class="card-subtitle">{{ t('member.overview.recentMembersDesc') }}</div>
        </div>
      </div>
      <el-table :data="recentMembers" stripe v-loading="loading">
        <el-table-column prop="nickname" :label="t('member.overview.nickname')" min-width="160">
          <template #default="{ row }">
            <div class="flex items-center gap-2">
              <el-avatar :size="32" :src="row.avatar">
                {{ row.nickname?.slice(0, 1) || 'U' }}
              </el-avatar>
              <div>
                <div class="font-medium">{{ row.nickname || t('member.overview.unnamed') }}</div>
                <div class="text-xs text-gray-500">ID: {{ row.id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="phone" :label="t('member.overview.phoneColumn')" width="140">
          <template #default="{ row }">
            {{ row.phone || '-' }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.overview.levelColumn')" width="120">
          <template #default="{ row }">
            <el-tag size="small" type="warning">{{ levelLabelMap[row.level || 'bronze'] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('member.overview.registeredAt')" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column :label="t('member.overview.statusColumn')" width="120">
          <template #default="{ row }">
            <el-tag :type="statusTagTypeMap[row.status]" size="small">
              {{ statusLabelMap[row.status] }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
      <el-empty v-if="!recentMembers.length && !loading" :description="t('mall.noData')" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { memberApi, type MallMember, type MemberBreakdownItem, type MemberOverviewResponse } from '~/member/api/member'
import { useEcharts } from '@/hooks/useEcharts.ts'

defineOptions({ name: 'member:overview' })

const { t } = useI18n()

const loading = ref(false)
const stats = reactive({
  total: 0,
  new_today: 0,
  active_30d: 0,
  sleeping_30d: 0,
  banned: 0,
})
const recentMembers = ref<MallMember[]>([])
const overviewData = reactive({
  trend: {
    labels: [] as string[],
    newMembers: [] as number[],
    activeMembers: [] as number[],
  },
  region: [] as MemberBreakdownItem[],
  level: [] as MemberBreakdownItem[],
})

const statCards = computed(() => [
  { key: 'total', label: t('member.overview.totalMembers'), trend: 18, desc: t('member.overview.vsLastWeek') },
  { key: 'new_today', label: t('member.overview.newToday'), trend: 6, desc: t('member.overview.realtime') },
  { key: 'active_30d', label: t('member.overview.active30d'), trend: 11, desc: t('member.overview.kpiOps') },
  { key: 'sleeping_30d', label: t('member.overview.sleeping30d'), trend: -4, desc: t('member.overview.needRecall') },
] as const)

const regionColors = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#9C27B0']
const levelColors = ['#8CC5FF', '#A0DEFF', '#FDBA74', '#F7797D', '#BB8FCE', '#67C23A']

const growthChartRef = ref<HTMLDivElement>()
const regionChartRef = ref<HTMLDivElement>()
const levelChartRef = ref<HTMLDivElement>()

const growthChart = useEcharts(growthChartRef)
const regionChart = useEcharts(regionChartRef)
const levelChart = useEcharts(levelChartRef)

const regionBreakdown = computed(() => {
  const total = overviewData.region.reduce((sum, item) => sum + item.value, 0)
  if (!total) {
    return []
  }
  return overviewData.region.map((item, index) => ({
    ...item,
    percent: Math.round(((item.value / total) || 0) * 100),
    color: regionColors[index % regionColors.length],
  }))
})

const todoList = computed(() => [
  { title: t('member.overview.todoRecall'), desc: t('member.overview.todoRecallDesc'), owner: 'Mia', deadline: t('member.overview.today'), priority: 'high', priorityLabel: t('member.overview.priorityHigh') },
  { title: t('member.overview.todoLevel'), desc: t('member.overview.todoLevelDesc'), owner: 'Iris', deadline: t('member.overview.tomorrow'), priority: 'medium', priorityLabel: t('member.overview.priorityMedium') },
  { title: t('member.overview.todoPoints'), desc: t('member.overview.todoPointsDesc'), owner: 'DevOps', deadline: t('member.overview.thisWeek'), priority: 'low', priorityLabel: t('member.overview.priorityLow') },
])

const behaviorInsights = computed(() => [
  { scenario: t('member.overview.scenarioFirstOrder'), conversion: 36, remark: t('member.overview.scenarioFirstOrderRemark') },
  { scenario: t('member.overview.scenarioRepurchase'), conversion: 24, remark: t('member.overview.scenarioRepurchaseRemark') },
  { scenario: t('member.overview.scenarioRecall'), conversion: 12, remark: t('member.overview.scenarioRecallRemark') },
])

const levelLabelMap = computed<Record<string, string>>(() => ({
  bronze: t('member.level.bronze'),
  silver: t('member.level.silver'),
  gold: t('member.level.gold'),
  diamond: t('member.level.diamond'),
}))

const statusLabelMap = computed<Record<string, string>>(() => ({
  active: t('member.status.active'),
  inactive: t('member.status.inactive'),
  banned: t('member.status.banned'),
}))

const statusTagTypeMap: Record<string, 'success' | 'info' | 'danger'> = {
  active: 'success',
  inactive: 'info',
  banned: 'danger',
}

const formatNumber = (value?: number) => Intl.NumberFormat('zh-CN').format(value ?? 0)
const formatDateTime = (value?: string | null) => (value ? dayjs(value).format('YYYY-MM-DD HH:mm') : '-')

const applyOverviewPayload = async (payload?: MemberOverviewResponse) => {
  overviewData.trend.labels = [...(payload?.trend?.labels ?? [])]
  overviewData.trend.newMembers = [...(payload?.trend?.new_members ?? [])]
  overviewData.trend.activeMembers = [...(payload?.trend?.active_members ?? [])]
  overviewData.region = [...(payload?.region_breakdown ?? [])]
  overviewData.level = [...(payload?.level_breakdown ?? [])]
  await nextTick()
  renderTrendChart()
  renderRegionChart()
  renderLevelChart()
}

const renderTrendChart = () => {
  const option = {
    tooltip: { trigger: 'axis' },
    legend: { data: [t('member.overview.newMemberLegend'), t('member.overview.activeMemberLegend')] },
    grid: { left: '2%', right: '3%', top: 40, bottom: 10, containLabel: true },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: overviewData.trend.labels,
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: '#ebeef5' } },
    },
    series: [
      {
        name: t('member.overview.newMemberLegend'),
        type: 'line',
        smooth: true,
        showSymbol: false,
        lineStyle: { width: 3, color: '#409EFF' },
        areaStyle: { color: 'rgba(64,158,255,0.12)' },
        data: overviewData.trend.newMembers,
      },
      {
        name: t('member.overview.activeMemberLegend'),
        type: 'line',
        smooth: true,
        showSymbol: false,
        lineStyle: { width: 3, color: '#67C23A' },
        areaStyle: { color: 'rgba(103,194,58,0.10)' },
        data: overviewData.trend.activeMembers,
      },
    ],
  }
  growthChart.setOption(option)
}

const renderRegionChart = () => {
  const data = overviewData.region.length
    ? overviewData.region.map((item, index) => ({
        value: item.value,
        name: item.label,
        itemStyle: { color: regionColors[index % regionColors.length] },
      }))
    : [{ value: 1, name: t('mall.noData'), itemStyle: { color: '#E4E7ED' } }]

  const option = {
    tooltip: { trigger: 'item' },
    legend: { bottom: 0, left: 'center' },
    series: [
      {
        type: 'pie',
        radius: ['45%', '70%'],
        avoidLabelOverlap: true,
        label: { show: false },
        data,
      },
    ],
  }
  regionChart.setOption(option)
}

const renderLevelChart = () => {
  const categories = overviewData.level.map(item => item.label)
  const data = overviewData.level.map(item => item.value)
  const option = {
    grid: { left: '4%', right: '4%', top: 10, bottom: 20, containLabel: true },
    xAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: '#ebeef5' } },
    },
    yAxis: {
      type: 'category',
      data: categories,
      axisTick: { show: false },
      axisLine: { show: false },
    },
    series: [
      {
        type: 'bar',
        data,
        barWidth: 18,
        itemStyle: {
          borderRadius: [0, 8, 8, 0],
          color: (params: { dataIndex: number }) => levelColors[params.dataIndex % levelColors.length],
        },
        label: {
          show: true,
          position: 'right',
        },
      },
    ],
  }
  levelChart.setOption(option)
}

const loadOverview = async () => {
  loading.value = true
  try {
    const [statsRes, listRes, overviewRes] = await Promise.all([
      memberApi.stats(),
      memberApi.list({ page: 1, page_size: 6 }),
      memberApi.overview(),
    ])
    Object.assign(stats, statsRes.data)
    recentMembers.value = listRes.data.list
    await applyOverviewPayload(overviewRes.data)
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('member.overview.loadFailed'))
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  loadOverview()
})
</script>

<style scoped>
.member-analysis .mine-card + .mine-card {
  margin-top: 16px;
}

.analysis-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.analysis-header .title {
  font-size: 20px;
  font-weight: 600;
}

.analysis-header .subtitle {
  font-size: 13px;
  color: #909399;
}

.kpi-card {
  background: rgba(64, 158, 255, 0.05);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(64, 158, 255, 0.08);
}

.kpi-label {
  font-size: 13px;
  color: #909399;
}

.kpi-value {
  font-size: 28px;
  font-weight: 600;
  margin: 8px 0;
}

.kpi-meta {
  font-size: 12px;
  color: #909399;
  display: flex;
  align-items: center;
  gap: 8px;
}

.trend {
  font-weight: 600;
}

.trend-up {
  color: #67c23a;
}

.trend-down {
  color: #f56c6c;
}

.analysis-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 16px;
}

.channel-card {
  width: 100%;
  max-width: 420px;
}

.chart-panel {
  width: 100%;
  min-height: 260px;
}

.growth-chart {
  min-height: 320px;
}

.channel-chart {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}

.channel-pie {
  height: 220px;
  width: 220px;
}

.channel-item {
  padding: 12px 0;
  border-bottom: 1px dashed #ebeef5;
}

.channel-item:last-child {
  border-bottom: none;
}

.channel-header {
  display: flex;
  justify-content: space-between;
  font-weight: 500;
  margin-bottom: 6px;
}

.channel-remark {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.empty-text {
  text-align: center;
  color: #a8abb2;
  font-size: 13px;
  margin-bottom: 8px;
}

.card-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 4px;
}

.card-subtitle {
  font-size: 13px;
  color: #a0a0a0;
  margin-bottom: 12px;
}

.todo-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.todo-list li {
  padding: 12px 14px;
  border-radius: 10px;
  border: 1px solid #ebeef5;
  background: #fafafa;
}

.todo-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.todo-title {
  font-weight: 600;
}

.todo-priority {
  border-radius: 4px;
  padding: 0 8px;
  font-size: 12px;
  color: #fff;
}

.todo-priority.high {
  background: #f56c6c;
}

.todo-priority.medium {
  background: #e6a23c;
}

.todo-priority.low {
  background: #909399;
}

.todo-desc {
  font-size: 13px;
  color: #606266;
  margin-bottom: 4px;
}

.todo-meta {
  font-size: 12px;
  color: #a0a0a0;
  display: flex;
  justify-content: space-between;
}

.table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
</style>
