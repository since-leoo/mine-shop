<template>
  <div class="message-detail">
    <div class="detail-header">
      <el-button @click="goBack" text class="back-btn">
        <el-icon><ArrowLeft /></el-icon>
        返回
      </el-button>
      
      <div class="header-actions" v-if="userMessage">
        <el-button 
          @click="markAsRead" 
          v-if="!userMessage.is_read"
          type="primary"
        >
          标记已读
        </el-button>
        <el-popconfirm
          title="确定要删除这条消息吗？"
          @confirm="deleteMessage"
        >
          <template #reference>
            <el-button type="danger">删除</el-button>
          </template>
        </el-popconfirm>
      </div>
    </div>

    <div class="detail-content" v-if="userMessage">
      <div class="message-header">
        <h1 class="message-title">{{ userMessage.message.title }}</h1>
        <div class="message-meta">
          <el-tag :type="getTypeTagType(userMessage.message.type)">
            {{ getTypeLabel(userMessage.message.type) }}
          </el-tag>
          <span class="priority" :class="`priority-${userMessage.message.priority}`">
            {{ getPriorityLabel(userMessage.message.priority) }}
          </span>
          <span class="time">{{ formatTime(userMessage.created_at) }}</span>
          <el-tag :type="userMessage.is_read ? 'success' : 'warning'">
            {{ userMessage.is_read ? '已读' : '未读' }}
          </el-tag>
        </div>
      </div>

      <el-divider />

      <div class="message-content">
        <div class="content-body" v-html="formatContent(userMessage.message.content)"></div>
        
        <!-- 附加数据 -->
        <div class="extra-data" v-if="userMessage.message.extra_data">
          <h3>附加信息</h3>
          <el-descriptions :column="2" size="small" border>
            <el-descriptions-item 
              v-for="(value, key) in userMessage.message.extra_data" 
              :key="key"
              :label="String(key)"
            >
              {{ value }}
            </el-descriptions-item>
          </el-descriptions>
        </div>
      </div>

      <el-divider />

      <div class="message-footer">
        <div class="read-info" v-if="userMessage.is_read && userMessage.read_at">
          <span class="read-label">已读时间：</span>
          <span class="read-time">{{ formatTime(userMessage.read_at) }}</span>
        </div>
      </div>
    </div>

    <div class="loading-container" v-else-if="loading">
      <el-skeleton :rows="10" animated />
    </div>

    <div class="error-container" v-else>
      <el-result
        icon="warning"
        title="消息不存在"
        sub-title="您访问的消息可能已被删除或不存在"
      >
        <template #extra>
          <el-button type="primary" @click="goBack">返回列表</el-button>
        </template>
      </el-result>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMessageStore } from '../store/message'
import { ElMessage } from 'element-plus'
import { ArrowLeft } from '@element-plus/icons-vue'
import type { UserMessage } from '../api/message'
import dayjs from 'dayjs'

const route = useRoute()
const router = useRouter()
const messageStore = useMessageStore()

const userMessage = ref<UserMessage | null>(null)
const loading = ref(false)

// 获取消息类型 Tag 类型
const getTypeTagType = (type: string): '' | 'success' | 'warning' | 'info' | 'danger' => {
  const types: Record<string, '' | 'success' | 'warning' | 'info' | 'danger'> = {
    system: '',
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
    system: '系统消息',
    announcement: '公告',
    alert: '警报',
    reminder: '提醒',
    marketing: '营销消息'
  }
  return labels[type] || type
}

// 获取优先级标签
const getPriorityLabel = (priority: number) => {
  const labels: Record<number, string> = {
    1: '低优先级',
    2: '较低优先级',
    3: '中等优先级',
    4: '较高优先级',
    5: '高优先级'
  }
  return labels[priority] || `优先级 ${priority}`
}

// 格式化时间
const formatTime = (time: string) => {
  return dayjs(time).format('YYYY年MM月DD日 HH:mm:ss')
}

// 格式化消息内容
const formatContent = (content: string) => {
  // 简单的换行处理
  return content.replace(/\n/g, '<br>')
}

// 返回上一页
const goBack = () => {
  router.back()
}

// 标记消息为已读
const markAsRead = async () => {
  if (!userMessage.value) return
  
  try {
    await messageStore.userActions.markAsRead(userMessage.value.message_id)
    userMessage.value.is_read = true
    userMessage.value.read_at = new Date().toISOString()
    ElMessage.success('已标记为已读')
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

// 删除消息
const deleteMessage = async () => {
  if (!userMessage.value) return
  
  try {
    await messageStore.userActions.delete(userMessage.value.message_id)
    ElMessage.success('删除成功')
    goBack()
  } catch (error) {
    ElMessage.error('删除失败')
  }
}

// 加载消息详情
const loadMessageDetail = async () => {
  const messageId = Number(route.params.id)
  if (!messageId) return
  
  loading.value = true
  try {
    const response = await messageStore.userActions.getDetail(messageId)
    userMessage.value = response.data
  } catch (error) {
    console.error('Failed to load message detail:', error)
  } finally {
    loading.value = false
  }
}

// 初始化
onMounted(() => {
  loadMessageDetail()
})
</script>

<style scoped>
.message-detail {
  background: #fff;
  min-height: 100%;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-bottom: 1px solid var(--el-border-color-light);
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.detail-content {
  padding: 24px;
}

.message-header {
  margin-bottom: 24px;
}

.message-title {
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 12px 0;
  color: var(--el-text-color-primary);
}

.message-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.priority {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.priority-1 { background: var(--el-color-success-light-9); color: var(--el-color-success); }
.priority-2 { background: var(--el-color-warning-light-9); color: var(--el-color-warning); }
.priority-3 { background: var(--el-color-primary-light-9); color: var(--el-color-primary); }
.priority-4 { background: var(--el-color-warning-light-7); color: var(--el-color-warning-dark-2); }
.priority-5 { background: var(--el-color-danger-light-9); color: var(--el-color-danger); }

.time {
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.message-content {
  line-height: 1.6;
}

.content-body {
  font-size: 16px;
  color: var(--el-text-color-primary);
  margin-bottom: 24px;
  white-space: pre-wrap;
}

.extra-data {
  margin-top: 24px;
  padding: 16px;
  background: var(--el-fill-color-light);
  border-radius: 6px;
}

.extra-data h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
  color: var(--el-text-color-primary);
}

.message-footer {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--el-border-color-light);
}

.read-info {
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.read-label {
  font-weight: 500;
}

.read-time {
  color: var(--el-color-success);
}

.loading-container {
  padding: 48px 24px;
}

.error-container {
  padding: 48px 24px;
}
</style>
