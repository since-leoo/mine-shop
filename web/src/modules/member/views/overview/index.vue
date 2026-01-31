<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file distributed with this source code.
-->
<template>
  <div class="mine-layout member-analysis">
    <div class="mine-card">
      <div class="analysis-header">
        <div>
          <div class="title">会员数据驾驶舱</div>
          <div class="subtitle">对齐 dashboard/analysis 风格，随时替换为实时指标</div>
        </div>
        <el-button text type="primary" size="small" @click="loadOverview">
          <template #icon><el-icon><Refresh /></el-icon></template>
          刷新数据
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
        <div class="card-title">会员增长走势</div>
        <div class="card-subtitle">结合新增与活跃曲线，快速判断获客和留存趋势</div>
        <div ref="growthChartRef" class="chart-panel growth-chart" />
      </div>
      <div class="mine-card channel-card">
        <div class="card-title">渠道构成</div>
        <div class="card-subtitle">数据来自会员来源字段，支持筛选条件联动</div>
        <div class="channel-chart">
          <div ref="channelChartRef" class="chart-panel channel-pie" />
        </div>
        <div v-if="!channelBreakdown.length" class="empty-text">暂无数据</div>
        <div v-for="channel in channelBreakdown" :key="channel.label" class="channel-item">
          <div class="channel-header">
            <span>{{ channel.label }}</span>
            <span class="font-medium">{{ channel.percent }}%</span>
          </div>
          <el-progress :percentage="channel.percent" :stroke-width="10" :color="channel.color" />
          <div class="channel-remark">累计人数：{{ formatNumber(channel.value) }}</div>
        </div>
      </div>
    </div>

    <div class="analysis-row">
      <div class="mine-card flex-1">
        <div class="card-title">运营待办</div>
        <div class="card-subtitle">聚焦高优任务，辅助日常站会</div>
        <ul class="todo-list">
          <li v-for="todo in todoList" :key="todo.title">
            <div class="todo-main">
              <span class="todo-title">{{ todo.title }}</span>
              <span class="todo-priority" :class="todo.priority">{{ todo.priorityLabel }}</span>
            </div>
            <div class="todo-desc">{{ todo.desc }}</div>
            <div class="todo-meta">
              <span>负责人：{{ todo.owner }}</span>
              <span>截止：{{ todo.deadline }}</span>
            </div>
          </li>
        </ul>
      </div>
      <div class="mine-card flex-1">
        <div class="card-title">等级结构</div>
        <div class="card-subtitle">实时读取 mall_members.level 字段，展示会员等级分布</div>
        <div ref="levelChartRef" class="chart-panel level-chart" />
      </div>
    </div>

    <div class="analysis-row">
      <div class="mine-card flex-1">
        <div class="card-title">行为洞察</div>
        <div class="card-subtitle">后续可替换为漏斗或自定义图表</div>
        <el-table :data="behaviorInsights" size="small" border>
          <el-table-column prop="scenario" label="场景" min-width="140" />
          <el-table-column prop="conversion" label="转化率" width="120">
            <template #default="{ row }">
              <el-tag :type="row.conversion > 30 ? 'success' : 'info'">{{ row.conversion }}%</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="remark" label="备注" />
        </el-table>
      </div>
    </div>

    <div class="mine-card">
      <div class="table-header">
        <div>
          <div class="card-title">最新会员</div>
          <div class="card-subtitle">与分析页一致的卡片式列表，可快速查看画像</div>
        </div>
      </div>
      <el-table :data="recentMembers" stripe v-loading="loading">
        <el-table-column prop="nickname" label="昵称" min-width="160">
          <template #default="{ row }">
            <div class="flex items-center gap-2">
              <el-avatar :size="32" :src="row.avatar">
                {{ row.nickname?.slice(0, 1) || 'U' }}
              </el-avatar>
              <div>
                <div class="font-medium">{{ row.nickname || '未命名' }}</div>
                <div class="text-xs text-gray-500">ID: {{ row.id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="phone" label="手机号" width="140">
          <template #default="{ row }">
            {{ row.phone || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="等级" width="120">
          <template #default="{ row }">
            <el-tag size="small" type="warning">{{ levelLabelMap[row.level || 'bronze'] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="注册时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="状态" width="120">
          <template #default="{ row }">
            <el-tag :type="statusTagTypeMap[row.status]" size="small">
              {{ statusLabelMap[row.status] }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
      <el-empty v-if="!recentMembers.length && !loading" description="暂无数据" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import dayjs from 'dayjs'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { memberApi, type MallMember, type MemberBreakdownItem, type MemberOverviewResponse } from '~/member/api/member'
import { useEcharts } from '@/hooks/useEcharts.ts'

defineOptions({ name: 'member:overview' })

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
  source: [] as MemberBreakdownItem[],
  level: [] as MemberBreakdownItem[],
})

const statCards = [
  { key: 'total', label: '累计会员', trend: 18, desc: '较上周' },
  { key: 'new_today', label: '今日新增', trend: 6, desc: '实时刷新' },
  { key: 'active_30d', label: '30 日活跃', trend: 11, desc: '偏运营 KPI' },
  { key: 'sleeping_30d', label: '沉睡会员', trend: -4, desc: '需召回' },
] as const

const channelColors = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#9C27B0']
const levelColors = ['#8CC5FF', '#A0DEFF', '#FDBA74', '#F7797D', '#BB8FCE', '#67C23A']

const growthChartRef = ref<HTMLDivElement>()
const channelChartRef = ref<HTMLDivElement>()
const levelChartRef = ref<HTMLDivElement>()

const growthChart = useEcharts(growthChartRef)
const channelChart = useEcharts(channelChartRef)
const levelChart = useEcharts(levelChartRef)

const channelBreakdown = computed(() => {
  const total = overviewData.source.reduce((sum, item) => sum + item.value, 0)
  if (!total) {
    return []
  }
  return overviewData.source.map((item, index) => ({
    ...item,
    percent: Math.round(((item.value / total) || 0) * 100),
    color: channelColors[index % channelColors.length],
  }))
})

const todoList = ref([
  { title: '沉睡会员召回', desc: '针对 30 天未登录用户推送成长任务券包', owner: 'Mia', deadline: '今日', priority: 'high', priorityLabel: '高' },
  { title: '会员等级梳理', desc: '复核 LV3-LV4 条件，结合 GMV 微调', owner: 'Iris', deadline: '明日', priority: 'medium', priorityLabel: '中' },
  { title: '积分规则埋点', desc: '补齐钱包合并后的前端曝光位', owner: 'DevOps', deadline: '本周', priority: 'low', priorityLabel: '低' },
])

const behaviorInsights = ref([
  { scenario: '首单引导', conversion: 36, remark: '新人券+包邮组合继续保持' },
  { scenario: '复购激励', conversion: 24, remark: '需结合钱包余额推送个性化权益' },
  { scenario: '沉睡唤醒', conversion: 12, remark: '建议联动短信+小程序直播' },
])

const levelLabelMap: Record<string, string> = {
  bronze: '青铜',
  silver: '白银',
  gold: '黄金',
  diamond: '钻石',
}

const statusLabelMap: Record<string, string> = {
  active: '正常',
  inactive: '未激活',
  banned: '已禁用',
}

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
  overviewData.source = [...(payload?.source_breakdown ?? [])]
  overviewData.level = [...(payload?.level_breakdown ?? [])]
  await nextTick()
  renderTrendChart()
  renderChannelChart()
  renderLevelChart()
}

const renderTrendChart = () => {
  const option = {
    tooltip: { trigger: 'axis' },
    legend: { data: ['新增会员', '活跃会员'] },
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
        name: '新增会员',
        type: 'line',
        smooth: true,
        showSymbol: false,
        lineStyle: { width: 3, color: '#409EFF' },
        areaStyle: { color: 'rgba(64,158,255,0.12)' },
        data: overviewData.trend.newMembers,
      },
      {
        name: '活跃会员',
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

const renderChannelChart = () => {
  const data = overviewData.source.length
    ? overviewData.source.map((item, index) => ({
        value: item.value,
        name: item.label,
        itemStyle: { color: channelColors[index % channelColors.length] },
      }))
    : [{ value: 1, name: '暂无数据', itemStyle: { color: '#E4E7ED' } }]

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
  channelChart.setOption(option)
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
    ElMessage.error(error?.message || '加载会员概览失败')
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
