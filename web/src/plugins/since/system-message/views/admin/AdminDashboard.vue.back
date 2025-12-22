<template>
  <div class="admin-dashboard">
    <div class="dashboard-header">
      <h1>消息管理仪表板</h1>
      <div class="header-actions">
        <a-button type="primary" @click="$router.push('/admin/message/create')">
          创建消息
        </a-button>
        <a-button @click="refreshData" :loading="loading">
          刷新数据
        </a-button>
      </div>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-cards">
      <a-row :gutter="16">
        <a-col :span="6">
          <a-card>
            <a-statistic
              title="总消息数"
              :value="statistics.total_messages"
              :loading="loading"
            >
              <template #prefix>
                <MessageOutlined />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :span="6">
          <a-card>
            <a-statistic
              title="今日发送"
              :value="statistics.today_sent"
              :loading="loading"
            >
              <template #prefix>
                <SendOutlined />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :span="6">
          <a-card>
            <a-statistic
              title="未读消息"
              :value="statistics.unread_count"
              :loading="loading"
            >
              <template #prefix>
                <BellOutlined />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :span="6">
          <a-card>
            <a-statistic
              title="活跃用户"
              :value="statistics.active_users"
              :loading="loading"
            >
              <template #prefix>
                <UserOutlined />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
      </a-row>
    </div>

    <!-- 图表区域 -->
    <div class="charts-section">
      <a-row :gutter="16">
        <a-col :span="12">
          <a-card title="消息发送趋势" class="chart-card">
            <div ref="messageChartRef" class="chart-container"></div>
          </a-card>
        </a-col>
        <a-col :span="12">
          <a-card title="消息类型分布" class="chart-card">
            <div ref="typeChartRef" class="chart-container"></div>
          </a-card>
        </a-col>
      </a-row>
    </div>

    <!-- 最近活动 -->
    <div class="recent-section">
      <a-row :gutter="16">
        <a-col :span="12">
          <a-card title="最近消息" class="recent-card">
            <template #extra>
              <a-button type="link" @click="$router.push('/admin/message')">
                查看全部
              </a-button>
            </template>
            
            <a-list
              :data-source="recentMessages"
              :loading="loading"
              size="small"
            >
              <template #renderItem="{ item }">
                <a-list-item>
                  <a-list-item-meta>
                    <template #title>
                      <a @click="viewMessage(item)">{{ item.title }}</a>
                    </template>
                    <template #description>
                      <div class="message-meta">
                        <a-tag :color="getTypeColor(item.type)" size="small">
                          {{ getTypeLabel(item.type) }}
                        </a-tag>
                        <span class="time">{{ formatTime(item.created_at) }}</span>
                      </div>
                    </template>
                  </a-list-item-meta>
                  <template #actions>
                    <a-tag :color="getStatusColor(item.status)">
                      {{ getStatusLabel(item.status) }}
                    </a-tag>
                  </template>
                </a-list-item>
              </template>
            </a-list>
          </a-card>
        </a-col>
        
        <a-col :span="12">
          <a-card title="系统状态" class="status-card">
            <div class="status-items">
              <div class="status-item">
                <div class="status-label">消息队列</div>
                <div class="status-value">
                  <a-badge 
                    :status="queueStatus.status" 
                    :text="queueStatus.text"
                  />
                </div>
              </div>
              
              <div class="status-item">
                <div class="status-label">WebSocket连接</div>
                <div class="status-value">
                  <a-badge 
                    :status="websocketStatus.status" 
                    :text="websocketStatus.text"
                  />
                </div>
              </div>
              
              <div class="status-item">
                <div class="status-label">邮件服务</div>
                <div class="status-value">
                  <a-badge 
                    :status="emailStatus.status" 
                    :text="emailStatus.text"
                  />
                </div>
              </div>
              
              <div class="status-item">
                <div class="status-label">短信服务</div>
                <div class="status-value">
                  <a-badge 
                    :status="smsStatus.status" 
                    :text="smsStatus.text"
                  />
                </div>
              </div>
            </div>
          </a-card>
        </a-col>
      </a-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessageStore } from '../../store/message'
import { message } from 'ant-design-vue'
import { 
  MessageOutlined, 
  SendOutlined, 
  BellOutlined, 
  UserOutlined 
} from '@ant-design/icons-vue'
import type { Message } from '../../api/message'
import dayjs from 'dayjs'

const router = useRouter()
const messageStore = useMessageStore()

// 响应式数据
const loading = ref(false)
const statistics = ref({
  total_messages: 0,
  today_sent: 0,
  unread_count: 0,
  active_users: 0
})

const recentMessages = ref<Message[]>([])
const messageChartRef = ref<HTMLElement>()
const typeChartRef = ref<HTMLElement>()

// 系统状态
const queueStatus = ref({ status: 'processing', text: '正常运行' })
const websocketStatus = ref({ status: 'success', text: '已连接' })
const emailStatus = ref({ status: 'success', text: '正常' })
const smsStatus = ref({ status: 'warning', text: '部分异常' })

// 获取消息类型颜色
const getTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    system: 'blue',
    announcement: 'green',
    alert: 'red',
    reminder: 'orange',
    marketing: 'purple'
  }
  return colors[type] || 'default'
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

// 获取状态颜色
const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    draft: 'default',
    scheduled: 'orange',
    sending: 'processing',
    sent: 'success',
    failed: 'error'
  }
  return colors[status] || 'default'
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
const viewMessage = (msg: Message) => {
  router.push(`/admin/message/${msg.id}`)
}

// 加载统计数据
const loadStatistics = async () => {
  try {
    const response = await messageStore.adminActions.getStatistics()
    statistics.value = response.data
  } catch (error) {
    console.error('Failed to load statistics:', error)
  }
}

// 加载最近消息
const loadRecentMessages = async () => {
  try {
    const response = await messageStore.adminActions.getRecent(7, 10)
    recentMessages.value = response.data
  } catch (error) {
    console.error('Failed to load recent messages:', error)
  }
}

// 初始化图表
const initCharts = () => {
  // 这里可以集成 ECharts 或其他图表库
  // 暂时留空，等待具体图表库的集成
}

// 刷新数据
const refreshData = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadStatistics(),
      loadRecentMessages()
    ])
    message.success('数据已刷新')
  } catch (error) {
    message.error('刷新失败')
  } finally {
    loading.value = false
  }
}

// 定时刷新
let refreshTimer: NodeJS.Timeout | null = null

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
})
</script>

<style scoped>
.admin-dashboard {
  padding: 24px;
  background: #f5f5f5;
  min-height: 100vh;
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

.message-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.time {
  color: #999;
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
  background: #fafafa;
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