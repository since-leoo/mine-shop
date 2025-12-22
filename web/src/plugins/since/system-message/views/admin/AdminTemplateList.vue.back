<template>
  <div class="admin-template-list">
    <div class="list-header">
      <div class="header-left">
        <h2>模板管理</h2>
      </div>
      <div class="header-right">
        <a-button type="primary" @click="createTemplate">
          创建模板
        </a-button>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <a-select
          v-model:value="filters.type"
          placeholder="模板类型"
          style="width: 120px"
          allowClear
          @change="handleFilterChange"
        >
          <a-select-option value="system">系统模板</a-select-option>
          <a-select-option value="announcement">公告模板</a-select-option>
          <a-select-option value="alert">警报模板</a-select-option>
          <a-select-option value="reminder">提醒模板</a-select-option>
          <a-select-option value="marketing">营销模板</a-select-option>
        </a-select>
        
        <a-select
          v-model:value="filters.status"
          placeholder="模板状态"
          style="width: 120px"
          allowClear
          @change="handleFilterChange"
        >
          <a-select-option value="active">启用</a-select-option>
          <a-select-option value="inactive">禁用</a-select-option>
        </a-select>
      </div>
      
      <div class="filter-right">
        <a-input-search
          v-model:value="searchKeyword"
          placeholder="搜索模板名称或内容"
          style="width: 250px"
          @search="handleSearch"
          allowClear
        />
      </div>
    </div>

    <!-- 批量操作栏 -->
    <div class="batch-actions" v-if="selectedRowKeys.length > 0">
      <span>已选择 {{ selectedRowKeys.length }} 项</span>
      <a-button @click="batchEnable" :loading="batchLoading">
        批量启用
      </a-button>
      <a-button @click="batchDisable" :loading="batchLoading">
        批量禁用
      </a-button>
      <a-button @click="batchDelete" :loading="batchLoading" danger>
        批量删除
      </a-button>
      <a-button @click="clearSelection">取消选择</a-button>
    </div>

    <!-- 模板列表 -->
    <a-table
      :columns="columns"
      :data-source="templateStore.templates"
      :loading="templateStore.loading"
      :pagination="paginationConfig"
      :row-selection="rowSelection"
      row-key="id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'name'">
          <div class="template-name-cell">
            <div class="name-content">
              <a @click="editTemplate(record)" class="name-link">
                {{ record.name }}
              </a>
              <a-tag :color="getTypeColor(record.type)" size="small">
                {{ getTypeLabel(record.type) }}
              </a-tag>
            </div>
            <div class="template-meta">
              <span class="description">{{ record.description }}</span>
              <span class="time">{{ formatTime(record.created_at) }}</span>
            </div>
          </div>
        </template>
        
        <template v-if="column.key === 'variables'">
          <div class="variables-cell">
            <a-tag 
              v-for="variable in getTemplateVariables(record.content)" 
              :key="variable"
              size="small"
              color="blue"
            >
              {{ variable }}
            </a-tag>
            <span v-if="getTemplateVariables(record.content).length === 0" class="text-muted">
              无变量
            </span>
          </div>
        </template>
        
        <template v-if="column.key === 'usage_count'">
          <a-statistic 
            :value="record.usage_count || 0" 
            :value-style="{ fontSize: '14px' }"
          />
        </template>
        
        <template v-if="column.key === 'status'">
          <a-switch
            :checked="record.status === 'active'"
            @change="toggleStatus(record)"
            :loading="record.statusLoading"
          />
        </template>
        
        <template v-if="column.key === 'action'">
          <a-space>
            <a-button 
              type="link" 
              size="small" 
              @click="previewTemplate(record)"
            >
              预览
            </a-button>
            <a-button 
              type="link" 
              size="small" 
              @click="editTemplate(record)"
            >
              编辑
            </a-button>
            <a-button 
              type="link" 
              size="small" 
              @click="duplicateTemplate(record)"
            >
              复制
            </a-button>
            <a-popconfirm
              title="确定要删除这个模板吗？"
              @confirm="deleteTemplate(record)"
            >
              <a-button type="link" size="small" danger>
                删除
              </a-button>
            </a-popconfirm>
          </a-space>
        </template>
      </template>
    </a-table>

    <!-- 模板预览弹窗 -->
    <a-modal
      v-model:open="previewVisible"
      title="模板预览"
      :footer="null"
      width="800px"
    >
      <div v-if="previewTemplate">
        <div class="preview-header">
          <h3>{{ previewTemplate.name }}</h3>
          <a-tag :color="getTypeColor(previewTemplate.type)">
            {{ getTypeLabel(previewTemplate.type) }}
          </a-tag>
        </div>
        
        <a-divider />
        
        <div class="preview-variables" v-if="previewVariables.length > 0">
          <h4>模板变量</h4>
          <div class="variables-form">
            <div 
              v-for="variable in previewVariables" 
              :key="variable"
              class="variable-input"
            >
              <label>{{ variable }}</label>
              <a-input 
                v-model:value="previewValues[variable]"
                :placeholder="`请输入 ${variable} 的值`"
                @change="updatePreview"
              />
            </div>
          </div>
        </div>
        
        <div class="preview-content">
          <h4>预览效果</h4>
          <div class="content-preview" v-html="previewContent"></div>
        </div>
      </div>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTemplateStore } from '../../store/template'
import { message } from 'ant-design-vue'
import type { MessageTemplate, TemplateListParams } from '../../api/template'
import dayjs from 'dayjs'

const router = useRouter()
const templateStore = useTemplateStore()

// 筛选条件
const filters = reactive<TemplateListParams>({
  type: undefined,
  status: undefined
})

const searchKeyword = ref('')
const selectedRowKeys = ref<number[]>([])
const batchLoading = ref(false)

// 预览相关
const previewVisible = ref(false)
const previewTemplate = ref<MessageTemplate | null>(null)
const previewVariables = ref<string[]>([])
const previewValues = ref<Record<string, string>>({})
const previewContent = ref('')

// 表格列配置
const columns = [
  {
    title: '模板信息',
    key: 'name',
    width: '35%'
  },
  {
    title: '模板变量',
    key: 'variables',
    width: '25%'
  },
  {
    title: '使用次数',
    key: 'usage_count',
    width: '100px'
  },
  {
    title: '状态',
    key: 'status',
    width: '80px'
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
  current: templateStore.currentPage,
  pageSize: templateStore.pageSize,
  total: templateStore.total,
  showSizeChanger: true,
  showQuickJumper: true,
  showTotal: (total: number) => `共 ${total} 条记录`
}))

// 获取模板类型颜色
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

// 获取模板类型标签
const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    system: '系统模板',
    announcement: '公告模板',
    alert: '警报模板',
    reminder: '提醒模板',
    marketing: '营销模板'
  }
  return labels[type] || type
}

// 格式化时间
const formatTime = (time: string) => {
  return dayjs(time).format('YYYY-MM-DD HH:mm')
}

// 提取模板变量
const getTemplateVariables = (content: string): string[] => {
  const matches = content.match(/\{\{(\w+)\}\}/g)
  return matches ? [...new Set(matches.map(m => m.replace(/[{}]/g, '')))] : []
}

// 处理筛选变化
const handleFilterChange = () => {
  templateStore.setPage(1)
  loadTemplates()
}

// 处理搜索
const handleSearch = (keyword: string) => {
  if (keyword.trim()) {
    templateStore.actions.search(keyword, filters)
  } else {
    loadTemplates()
  }
}

// 处理表格变化
const handleTableChange = (pagination: any) => {
  templateStore.setPage(pagination.current)
  templateStore.setPageSize(pagination.pageSize)
  loadTemplates()
}

// 加载模板列表
const loadTemplates = async () => {
  try {
    await templateStore.actions.getList(filters)
  } catch (error) {
    message.error('加载模板列表失败')
  }
}

// 创建模板
const createTemplate = () => {
  router.push('/admin/template/create')
}

// 编辑模板
const editTemplate = (record: MessageTemplate) => {
  router.push(`/admin/template/edit/${record.id}`)
}

// 复制模板
const duplicateTemplate = (record: MessageTemplate) => {
  router.push(`/admin/template/create?duplicate=${record.id}`)
}

// 预览模板
const showPreview = (record: MessageTemplate) => {
  previewTemplate.value = record
  previewVariables.value = getTemplateVariables(record.content)
  previewValues.value = {}
  
  // 初始化预览值
  previewVariables.value.forEach(variable => {
    previewValues.value[variable] = `[${variable}]`
  })
  
  updatePreview()
  previewVisible.value = true
}

// 更新预览内容
const updatePreview = () => {
  if (!previewTemplate.value) return
  
  let content = previewTemplate.value.content
  Object.entries(previewValues.value).forEach(([key, value]) => {
    const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g')
    content = content.replace(regex, value || `[${key}]`)
  })
  
  previewContent.value = content.replace(/\n/g, '<br>')
}

// 切换模板状态
const toggleStatus = async (record: MessageTemplate) => {
  record.statusLoading = true
  try {
    const newStatus = record.status === 'active' ? 'inactive' : 'active'
    await templateStore.actions.updateStatus(record.id, newStatus)
    record.status = newStatus
    message.success(`模板已${newStatus === 'active' ? '启用' : '禁用'}`)
  } catch (error) {
    message.error('状态更新失败')
  } finally {
    record.statusLoading = false
  }
}

// 删除模板
const deleteTemplate = async (record: MessageTemplate) => {
  try {
    await templateStore.actions.delete(record.id)
    message.success('删除成功')
  } catch (error) {
    message.error('删除失败')
  }
}

// 批量启用
const batchEnable = async () => {
  if (selectedRowKeys.value.length === 0) return
  
  batchLoading.value = true
  try {
    const promises = selectedRowKeys.value.map(id => 
      templateStore.actions.updateStatus(id, 'active')
    )
    await Promise.all(promises)
    message.success(`已启用 ${selectedRowKeys.value.length} 个模板`)
    clearSelection()
    await loadTemplates()
  } catch (error) {
    message.error('批量启用失败')
  } finally {
    batchLoading.value = false
  }
}

// 批量禁用
const batchDisable = async () => {
  if (selectedRowKeys.value.length === 0) return
  
  batchLoading.value = true
  try {
    const promises = selectedRowKeys.value.map(id => 
      templateStore.actions.updateStatus(id, 'inactive')
    )
    await Promise.all(promises)
    message.success(`已禁用 ${selectedRowKeys.value.length} 个模板`)
    clearSelection()
    await loadTemplates()
  } catch (error) {
    message.error('批量禁用失败')
  } finally {
    batchLoading.value = false
  }
}

// 批量删除
const batchDelete = async () => {
  if (selectedRowKeys.value.length === 0) return
  
  batchLoading.value = true
  try {
    await templateStore.actions.batchDelete(selectedRowKeys.value)
    message.success(`已删除 ${selectedRowKeys.value.length} 个模板`)
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
  loadTemplates()
})
</script>

<style scoped>
.admin-template-list {
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

.template-name-cell {
  cursor: pointer;
}

.name-content {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.name-link {
  flex: 1;
  color: #1890ff;
  text-decoration: none;
  font-weight: 500;
}

.name-link:hover {
  text-decoration: underline;
}

.template-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: #666;
}

.description {
  flex: 1;
  color: #999;
}

.time {
  color: #999;
}

.variables-cell {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.text-muted {
  color: #999;
  font-style: italic;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-header h3 {
  margin: 0;
  font-size: 18px;
}

.preview-variables {
  margin: 16px 0;
}

.preview-variables h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
}

.variables-form {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.variable-input label {
  display: block;
  margin-bottom: 4px;
  font-size: 12px;
  color: #666;
  font-weight: 500;
}

.preview-content h4 {
  margin: 16px 0 12px 0;
  font-size: 14px;
  font-weight: 600;
}

.content-preview {
  padding: 16px;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  background: #fafafa;
  line-height: 1.6;
  min-height: 100px;
}
</style>