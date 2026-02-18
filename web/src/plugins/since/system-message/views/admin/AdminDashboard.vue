<template>
  <div class="admin-dashboard">
    <div class="dashboard-header">
      <h1>消息管理仪表板</h1>
      <div class="header-actions">
        <el-button type="primary" @click="$router.push('/admin/system-message/list')">
          消息列表
        </el-button>
        <el-button @click="refreshData" :loading="loading">
          刷新数据
        </el-button>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-cards">
      <el-row :gutter="16">
        <el-col :span="6">
          <el-card shadow="hover">
            <el-statistic title="总消息数" :value="statistics.total_messages">
              <template #prefix>
                <el-icon><Message /></el-icon>
              </template>
            </el-statistic>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover">
            <el-statistic title="今日发送" :value="statistics.today_sent">
              <template #prefix>
                <el-icon><Promotion /></el-icon>
              </template>
            </el-statistic>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover">
            <el-statistic title="未读消息" :value="statistics.unread_count">
              <template #prefix>
                <el-icon><Bell /></el-icon>
              </template>
            </el-statistic>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover">
            <el-statistic title="活跃用户" :value="statistics.active_users">
              <template #prefix>
                <el-icon><User /></el-icon>
              </template>
            </el-statistic>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- 图表区域 -->
    <div class="charts-section">
      <el-row :gutter="16">
        <el-col :span="12">
          <el-card class="chart-card" shadow="hover">
            <template #header>
              <span>消息发送趋势</span>
            </template>
            <div ref="messageChartRef" class="chart-container"></div>
          </el-card>
        </el-col>
        <el-col :span="12">
          <el-card class="chart-card" shadow="hover">
            <template #header>
              <span>消息类型分布</span>
            </template>
            <div ref="typeChartRef" class="chart-container"></div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- 最近活动 -->
    <div class="recent-section">
      <el-row :gutter="16">
        <el-col :span="12">
          <el-card class="recent-card" shadow="hover">
            <template #header>
              <div class="card-header">
                <span>最近消息</span>
                <el-button type="primary" link @click="$router.push('/admin/message')">
                  查看全部
                </el-button>
              </div>
            </template>
            
            <el-skeleton :loading="loading" animated :rows="5">
              <div class="message-list" v-if="recentMessages.length > 0">
                <div 
                  v-for="item in recentMessages" 
                  :key="item.id"
                  class="message-item"
                  @click="viewMessage(item)"
                >
                  <div class="message-info">
                    <div class="message-title">{{ item.title }}</div>
                    <div class="message-meta">
                      <el-tag :type="getTypeTagType(item.type)" size="small">
                        {{ getTypeLabel(item.type) }}
                      </el-tag>
                      <span class="time">{{ formatTime(item.created_at) }}</span>
                    </div>
                  </div>
                  <el-tag :type="getStatusTagType(item.status)" size="small">
                    {{ getStatusLabel(item.status) }}
                  </el-tag>
                </div>
              </div>
              <el-empty v-else description="暂无消息" :image-size="80" />
            </el-skeleton>
          </el-card>
        </el-col>
        
        <el-col :span="12">
          <el-card class="status-card" shadow="hover">
            <template #header>
              <span>系统状态</span>
            </template>
            <div class="status-items">
              <div class="status-item">
                <div class="status-label">消息队列</div>
                <div class="status-value">
                  <el-tag :type="queueStatus.type">
                    {{ queueStatus.text }}
                  </el-tag>
                </div>
              </div>
              
              <div class="status-item">
                <div class="status-label">数据库服务</div>
                <div class="status-value">
                  <el-tag :type="databaseStatus.type">
                    {{ databaseStatus.text }}
                  </el-tag>
                </div>
              </div>
              
              <div class="status-item">
                <div class="status-label">邮件服务</div>
                <div class="status-value">
                  <el-tag :type="emailStatus.type">
                    {{ emailStatus.text }}
                  </el-tag>
                </div>
              </div>
              
              <div class="status-item">
                <div class="status-label">短信服务</div>
                <div class="status-value">
                  <el-tag :type="smsStatus.type">
                    {{ smsStatus.text }}
                  </el-tag>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { messageAdminApi } from '../../api/message'
import { ElMessage } from 'element-plus'
import { Message, Promotion, Bell, User } from '@element-plus/icons-vue'
import type { Message as MessageType } from '../../api/message'
import dayjs from 'dayjs'
import * as echarts from 'echarts'

// 定义组件名称，用于缓存
defineOptions({
  name: 'AdminDashboard'
})

const router = useRouter()

// 响应式数据
const loading = ref(false)
const statistics = ref({
  total_messages: 0,
  today_sent: 0,
  unread_count: 0,
  active_users: 0
})

const recentMessages = ref<MessageType[]>([])
const messageChartRef = ref<HTMLElement>()
const typeChartRef = ref<HTMLElement>()

// ECharts 实例
let messageChart: echarts.ECharts | null = null
let typeChart: echarts.ECharts | null = null

// 图表数据
const trendData = ref({
  dates: [] as string[],
  values: [] as number[]
})

const typeDistribution = ref([
  { name: '系统消息', value: 0 },
  { name: '公告', value: 0 },
  { name: '警报', value: 0 },
  { name: '提醒', value: 0 },
  { name: '营销', value: 0 }
])

// 系统状态 - 使用 'primary' 替代空字符串
const queueStatus = ref({ type: 'success' as 'primary' | 'success' | 'warning' | 'danger' | 'info', text: '正常运行' })
const databaseStatus = ref({ type: 'success' as 'primary' | 'success' | 'warning' | 'danger' | 'info', text: '正常' })
const emailStatus = ref({ type: 'success' as 'primary' | 'success' | 'warning' | 'danger' | 'info', text: '正常' })
const smsStatus = ref({ type: 'warning' as 'primary' | 'success' | 'warning' | 'danger' | 'info', text: '部分异常' })

// 获取消息类型 Tag 类型
const getTypeTagType = (type: string): 'primary' | 'success' | 'warning' | 'info' | 'danger' => {
  const types: Record<string, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    system: 'primary',
    announcement: 'success',
    alert: 'danger',
    reminder: 'warning',
    marketing: 'info'
  }
  return types[type] || 'info'
}

// 获取消息类型标签
const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    system: '系统',
    announcement: '公告',
    alert: '警报',
    reminder: '提醒',
    marketing: '营销'
  }
  return labels[type] || type
}

// 获取状态 Tag 类型
const getStatusTagType = (status: string): 'primary' | 'success' | 'warning' | 'info' | 'danger' => {
  const types: Record<string, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    draft: 'info',
    scheduled: 'warning',
    sending: 'primary',
    sent: 'success',
    failed: 'danger'
  }
  return types[status] || 'info'
}

// 获取状态标签
const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    draft: '草稿',
    scheduled: '已调度',
    sending: '发送中',
    sent: '已发送',
    failed: '发送失败'
  }
  return labels[status] || status
}

// 格式化时间
const formatTime = (time: string) => {
  return dayjs(time).format('MM-DD HH:mm')
}

// 查看消息详情
const viewMessage = (msg: MessageType) => {
  router.push(`/admin/message/${msg.id}`)
}

// 加载统计数据
const loadStatistics = async () => {
  try {
    const res = await messageAdminApi.getStatistics()
    const data = res.data ?? res as any

    const msgStats = data?.messages ?? {}
    const userMsgStats = data?.user_messages ?? {}

    statistics.value = {
      total_messages: msgStats.total ?? 0,
      today_sent: msgStats.sent ?? 0,
      unread_count: userMsgStats.unread ?? 0,
      active_users: userMsgStats.total ?? 0
    }

    // 趋势数据 — 从 recent 字段（date => count）构建
    const recent: Record<string, number> = msgStats.recent ?? {}
    const dates: string[] = []
    const values: number[] = []
    for (let i = 6; i >= 0; i--) {
      const d = dayjs().subtract(i, 'day')
      dates.push(d.format('MM-DD'))
      values.push(recent[d.format('YYYY-MM-DD')] ?? 0)
    }
    trendData.value = { dates, values }

    // 类型分布数据
    const byType: Record<string, number> = msgStats.by_type ?? {}
    const typeMap: Record<string, string> = {
      system: '系统消息', announcement: '公告', alert: '警报', reminder: '提醒', marketing: '营销'
    }
    typeDistribution.value = Object.entries(typeMap).map(([key, name]) => ({
      name,
      value: byType[key] ?? 0
    }))
  } catch (error) {
    console.error('Failed to load statistics:', error)
  }
}

// 加载最近消息
const loadRecentMessages = async () => {
  try {
    const res = await messageAdminApi.getRecent(7, 5)
    const list = res.data ?? res as any
    recentMessages.value = Array.isArray(list) ? list : []
  } catch (error) {
    console.error('Failed to load recent messages:', error)
    recentMessages.value = []
  }
}

// 初始化图表
const initCharts = async () => {
  await nextTick()
  
  // 初始化消息趋势图
  if (messageChartRef.value) {
    messageChart = echarts.init(messageChartRef.value)
    updateMessageChart()
  }
  
  // 初始化类型分布图
  if (typeChartRef.value) {
    typeChart = echarts.init(typeChartRef.value)
    updateTypeChart()
  }
  
  // 监听窗口大小变化
  window.addEventListener('resize', handleResize)
}

// 更新消息趋势图
const updateMessageChart = () => {
  if (!messageChart) return
  
  // 生成最近7天的日期
  const dates = trendData.value.dates.length > 0 
    ? trendData.value.dates 
    : Array.from({ length: 7 }, (_, i) => dayjs().subtract(6 - i, 'day').format('MM-DD'))
  
  const values = trendData.value.values.length > 0 
    ? trendData.value.values 
    : Array(7).fill(0)
  
  const option: echarts.EChartsOption = {
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      top: '10%',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      data: dates,
      axisLine: {
        lineStyle: {
          color: '#ddd'
        }
      },
      axisLabel: {
        color: '#666'
      }
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      axisLine: {
        show: false
      },
      axisTick: {
        show: false
      },
      axisLabel: {
        color: '#666'
      },
      splitLine: {
        lineStyle: {
          color: '#eee'
        }
      }
    },
    series: [
      {
        name: '发送数量',
        type: 'bar',
        data: values,
        itemStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: '#409EFF' },
            { offset: 1, color: '#79bbff' }
          ]),
          borderRadius: [4, 4, 0, 0]
        },
        emphasis: {
          itemStyle: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
              { offset: 0, color: '#337ecc' },
              { offset: 1, color: '#409EFF' }
            ])
          }
        },
        barWidth: '60%'
      }
    ]
  }
  
  // 如果没有数据，显示空状态提示
  if (values.every(v => v === 0)) {
    option.graphic = {
      type: 'text',
      left: 'center',
      top: 'middle',
      style: {
        text: '暂无数据',
        fontSize: 14,
        fill: '#999'
      }
    }
  }
  
  messageChart.setOption(option)
}

// 更新类型分布图
const updateTypeChart = () => {
  if (!typeChart) return
  
  const data = typeDistribution.value
  const hasData = data.some(item => item.value > 0)
  
  const option: echarts.EChartsOption = {
    tooltip: {
      trigger: 'item',
      formatter: '{a} <br/>{b}: {c} ({d}%)'
    },
    legend: {
      orient: 'vertical',
      right: '5%',
      top: 'center',
      itemWidth: 12,
      itemHeight: 12,
      textStyle: {
        color: '#666'
      }
    },
    series: [
      {
        name: '消息类型',
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['40%', '50%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 6,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 16,
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: false
        },
        data: hasData ? data : [
          { name: '系统消息', value: 1 },
          { name: '公告', value: 1 },
          { name: '警报', value: 1 },
          { name: '提醒', value: 1 },
          { name: '营销', value: 1 }
        ],
        color: ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399']
      }
    ]
  }
  
  // 如果没有数据，显示空状态提示并使用灰色
  if (!hasData) {
    option.series = [{
      name: '消息类型',
      type: 'pie',
      radius: ['40%', '70%'],
      center: ['40%', '50%'],
      avoidLabelOverlap: false,
      itemStyle: {
        borderRadius: 6,
        borderColor: '#fff',
        borderWidth: 2,
        color: '#e0e0e0'
      },
      label: {
        show: false
      },
      emphasis: {
        disabled: true
      },
      labelLine: {
        show: false
      },
      data: [{ name: '暂无数据', value: 1 }]
    }]
    option.graphic = {
      type: 'text',
      left: '35%',
      top: 'middle',
      style: {
        text: '暂无数据',
        fontSize: 14,
        fill: '#999'
      }
    }
    option.legend = {
      show: false
    }
  }
  
  typeChart.setOption(option)
}

// 处理窗口大小变化
const handleResize = () => {
  messageChart?.resize()
  typeChart?.resize()
}

// 销毁图表
const destroyCharts = () => {
  window.removeEventListener('resize', handleResize)
  messageChart?.dispose()
  typeChart?.dispose()
  messageChart = null
  typeChart = null
}

// 刷新数据
const refreshData = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadStatistics(),
      loadRecentMessages()
    ])
    // 更新图表
    updateMessageChart()
    updateTypeChart()
    ElMessage.success('数据已刷新')
  } catch (error) {
    ElMessage.error('刷新失败')
  } finally {
    loading.value = false
  }
}

// 定时刷新
let refreshTimer: ReturnType<typeof setInterval> | null = null

const startAutoRefresh = () => {
  refreshTimer = setInterval(() => {
    refreshData()
  }, 30000) // 30秒刷新一次
}

const stopAutoRefresh = () => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
}

// 生命周期
onMounted(async () => {
  await refreshData()
  initCharts()
  startAutoRefresh()
})

onUnmounted(() => {
  stopAutoRefresh()
  destroyCharts()
})
</script>

<style scoped>
.admin-dashboard {
  padding: 24px;
  background: var(--el-bg-color-page);
  min-height: 100%;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.dashboard-header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.stats-cards {
  margin-bottom: 24px;
}

.charts-section {
  margin-bottom: 24px;
}

.chart-card {
  height: 400px;
}

.chart-container {
  height: 320px;
  width: 100%;
}

.recent-section {
  margin-bottom: 24px;
}

.recent-card {
  height: 400px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.message-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.message-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--el-fill-color-light);
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.message-item:hover {
  background: var(--el-fill-color);
}

.message-info {
  flex: 1;
}

.message-title {
  font-weight: 500;
  margin-bottom: 4px;
}

.message-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.time {
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.status-card {
  height: 400px;
}

.status-items {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.status-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--el-fill-color-light);
  border-radius: 6px;
}

.status-label {
  font-weight: 500;
}

.status-value {
  display: flex;
  align-items: center;
}
</style>
