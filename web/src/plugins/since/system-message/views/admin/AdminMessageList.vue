<template>
  <div class="admin-message-list">
    <div class="list-header">
      <div class="header-left">
        <h2>消息管理</h2>
      </div>
      <div class="header-right">
        <a-button type="primary" @click="createMessage">
          创建消息
        </a-button>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
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
          v-model:value="filters.status"
          placeholder="消息状态"
          style="width: 120px"
          allowClear
          @change="handleFilterChange"
        >
          <a-select-option value="draft">草稿</a-select-option>
          <a-select-option value="scheduled">已调度</a-select-option>
          <a-select-option value="sending">发送中</a-select-option>
          <a-select-option value="sent">已发送</a-select-option>
          <a-select-option value="failed">发送失败</a-select-option>
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

        <a-range-picker
          v-model:value="dateRange"
          @change="handleDateRangeChange"
          style="width: 240px"
        />
      </div>
      
      <div class="filter-right">
        <a-input-search
          v-model:value="searchKeyword"
          placeholder="搜索消息标题或内容"
          style="width: 250px"
          @search="handleSearch"
          allowClear
        />
      </div>
    </div>

    <!-- 批量操作栏 -->
    <div class="batch-actions" v-if="selectedRowKeys.length > 0">
      <span>已选择 {{ selectedRowKeys.length }} 项</span>
      <a-button @click="batchSend" :loading="batchLoading">
        批量发送
      </a-button>
      <a-button @click="batchDelete" :loading="batchLoading" danger>
        批量删除
      </a-button>
      <a-button @click="clearSelection">取消选择</a-button>
    </div>

    <!-- 消息列表 -->
    <a-table
      :columns="columns"
      :data-source="messageStore.messages"
      :loading="messageStore.loading"
      :pagination="paginationConfig"
      :row-selection="rowSelection"
      row-key="id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'title'">
          <div class="message-title-cell">
            <div class="title-content">
              <a @click="editMessage(record)" class="title-link">
                {{ record.title }}
              </a>
              <a-tag :color="getTypeColor(record.type)" size="small">
                {{ getTypeLabel(record.type) }}
              </a-tag>
            </div>
            <div class="message-meta">
              <span class="priority" :class="`priority-${record.priority}`">
                {{ getPriorityLabel(record.priority) }}
              </span>
              <span class="recipient">
                {{ getRecipientLabel(record.recipient_type) }}
              </span>
              <span class="time">{{ formatTime(record.created_at) }}</span>
            </div>
          </div>
        </template>
        
        <template v-if="column.key === 'status'">
          <a-tag :color="getStatusColor(record.status)">
            {{ getStatusLabel(record.status) }}
          </a-tag>
        </template>
        
        <template v-if="column.key === 'channels'">
          <div class="channels-cell">
            <a-tag 
              v-for="channel in record.channels" 
              :key="channel"
              size="small"
            >
              {{ getChannelLabel(channel) }}
            </a-tag>
          </div>
        </template>
        
        <template v-if="column.key === 'scheduled_at'">
          <span v-if="record.scheduled_at">
            {{ formatTime(record.scheduled_at) }}
          </span>
          <span v-else class="text-muted">立即发送</span>
        </template>
        
        <template v-if="column.key === 'action'">
          <a-space>
            <a-button 
              type="link" 
              size="small" 
              @click="editMessage(record)"
            >
              编辑
            </a-button>
            <a-button 
              type="link" 
              size="small" 
              @click="sendMessage(record)"
              v-if="record.status === 'draft'"
            >
              发送
            </a-button>
            <a-button 
              type="link" 
              size="small" 
              @click="duplicateMessage(record)"
            >
              复制
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
import { useMessageStore } from '../../store/message'
import { message } from 'ant-design-vue'
import type { Message, MessageListParams } from '../../api/message'
import dayjs from 'dayjs'
import type { Dayjs } from 'dayjs'

const router = useRouter()
const messageStore = useMessageStore()

// 筛选条件
const filters = reactive<MessageListParams>({
  type: undefined,
  status: undefined,
  priority: undefined
})

const searchKeyword = ref('')
const dateRange = ref<[Dayjs, Dayjs] | null>(null)
const selectedRowKeys = ref<number[]>([])
const batchLoading = ref(false)

// 表格列配置
const columns = [
  {
    title: '消息信息',
    key: 'title',
    width: '40%'
  },
  {
    title: '状态',
    key: 'status',
    width: '100px'
  },
  {
    title: '发送渠道',
    key: 'channels',
    width: '150px'
  },
  {
    title: '调度时间',
    key: 'scheduled_at',
    width: '150px'
  },
  {
    title: '操作',
    key: 'action',
    width: '200px'
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

// 获取收件人类型标签
const getRecipientLabel = (type: string) => {
  const labels: Record<string, string> = {
    all: '全部用户',
    role: '按角色',
    user: '指定用户',
    department: '按部门'
  }
  return labels[type] || type
}

// 获取渠道标签
const getChannelLabel = (channel: string) => {
  const labels: Record<string, string> = {
    socketio: 'WebSocket',
    email: '邮件',
    sms: '短信',
    push: '推送'
  }
  return labels[channel] || channel
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

// 处理日期范围变化
const handleDateRangeChange = (dates: [Dayjs, Dayjs] | null) => {
  if (dates) {
    filters.date_from = dates[0].format('YYYY-MM-DD')
    filters.date_to = dates[1].format('YYYY-MM-DD')
  } else {
    filters.date_from = undefined
    filters.date_to = undefined
  }
  handleFilterChange()
}

// 处理搜索
const handleSearch = (keyword: string) => {
  if (keyword.trim()) {
    messageStore.adminActions.search(keyword, filters)
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
    await messageStore.adminActions.getList(filters)
  } catch (error) {
    message.error('加载消息列表失败')
  }
}

// 创建消息
const createMessage = () => {
  router.push('/admin/message/create')
}

// 编辑消息
const editMessage = (record: Message) => {
  router.push(`/admin/message/edit/${record.id}`)
}

// 发送消息
const sendMessage = async (record: Message) => {
  try {
    await messageStore.adminActions.send(record.id)
    message.success('消息发送成功')
  } catch (error) {
    message.error('消息发送失败')
  }
}

// 复制消息
const duplicateMessage = (record: Message) => {
  router.push(`/admin/message/create?duplicate=${record.id}`)
}

// 删除消息
const deleteMessage = async (record: Message) => {
  try {
    await messageStore.adminActions.delete(record.id)
    message.success('删除成功')
  } catch (error) {
    message.error('删除失败')
  }
}

// 批量发送
const batchSend = async () => {
  if (selectedRowKeys.value.length === 0) return
  
  batchLoading.value = true
  try {
    const promises = selectedRowKeys.value.map(id => 
      messageStore.adminActions.send(id)
    )
    await Promise.all(promises)
    message.success(`已发送 ${selectedRowKeys.value.length} 条消息`)
    clearSelection()
  } catch (error) {
    message.error('批量发送失败')
  } finally {
    batchLoading.value = false
  }
}

// 批量删除
const batchDelete = async () => {
  if (selectedRowKeys.value.length === 0) return
  
  batchLoading.value = true
  try {
    await messageStore.adminActions.batchDelete(selectedRowKeys.value)
    message.success(`已删除 ${selectedRowKeys.value.length} 条消息`)
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
.admin-message-list {
  padding: 24px;
  background: #fff;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.list-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
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

.message-title-cell {
  cursor: pointer;
}

.title-content {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.title-link {
  flex: 1;
  color: #1890ff;
  text-decoration: none;
}

.title-link:hover {
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

.recipient {
  color: #999;
}

.time {
  color: #999;
}

.channels-cell {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.text-muted {
  color: #999;
}
</style>