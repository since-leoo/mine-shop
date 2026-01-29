<template>
  <div class="admin-message-list">
    <div class="list-header">
      <div class="header-left">
        <h2>消息管理</h2>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="showCreateDialog">
          创建消息
        </el-button>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
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
          v-model="filters.status"
          placeholder="消息状态"
          style="width: 120px"
          clearable
          @change="handleFilterChange"
        >
          <el-option value="draft" label="草稿" />
          <el-option value="scheduled" label="已调度" />
          <el-option value="sending" label="发送中" />
          <el-option value="sent" label="已发送" />
          <el-option value="failed" label="发送失败" />
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

        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          @change="handleDateRangeChange"
          style="width: 240px"
        />
      </div>
      
      <div class="filter-right">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索消息标题或内容"
          style="width: 250px"
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
      <el-button @click="batchSend" :loading="batchLoading">批量发送</el-button>
      <el-button @click="batchDelete" :loading="batchLoading" type="danger">批量删除</el-button>
      <el-button @click="clearSelection">取消选择</el-button>
    </div>

    <!-- 消息列表 -->
    <el-table ref="tableRef" :data="messageStore.messages" v-loading="messageStore.loading"
      @selection-change="handleSelectionChange" row-key="id" style="width: 100%">
      <el-table-column type="selection" width="55" />
      <el-table-column label="消息信息" min-width="300">
        <template #default="{ row }">
          <div class="message-title-cell">
            <div class="title-content">
              <a @click="showEditDialog(row)" class="title-link">{{ row.title }}</a>
              <el-tag :type="getTypeTagType(row.type)" size="small">{{ getTypeLabel(row.type) }}</el-tag>
            </div>
            <div class="message-meta">
              <span class="priority" :class="`priority-${row.priority}`">{{ getPriorityLabel(row.priority) }}</span>
              <span class="recipient">{{ getRecipientLabel(row.recipient_type) }}</span>
              <span class="time">{{ formatTime(row.created_at) }}</span>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="getStatusTagType(row.status)">{{ getStatusLabel(row.status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="发送渠道" width="150">
        <template #default="{ row }">
          <div class="channels-cell">
            <el-tag v-for="channel in row.channels" :key="channel" size="small" type="info">
              {{ getChannelLabel(channel) }}
            </el-tag>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="调度时间" width="150">
        <template #default="{ row }">
          <span v-if="row.scheduled_at">{{ formatTime(row.scheduled_at) }}</span>
          <span v-else class="text-muted">立即发送</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="200" fixed="right">
        <template #default="{ row }">
          <el-button type="primary" link size="small" @click="showEditDialog(row)">编辑</el-button>
          <el-button type="primary" link size="small" @click="sendMessage(row)" v-if="row.status === 'draft'">发送</el-button>
          <el-button type="primary" link size="small" @click="duplicateMessage(row)">复制</el-button>
          <el-popconfirm title="确定要删除这条消息吗？" @confirm="deleteMessage(row)">
            <template #reference>
              <el-button type="danger" link size="small">删除</el-button>
            </template>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>

    <!-- 分页 -->
    <div class="pagination-wrapper">
      <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]" :total="messageStore.total"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange" @current-change="handleCurrentChange" />
    </div>

    <!-- 消息表单弹窗 -->
    <AdminMessageForm
      v-model:visible="formDialogVisible"
      :message="currentMessage"
      :mode="formMode"
      @success="handleFormSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMessageStore } from '../../store/message'
import { ElMessage } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import type { Message, MessageListParams } from '../../api/message'
import type { TableInstance } from 'element-plus'
import dayjs from 'dayjs'
import AdminMessageForm from './AdminMessageForm.vue'

// 定义组件名称
defineOptions({
  name: 'AdminMessageList'
})

const router = useRouter()
const messageStore = useMessageStore()

const filters = reactive<MessageListParams>({ type: undefined, status: undefined, priority: undefined })
const searchKeyword = ref('')
const dateRange = ref<[Date, Date] | null>(null)
const selectedRows = ref<Message[]>([])
const batchLoading = ref(false)
const tableRef = ref<TableInstance>()
const currentPage = ref(1)
const pageSize = ref(10)

// 弹窗相关
const formDialogVisible = ref(false)
const currentMessage = ref<Message | null>(null)
const formMode = ref<'create' | 'edit' | 'duplicate'>('create')

const getTypeTagType = (type: string): 'primary' | 'success' | 'warning' | 'info' | 'danger' => {
  const types: Record<string, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    system: 'primary', announcement: 'success', alert: 'danger', reminder: 'warning', marketing: 'info'
  }
  return types[type] || 'info'
}

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = { system: '系统', announcement: '公告', alert: '警报', reminder: '提醒', marketing: '营销' }
  return labels[type] || type
}

const getStatusTagType = (status: string): 'primary' | 'success' | 'warning' | 'info' | 'danger' => {
  const types: Record<string, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    draft: 'info', scheduled: 'warning', sending: 'primary', sent: 'success', failed: 'danger'
  }
  return types[status] || 'info'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = { draft: '草稿', scheduled: '已调度', sending: '发送中', sent: '已发送', failed: '发送失败' }
  return labels[status] || status
}

const getPriorityLabel = (priority: number) => {
  const labels: Record<number, string> = { 1: '低', 2: '较低', 3: '中等', 4: '较高', 5: '高' }
  return labels[priority] || String(priority)
}

const getRecipientLabel = (type: string) => {
  const labels: Record<string, string> = { all: '全部用户', role: '按角色', user: '指定用户', department: '按部门' }
  return labels[type] || type
}

const getChannelLabel = (channel: string) => {
  const labels: Record<string, string> = { database: '站内信', email: '邮件', sms: '短信', push: '推送' }
  return labels[channel] || channel
}

const formatTime = (time: string) => dayjs(time).format('YYYY-MM-DD HH:mm')

const handleFilterChange = () => { currentPage.value = 1; loadMessages() }

const handleDateRangeChange = (dates: [Date, Date] | null) => {
  if (dates) {
    filters.date_from = dayjs(dates[0]).format('YYYY-MM-DD')
    filters.date_to = dayjs(dates[1]).format('YYYY-MM-DD')
  } else {
    filters.date_from = undefined
    filters.date_to = undefined
  }
  handleFilterChange()
}

const handleSearch = () => {
  if (searchKeyword.value.trim()) { messageStore.adminActions.search(searchKeyword.value, filters) }
  else { loadMessages() }
}

const handleSelectionChange = (rows: Message[]) => { selectedRows.value = rows }
const handleSizeChange = (size: number) => { pageSize.value = size; messageStore.setPageSize(size); loadMessages() }
const handleCurrentChange = (page: number) => { currentPage.value = page; messageStore.setPage(page); loadMessages() }

const loadMessages = async () => {
  try { await messageStore.adminActions.getList(filters) }
  catch (error) { ElMessage.error('加载消息列表失败') }
}

// 弹窗操作
const showCreateDialog = () => {
  currentMessage.value = null
  formMode.value = 'create'
  formDialogVisible.value = true
}

const showEditDialog = (record: Message) => {
  currentMessage.value = record
  formMode.value = 'edit'
  formDialogVisible.value = true
}

const duplicateMessage = (record: Message) => {
  currentMessage.value = record
  formMode.value = 'duplicate'
  formDialogVisible.value = true
}

const handleFormSuccess = () => {
  formDialogVisible.value = false
  loadMessages()
}

const sendMessage = async (record: Message) => {
  try { await messageStore.adminActions.send(record.id); ElMessage.success('消息发送成功') }
  catch (error) { ElMessage.error('消息发送失败') }
}

const deleteMessage = async (record: Message) => {
  try { await messageStore.adminActions.delete(record.id); ElMessage.success('删除成功') }
  catch (error) { ElMessage.error('删除失败') }
}

const batchSend = async () => {
  if (selectedRows.value.length === 0) return
  batchLoading.value = true
  try {
    const promises = selectedRows.value.map(row => messageStore.adminActions.send(row.id))
    await Promise.all(promises)
    ElMessage.success(`已发送 ${selectedRows.value.length} 条消息`)
    clearSelection()
  } catch (error) { ElMessage.error('批量发送失败') }
  finally { batchLoading.value = false }
}

const batchDelete = async () => {
  if (selectedRows.value.length === 0) return
  batchLoading.value = true
  try {
    const ids = selectedRows.value.map(row => row.id)
    await messageStore.adminActions.batchDelete(ids)
    ElMessage.success(`已删除 ${selectedRows.value.length} 条消息`)
    clearSelection()
  } catch (error) { ElMessage.error('批量删除失败') }
  finally { batchLoading.value = false }
}

const clearSelection = () => { tableRef.value?.clearSelection(); selectedRows.value = [] }

onMounted(() => { loadMessages() })
</script>

<style scoped>
.admin-message-list { 
  padding: 24px; 
  background: var(--el-bg-color); 
  min-height: 100%;
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
  color: var(--el-text-color-primary);
}
.filter-bar { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 16px; 
  padding: 16px; 
  background: var(--el-bg-color-overlay); 
  border-radius: 6px; 
  border: 1px solid var(--el-border-color-light);
}
.filter-left { display: flex; gap: 12px; align-items: center; }
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
.message-title-cell { cursor: pointer; }
.title-content { 
  display: flex; 
  align-items: center; 
  gap: 8px; 
  margin-bottom: 4px; 
  flex-wrap: nowrap;
}
.title-link { 
  color: var(--el-color-primary); 
  text-decoration: none; 
  cursor: pointer;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.title-content .el-tag {
  flex-shrink: 0;
}
.title-link:hover { text-decoration: underline; }
.message-meta { 
  display: flex; 
  align-items: center; 
  gap: 12px; 
  font-size: 12px; 
  color: var(--el-text-color-secondary); 
}
.priority { padding: 2px 6px; border-radius: 3px; font-size: 11px; }
.priority-1 { background: var(--el-color-success-light-9); color: var(--el-color-success); }
.priority-2 { background: var(--el-color-warning-light-9); color: var(--el-color-warning); }
.priority-3 { background: var(--el-color-primary-light-9); color: var(--el-color-primary); }
.priority-4 { background: var(--el-color-warning-light-7); color: var(--el-color-warning-dark-2); }
.priority-5 { background: var(--el-color-danger-light-9); color: var(--el-color-danger); }
.channels-cell { display: flex; flex-wrap: wrap; gap: 4px; }
.text-muted { color: var(--el-text-color-secondary); }
.pagination-wrapper { 
  display: flex; 
  justify-content: flex-end; 
  margin-top: 16px; 
  padding: 16px 0;
}

/* 深色主题适配 */
:deep(.el-table) {
  --el-table-bg-color: var(--el-bg-color-overlay);
  --el-table-tr-bg-color: var(--el-bg-color-overlay);
  --el-table-header-bg-color: var(--el-fill-color-light);
}
</style>
