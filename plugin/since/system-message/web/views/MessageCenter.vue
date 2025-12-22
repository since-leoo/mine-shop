<template>
  <div class="message-center">
    <div class="message-center-header">
      <div class="header-left">
        <h2>消息中心</h2>
        <el-badge 
          v-if="messageStore.hasUnreadMessages" 
          :value="messageStore.unreadCount" 
          class="unread-badge"
        />
      </div>
      <div class="header-right">
        <el-button 
          type="primary" 
          @click="markAllAsRead"
          :loading="loading"
          :disabled="!messageStore.hasUnreadMessages"
        >
          全部已读
        </el-button>
        <el-button @click="$router.push('/message-center/settings')">
          通知设置
        </el-button>
      </div>
    </div>
    
    <div class="message-center-content">
      <router-view />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useMessageStore } from '../store/message'
import { ElMessage } from 'element-plus'

const messageStore = useMessageStore()
const loading = ref(false)

// 标记所有消息为已读
const markAllAsRead = async () => {
  loading.value = true
  try {
    await messageStore.userActions.markAllAsRead()
    ElMessage.success('所有消息已标记为已读')
  } catch (error) {
    ElMessage.error('操作失败，请重试')
  } finally {
    loading.value = false
  }
}

// 初始化
onMounted(async () => {
  // 获取未读消息数量
  await messageStore.userActions.getUnreadCount()
})
</script>

<style scoped>
.message-center {
  padding: 24px;
  background: #fff;
  min-height: 100vh;
}

.message-center-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-left h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.header-right {
  display: flex;
  gap: 12px;
}

.message-center-content {
  flex: 1;
}
</style>