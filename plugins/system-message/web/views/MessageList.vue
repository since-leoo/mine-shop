<template>
  <div class="message-list">
    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-radio-group v-model="filters.is_read" @change="handleFilterChange">
          <el-radio-button :value="undefined">全部</el-radio-button>
          <el-radio-button :value="false">未读</el-radio-button>
          <el-radio-button :value="true">已读</el-radio-button>
        </el-radio-group>
        
        <el-select
          v-model="filters.type"
          placeholder="消息类型"
          style="width: 120px"
          clearable
          @change="handleFilterChange"
        >
          <el-option value="system" label="系统消息" />
          <el-option value="announcement" label="公告" />
          <el-option value="alert" label="警报" />
          <el-option value="reminder" label="提醒" />
          <el-option value="marketing" label="营销" />
        </el-select>
        
        <el-select
          v-model="filters.priority"
          placeholder="优先级"
          style="width: 100px"
          clearable
          @change="handleFilterChange"
        >
          <el-option :value="5" label="高" />
          <el-option :value="4" label="较高" />
          <el-option :value="3" label="中等" />
          <el-option :value="2" label="较低" />
          <el-option :value="1" label="低" />
        </el-select>
      </div>
      
      <div class="filter-right">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索消息"
          style="width: 200px"
          clearable
          @keyup.enter="handleSearch"
        >
          <template #append>
            <el-button @click="handleSearch">
              <el-icon><Search /></el-icon>
            </el-button>
          </template>
        </el-input>
      </div>
    </div>

    <!-- 批量操作栏 -->
    <div class="batch-actions" v-if="selectedRows.length > 0">
      <span>已选择 {{ selectedRows.length }} 项</span>
      <el-button @click="batchMarkAsRead" :loading="batchLoading">
        批量已读
      </el-button>
      <el-button @click="batchDelete" :loading="batchLoading" type="danger">
        批量删除
      </el-button>
      <el-button @click="clearSelection">取消选择</el-button>
    </div>

    <!-- 消息列表 -->
    <el-table
      ref="tableRef"
      :data="messageStore.userMessages"
      v-loading="messageStore.loading"
      @selection-change="handleSelectionChange"
      row-key="id"
      style="width: 100%"
    >
      <el-table-column type="selection" width="55" />
      
      <el-table-column label="消息内容" min-width="300">
        <template #default="{ row }">
          <div class="message-title" :class="{ 'unread': !row.is_read }">
            <div class="title-content">
              <span class="title-text" @click="viewMessage(row)">
                {{ row.message.title }}
              </span>
              <el-tag 
                :type="getTypeTagType(row.message.type)" 
                size="small"
              >
                {{ getTypeLabel(row.message.type) }}
              </el-tag>
            </div>
            <div class="message-meta">
              <span class="priority" :class="`priority-${row.message.priority}`">
                {{ getPriorityLabel(row.message.priority) }}
              </span>
              <span class="time">{{ formatTime(row.created_at) }}</span>
            </div>
          </div>
        </template>
      </el-table-column>
      
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="row.is_read ? 'success' : 'warning'">
            {{ row.is_read ? '已读' : '未读' }}
          </el-tag>
        </template>
      </el-table-column>
      
      <el-table-column label="操作" width="180" fixed="right">
        <template #default="{ row }">
          <el-button type="primary" link size="small" @click="viewMessage(row)">
            查看
          </el-button>
          <el-button 
            type="primary" 
            link 
            size="small" 
            @click="markAsRead(row)"
            v-if="!row.is_read"
          >
            标记已读
          </el-button>
          <el-popconfirm
            title="确定要删除这条消息吗？"
            @confirm="deleteMessage(row)"
          >
            <template #reference>
              <el-button type="danger" link size="small">
                删除
              </el-button>
            </template>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>

    <!-- 分页 -->
    <div class="pagination-wrapper">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="messageStore.total"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessageStore } from '../store/message'
import { ElMessage } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import type { UserMessage, UserMessageListParams } from '../api/message'
import type { TableInstance } from 'element-plus'
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
const selectedRows = ref<UserMessage[]>([])
const batchLoading = ref(false)
const tableRef = ref<TableInstance>()
const currentPage = ref(1)
const pageSize = ref(10)

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
  currentPage.value = 1
  loadMessages()
}

// 处理搜索
const handleSearch = () => {
  if (searchKeyword.value.trim()) {
    messageStore.userActions.search(searchKeyword.value, filters)
  } else {
    loadMessages()
  }
}

// 处理选择变化
const handleSelectionChange = (rows: UserMessage[]) => {
  selectedRows.value = rows
}

// 处理分页大小变化
const handleSizeChange = (size: number) => {
  pageSize.value = size
  messageStore.setPageSize(size)
  loadMessages()
}

// 处理页码变化
const handleCurrentChange = (page: number) => {
  currentPage.value = page
  messageStore.setPage(page)
  loadMessages()
}

// 加载消息列表
const loadMessages = async () => {
  try {
    await messageStore.userActions.getList(filters)
  } catch (error) {
    ElMessage.error('加载消息列表失败')
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
    ElMessage.success('已标记为已读')
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

// 删除消息
const deleteMessage = async (record: UserMessage) => {
  try {
    await messageStore.userActions.delete(record.message_id)
    ElMessage.success('删除成功')
  } catch (error) {
    ElMessage.error('删除失败')
  }
}

// 批量标记已读
const batchMarkAsRead = async () => {
  if (selectedRows.value.length === 0) return
  
  batchLoading.value = true
  try {
    const messageIds = selectedRows.value.map(row => row.message_id)
    await messageStore.userActions.batchMarkAsRead(messageIds)
    ElMessage.success(`已标记 ${messageIds.length} 条消息为已读`)
    clearSelection()
  } catch (error) {
    ElMessage.error('批量操作失败')
  } finally {
    batchLoading.value = false
  }
}

// 批量删除
const batchDelete = async () => {
  if (selectedRows.value.length === 0) return
  
  batchLoading.value = true
  try {
    const messageIds = selectedRows.value.map(row => row.message_id)
    await messageStore.userActions.batchDelete(messageIds)
    ElMessage.success(`已删除 ${messageIds.length} 条消息`)
    clearSelection()
  } catch (error) {
    ElMessage.error('批量删除失败')
  } finally {
    batchLoading.value = false
  }
}

// 清除选择
const clearSelection = () => {
  tableRef.value?.clearSelection()
  selectedRows.value = []
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
  background: var(--el-fill-color-light);
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
  background: var(--el-color-primary-light-9);
  border: 1px solid var(--el-color-primary-light-5);
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
  color: var(--el-color-primary);
  cursor: pointer;
}

.title-text:hover {
  text-decoration: underline;
}

.message-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.priority {
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 11px;
}

.priority-1 { background: var(--el-color-success-light-9); color: var(--el-color-success); }
.priority-2 { background: var(--el-color-warning-light-9); color: var(--el-color-warning); }
.priority-3 { background: var(--el-color-primary-light-9); color: var(--el-color-primary); }
.priority-4 { background: var(--el-color-warning-light-7); color: var(--el-color-warning-dark-2); }
.priority-5 { background: var(--el-color-danger-light-9); color: var(--el-color-danger); }

.time {
  color: var(--el-text-color-secondary);
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
