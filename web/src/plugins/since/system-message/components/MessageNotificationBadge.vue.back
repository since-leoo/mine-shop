<template>
  <div class="message-notification-badge">
    <a-badge 
      :count="unreadCount" 
      :show-zero="false"
      :overflow-count="99"
      @click="handleClick"
    >
      <slot>
        <a-button 
          type="text" 
          :icon="BellOutlined"
          class="notification-button"
          :class="{ 'has-unread': hasUnread }"
        />
      </slot>
    </a-badge>
    
    <!-- 通知弹窗 -->
    <a-dropdown 
      v-model:open="dropdownVisible"
      :trigger="['click']"
      placement="bottomRight"
      overlay-class-name="message-notification-dropdown"
    >
      <template #overlay>
        <div class="notification-panel">
          <div class="panel-header">
            <div class="header-title">
              <span>消息通知</span>
              <a-badge :count="unreadCount" :show-zero="false" />
            </div>
            <div class="header-actions">
              <a-button 
                type="link" 
                size="small" 
                @click="markAllAsRead"
                :disabled="!hasUnread"
              >
                全部已读
              </a-button>
              <a-button 
                type="link" 
                size="small" 
                @click="goToMessageCenter"
              >
                查看全部
              </a-button>
            </div>
          </div>
          
          <div class="panel-content">
            <div v-if="loading" class="loading-container">
              <a-spin />
            </div>
            
            <div v-else-if="recentMessages.length === 0" class="empty-container">
              <a-empty 
                description="暂无新消息" 
                :image="Empty.PRESENTED_IMAGE_SIMPLE"
              />
            </div>
            
            <div v-else class="messages-list">
              <div 
                v-for="msg in recentMessages" 
                :key="msg.id"
                class="message-item"
                :class="{ 'unread': !msg.is_read }"
                @click="viewMessage(msg)"
              >
                <div class="message-content">
                  <div class="message-title">{{ msg.message.title }}</div>
                  <div class="message-preview">{{ getMessagePreview(msg.message.content) }}</div>
                  <div class="message-meta">
                    <a-tag :color="getTypeColor(msg.message.type)" size="small">
                      {{ getTypeLabel(msg.message.type) }}
                    </a-tag>
                    <span class="time">{{ formatTime(msg.created_at) }}</span>
                  </div>
                </div>
                <div class="message-actions">
                  <a-button 
                    type="text" 
                    size="small"
                    @click.stop="markAsRead(msg)"
                    v-if="!msg.is_read"
                  >
                    标记已读
                  </a-button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="panel-footer" v-if="recentMessages.length > 0">
            <a-button type="link" @click="goToMessageCenter" block>
              查看更多消息
            </a-button>
          </div>
        </div>
      </template>
      
      <span></span>
    </a-dropdown>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessageStore } from '../store/message'
import { message, Empty } from 'ant-design-vue'
import { BellOutlined } from '@ant-design/icons-vue'
import type { UserMessage } from '../api/message'
import dayjs from 'dayjs'

interface Props {
  // 是否自动刷新
  autoRefresh?: boolean
  // 刷新间隔（毫秒）
  refreshInterval?: number
  // 显示的最大消息数量
  maxMessages?: number
  // 点击徽章时的行为
  clickBehavior?: 'dropdown' | 'navigate'
}

const props = withDefaults(defineProps<Props>(), {
  autoRefresh: true,
  refreshInterval: 30000, // 30秒
  maxMessages: 5,
  clickBehavior: 'dropdown'
})

const emit = defineEmits<{
  messageClick: [message: UserMessage]
  badgeClick: []
}>()

const router = useRouter()
const messageStore = useMessageStore()

// 状态
const loading = ref(false)
const dropdownVisible = ref(false)
const recentMessages = ref<UserMessage[]>([])
const refreshTimer = ref<NodeJS.Timeout | null>(null)

// 计算属性
const unreadCount = computed(() => messageStore.unreadCount)
const hasUnread = computed(() => unreadCount.value > 0)

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

// 格式化时间
const formatTime = (time: string) => {
  const now = dayjs()
  const msgTime = dayjs(time)
  const diffMinutes = now.diff(msgTime, 'minute')
  
  if (diffMinutes < 1) {
    return '刚刚'
  } else if (diffMinutes < 60) {
    return `${diffMinutes}分钟前`
  } else if (diffMinutes < 1440) {
    return `${Math.floor(diffMinutes / 60)}小时前`
  } else {
    return msgTime.format('MM-DD HH:mm')
  }
}

// 获取消息预览
const getMessagePreview = (content: string, maxLength = 50) => {
  if (content.length <= maxLength) {
    return content
  }
  return content.substring(0, maxLength) + '...'
}

// 处理徽章点击
const handleClick = () => {
  emit('badgeClick')
  
  if (props.clickBehavior === 'dropdown') {
    dropdownVisible.value = !dropdownVisible.value
    if (dropdownVisible.value) {
      loadRecentMessages()
    }
  } else {
    goToMessageCenter()
  }
}

// 加载最近消息
const loadRecentMessages = async () => {
  loading.value = true
  try {
    const response = await messageStore.userActions.getList({
      page: 1,
      page_size: props.maxMessages,
      is_read: false // 只获取未读消息
    })
    recentMessages.value = response.data.data
  } catch (error) {
    console.error('Failed to load recent messages:', error)
  } finally {
    loading.value = false
  }
}

// 查看消息详情
const viewMessage = (msg: UserMessage) => {
  emit('messageClick', msg)
  dropdownVisible.value = false
  router.push(`/message-center/detail/${msg.message_id}`)
}

// 标记消息为已读
const markAsRead = async (msg: UserMessage) => {
  try {
    await messageStore.userActions.markAsRead(msg.message_id)
    
    // 更新本地状态
    const index = recentMessages.value.findIndex(m => m.id === msg.id)
    if (index !== -1) {
      recentMessages.value[index].is_read = true
    }
    
    message.success('已标记为已读')
  } catch (error) {
    message.error('操作失败')
  }
}

// 标记所有消息为已读
const markAllAsRead = async () => {
  try {
    await messageStore.userActions.markAllAsRead()
    
    // 更新本地状态
    recentMessages.value.forEach(msg => {
      msg.is_read = true
    })
    
    message.success('所有消息已标记为已读')
  } catch (error) {
    message.error('操作失败')
  }
}

// 跳转到消息中心
const goToMessageCenter = () => {
  dropdownVisible.value = false
  router.push('/message-center')
}

// 刷新未读数量
const refreshUnreadCount = async () => {
  try {
    await messageStore.userActions.getUnreadCount()
  } catch (error) {
    console.error('Failed to refresh unread count:', error)
  }
}

// 开始自动刷新
const startAutoRefresh = () => {
  if (!props.autoRefresh) return
  
  refreshTimer.value = setInterval(() => {
    refreshUnreadCount()
  }, props.refreshInterval)
}

// 停止自动刷新
const stopAutoRefresh = () => {
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
    refreshTimer.value = null
  }
}

// 生命周期
onMounted(() => {
  refreshUnreadCount()
  startAutoRefresh()
})

onUnmounted(() => {
  stopAutoRefresh()
})

// 暴露方法给父组件
defineExpose({
  refresh: refreshUnreadCount,
  loadRecentMessages
})
</script>

<style scoped>
.message-notification-badge {
  display: inline-block;
}

.notification-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  transition: all 0.2s;
}

.notification-button:hover {
  background: #f0f0f0;
}

.notification-button.has-unread {
  color: #1890ff;
}

.notification-panel {
  width: 360px;
  max-height: 480px;
  background: #fff;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.panel-header {
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  background: #fafafa;
}

.header-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.panel-content {
  max-height: 320px;
  overflow-y: auto;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 120px;
}

.empty-container {
  padding: 24px;
}

.messages-list {
  padding: 8px 0;
}

.message-item {
  display: flex;
  align-items: flex-start;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.2s;
  border-left: 3px solid transparent;
}

.message-item:hover {
  background: #f5f5f5;
}

.message-item.unread {
  background: #f6ffed;
  border-left-color: #52c41a;
}

.message-content {
  flex: 1;
  min-width: 0;
}

.message-title {
  font-weight: 500;
  margin-bottom: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.message-preview {
  font-size: 12px;
  color: #666;
  margin-bottom: 6px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.message-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.time {
  font-size: 11px;
  color: #999;
}

.message-actions {
  margin-left: 8px;
}

.panel-footer {
  padding: 8px;
  border-top: 1px solid #f0f0f0;
  background: #fafafa;
}
</style>

<style>
.message-notification-dropdown .ant-dropdown-menu {
  padding: 0;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>