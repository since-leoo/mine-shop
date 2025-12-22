<template>
  <div class="message-list">
    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <a-radio-group v-model:value="filters.is_read" @change="handleFilterChange">
          <a-radio-button :value="undefined">全部</a-radio-button>
          <a-radio-button :value="false">未读</a-radio-button>
          <a-radio-button :value="true">已读</a-radio-button>
        </a-radio-group>
        
        <a-select
          v-model:value="filters.type"
          placeholder="消息类型"
          style="width: 120px"
          allowClear
          @change="handleFilterChange"
        >
          <a-select-option value="system">系统消息</a-select-option>
          <a-select-option value="announcement">公告</a-select-option>
          <a-select-option value="alert">警报</a-select-option>
          <a-select-option value="reminder">提醒</a-select-option>
          <a-select-option value="marketing">营销</a-select-option>
        </a-select>
        
        <a-select
          v-model:value="filters.priority"
          placeholder="优先级"
          style="width: 100px"
          allowClear
          @change="handleFilterChange"
        >
          <a-select-option :value="5">高</a-select-option>
          <a-select-option :value="4">较高</a-select-option>
          <a-select-option :value="3">中等</a-select-option>
          <a-select-option :value="2">较低</a-select-option>
          <a-select-option :value="1">低</a-select-option>
        </a-select>
      </div>
      
      <div class="filter-right">
        <a-input-search
          v-model:value="searchKeyword"
          placeholder="搜索消息"
          style="width: 200px"
          @search="handleSearch"
          allowClear
        />
      </div>
    </div>

    <!-- 批量操作栏 -->
    <div class="batch-actions" v-if="selectedRowKeys.length > 0">
      <span>已选择 {{ selectedRowKeys.length }} 项</span>
      <a-button @click="batchMarkAsRead" :loading="batchLoading">
        批量已读
      </a-button>
      <a-button @click="batchDelete" :loading="batchLoading" danger>
        批量删除
      </a-button>
      <a-button @click="clearSelection">取消选择</a-button>
    </div>

    <!-- 消息列表 -->
    <a-table
      :columns="columns"
      :data-source="messageStore.userMessages"
      :loading="messageStore.loading"
      :pagination="paginationConfig"
      :row-selection="rowSelection"
      row-key="id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'title'">
          <div class="message-title" :class="{ 'unread': !record.is_read }">
            <div class="title-content">
              <span class="title-text" @click="viewMessage(record)">
                {{ record.message.title }}
              </span>
              <a-tag 
                :color="getTypeColor(record.message.type)" 
                size="small"
              >
                {{ getTypeLabel(record.message.type) }}
              </a-tag>
            </div>
            <div class="message-meta">
              <span class="priority" :class="`priority-${record.message.priority}`">
                {{ getPriorityLabel(record.message.priority) }}
              </span>
              <span class="time">{{ formatTime(record.created_at) }}</span>
            </div>
          </div>
        </template>
        
        <template v-if="column.key === 'status'">
          <a-tag :color="record.is_read ? 'green' : 'orange'">
            {{ record.is_read ? '已读' : '未读' }}
          </a-tag>
        </template>
        
        <template v-if="column.key === 'action'">
          <a-space>
            <a-button 
              type="link" 
              size="small" 
              @click="viewMessage(record)"
            >
              查看
            </a-button>
            <a-button 
              type="link" 
              size="small" 
              @click="markAsRead(record)"
              v-if="!record.is_read"
            >
              标记已读
            </a-button>
            <a-popconfirm
              title="确定要删除这条消息吗？"
              @confirm="deleteMessage(record)"
            >
              <a-button type="link" size="small" danger>
                删除
              </a-button>
            </a-popconfirm>
          </a-space>
        </template>
      </template>
    </a-table>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessageStore } from '../store/message'
import { message } from 'ant-design-vue'
import type { UserMessage, UserMessageListParams } from '../api/message'
import dayjs from 'dayjs'

const router = useRouter()
const messageStore = useMessageStore()

// 筛选条件
const filters = reactive<UserMessageListParams>({
  is_read: undefined,
  type: undefined,
  priority: undefined
})

const searchKeyword = ref('')
const selectedRowKeys = ref<number[]>([])
const batchLoading = ref(false)

// 表格列配置
const columns = [
  {
    title: '消息内容',
    key: 'title',
    width: '60%'
  },
  {
    title: '状态',
    key: 'status',
    width: '100px'
  },
  {
    title: '操作',
    key: 'action',
    width: '150px'
  }
]

// 行选择配置
const rowSelection = {
  selectedRowKeys: selectedRowKeys,
  onChange: (keys: number[]) => {
    selectedRowKeys.value = keys
  }
}

// 分页配置
const paginationConfig = computed(() => ({
  current: messageStore.currentPage,
  pageSize: messageStore.pageSize,
  total: messageStore.total,
  showSizeChanger: true,
  showQuickJumper: true,
  showTotal: (total: number) => `共 ${total} 条记录`
}))

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

// 获取优先级标签
const getPriorityLabel = (priority: number) => {
  const labels: Record<number, string> = {
    1: '低',
    2: '较低',
    3: '中等',
    4: '较高',
    5: '高'
  }
  return labels[priority] || String(priority)
}

// 格式化时间
const formatTime = (time: string) => {
  return dayjs(time).format('YYYY-MM-DD HH:mm')
}

// 处理筛选变化
const handleFilterChange = () => {
  messageStore.setPage(1)
  loadMessages()
}

// 处理搜索
const handleSearch = (keyword: string) => {
  if (keyword.trim()) {
    messageStore.userActions.search(keyword, filters)
  } else {
    loadMessages()
  }
}

// 处理表格变化
const handleTableChange = (pagination: any) => {
  messageStore.setPage(pagination.current)
  messageStore.setPageSize(pagination.pageSize)
  loadMessages()
}

// 加载消息列表
const loadMessages = async () => {
  try {
    await messageStore.userActions.getList(filters)
  } catch (error) {
    message.error('加载消息列表失败')
  }
}

// 查看消息详情
const viewMessage = (record: UserMessage) => {
  router.push(`/message-center/detail/${record.message_id}`)
}

// 标记消息为已读
const markAsRead = async (record: UserMessage) => {
  try {
    await messageStore.userActions.markAsRead(record.message_id)
    message.success('已标记为已读')
  } catch (error) {
    message.error('操作失败')
  }
}

// 删除消息
const deleteMessage = async (record: UserMessage) => {
  try {
    await messageStore.userActions.delete(record.message_id)
    message.success('删除成功')
  } catch (error) {
    message.error('删除失败')
  }
}

// 批量标记已读
const batchMarkAsRead = async () => {
  if (selectedRowKeys.value.length === 0) return
  
  batchLoading.value = true
  try {
    const messageIds = selectedRowKeys.value.map(id => {
      const userMessage = messageStore.userMessages.find(msg => msg.id === id)
      return userMessage?.message_id
    }).filter(Boolean) as number[]
    
    await messageStore.userActions.batchMarkAsRead(messageIds)
    message.success(`已标记 ${messageIds.length} 条消息为已读`)
    clearSelection()
  } catch (error) {
    message.error('批量操作失败')
  } finally {
    batchLoading.value = false
  }
}

// 批量删除
const batchDelete = async () => {
  if (selectedRowKeys.value.length === 0) return
  
  batchLoading.value = true
  try {
    const messageIds = selectedRowKeys.value.map(id => {
      const userMessage = messageStore.userMessages.find(msg => msg.id === id)
      return userMessage?.message_id
    }).filter(Boolean) as number[]
    
    await messageStore.userActions.batchDelete(messageIds)
    message.success(`已删除 ${messageIds.length} 条消息`)
    clearSelection()
  } catch (error) {
    message.error('批量删除失败')
  } finally {
    batchLoading.value = false
  }
}

// 清除选择
const clearSelection = () => {
  selectedRowKeys.value = []
}

// 初始化
onMounted(() => {
  loadMessages()
})
</script>

<style scoped>
.message-list {
  background: #fff;
}

.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding: 16px;
  background: #fafafa;
  border-radius: 6px;
}

.filter-left {
  display: flex;
  gap: 12px;
  align-items: center;
}

.batch-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px 16px;
  background: #e6f7ff;
  border: 1px solid #91d5ff;
  border-radius: 6px;
}

.message-title {
  cursor: pointer;
}

.message-title.unread {
  font-weight: 600;
}

.title-content {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.title-text {
  flex: 1;
  color: #1890ff;
}

.title-text:hover {
  text-decoration: underline;
}

.message-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: #666;
}

.priority {
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 11px;
}

.priority-1 { background: #f6ffed; color: #52c41a; }
.priority-2 { background: #fff7e6; color: #fa8c16; }
.priority-3 { background: #e6f7ff; color: #1890ff; }
.priority-4 { background: #fff2e8; color: #fa541c; }
.priority-5 { background: #fff1f0; color: #f5222d; }

.time {
  color: #999;
}
</style>