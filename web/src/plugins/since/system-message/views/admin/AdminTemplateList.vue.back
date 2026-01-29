<template>
  <div class="admin-template-list">
    <div class="list-header">
      <div class="header-left">
        <h2>模板管理</h2>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="createTemplate">
          创建模板
        </el-button>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <div class="filter-left">
        <el-select
          v-model="filters.type"
          placeholder="模板类型"
          style="width: 120px"
          clearable
          @change="handleFilterChange"
        >
          <el-option value="system" label="系统模板" />
          <el-option value="announcement" label="公告模板" />
          <el-option value="alert" label="警报模板" />
          <el-option value="reminder" label="提醒模板" />
          <el-option value="marketing" label="营销模板" />
        </el-select>
        
        <el-select
          v-model="filters.status"
          placeholder="模板状态"
          style="width: 120px"
          clearable
          @change="handleFilterChange"
        >
          <el-option value="active" label="启用" />
          <el-option value="inactive" label="禁用" />
        </el-select>
      </div>
      
      <div class="filter-right">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索模板名称或内容"
          style="width: 250px"
          clearable
          @keyup.enter="handleSearch(searchKeyword)"
        >
          <template #append>
            <el-button @click="handleSearch(searchKeyword)">搜索</el-button>
          </template>
        </el-input>
      </div>
    </div>

    <!-- 批量操作栏 -->
    <div class="batch-actions" v-if="selectedRowKeys.length > 0">
      <span>已选择 {{ selectedRowKeys.length }} 项</span>
      <el-button @click="batchEnable" :loading="batchLoading">
        批量启用
      </el-button>
      <el-button @click="batchDisable" :loading="batchLoading">
        批量禁用
      </el-button>
      <el-button @click="batchDelete" :loading="batchLoading" type="danger">
        批量删除
      </el-button>
      <el-button @click="clearSelection">取消选择</el-button>
    </div>

    <!-- 模板列表 -->
    <el-table
      :data="templateStore.templates"
      v-loading="templateStore.loading"
      @selection-change="handleSelectionChange"
      row-key="id"
    >
      <el-table-column type="selection" width="55" />
      
      <el-table-column label="模板信息" min-width="300">
        <template #default="{ row }">
          <div class="template-name-cell">
            <div class="name-content">
              <a @click="editTemplate(row)" class="name-link">
                {{ row.name }}
              </a>
              <el-tag :type="getTypeTagType(row.type)" size="small">
                {{ getTypeLabel(row.type) }}
              </el-tag>
            </div>
            <div class="template-meta">
              <span class="description">{{ row.description }}</span>
              <span class="time">{{ formatTime(row.created_at) }}</span>
            </div>
          </div>
        </template>
      </el-table-column>
      
      <el-table-column label="模板变量" min-width="200">
        <template #default="{ row }">
          <div class="variables-cell">
            <el-tag 
              v-for="variable in getTemplateVariables(row.content)" 
              :key="variable"
              size="small"
              type="primary"
              class="variable-tag"
            >
              {{ variable }}
            </el-tag>
            <span v-if="getTemplateVariables(row.content).length === 0" class="text-muted">
              无变量
            </span>
          </div>
        </template>
      </el-table-column>
      
      <el-table-column label="使用次数" width="100">
        <template #default="{ row }">
          <el-statistic :value="row.usage_count || 0" />
        </template>
      </el-table-column>
      
      <el-table-column label="状态" width="80">
        <template #default="{ row }">
          <el-switch
            :model-value="row.status === 'active'"
            @change="toggleStatus(row)"
            :loading="row.statusLoading"
          />
        </template>
      </el-table-column>
      
      <el-table-column label="操作" width="200">
        <template #default="{ row }">
          <el-button type="primary" link size="small" @click="showPreview(row)">
            预览
          </el-button>
          <el-button type="primary" link size="small" @click="editTemplate(row)">
            编辑
          </el-button>
          <el-button type="primary" link size="small" @click="duplicateTemplate(row)">
            复制
          </el-button>
          <el-popconfirm
            title="确定要删除这个模板吗？"
            @confirm="deleteTemplate(row)"
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
        v-model:current-page="templateStore.currentPage"
        v-model:page-size="templateStore.pageSize"
        :total="templateStore.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>

    <!-- 模板预览弹窗 -->
    <el-dialog
      v-model="previewVisible"
      title="模板预览"
      width="800px"
    >
      <div v-if="previewTemplateData">
        <div class="preview-header">
          <h3>{{ previewTemplateData.name }}</h3>
          <el-tag :type="getTypeTagType(previewTemplateData.type)">
            {{ getTypeLabel(previewTemplateData.type) }}
          </el-tag>
        </div>
        
        <el-divider />
        
        <div class="preview-variables" v-if="previewVariables.length > 0">
          <h4>模板变量</h4>
          <div class="variables-form">
            <div 
              v-for="variable in previewVariables" 
              :key="variable"
              class="variable-input"
            >
              <label>{{ variable }}</label>
              <el-input 
                v-model="previewValues[variable]"
                :placeholder="`请输入 ${variable} 的值`"
                @input="updatePreview"
              />
            </div>
          </div>
        </div>
        
        <div class="preview-content">
          <h4>预览效果</h4>
          <div class="content-preview" v-html="previewContent"></div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTemplateStore } from '../../store/template'
import { ElMessage } from 'element-plus'
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
const previewTemplateData = ref<MessageTemplate | null>(null)
const previewVariables = ref<string[]>([])
const previewValues = ref<Record<string, string>>({})
const previewContent = ref('')

// 获取模板类型标签类型
const getTypeTagType = (type: string) => {
  const types: Record<string, '' | 'success' | 'warning' | 'danger' | 'info'> = {
    system: '',
    announcement: 'success',
    alert: 'danger',
    reminder: 'warning',
    marketing: 'info'
  }
  return types[type] || 'info'
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

// 处理选择变化
const handleSelectionChange = (selection: MessageTemplate[]) => {
  selectedRowKeys.value = selection.map(item => item.id)
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

// 处理分页大小变化
const handleSizeChange = (size: number) => {
  templateStore.setPageSize(size)
  loadTemplates()
}

// 处理页码变化
const handleCurrentChange = (page: number) => {
  templateStore.setPage(page)
  loadTemplates()
}

// 加载模板列表
const loadTemplates = async () => {
  try {
    await templateStore.actions.getList(filters)
  } catch (error) {
    ElMessage.error('加载模板列表失败')
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
  previewTemplateData.value = record
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
  if (!previewTemplateData.value) return
  
  let content = previewTemplateData.value.content
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
    ElMessage.success(`模板已${newStatus === 'active' ? '启用' : '禁用'}`)
  } catch (error) {
    ElMessage.error('状态更新失败')
  } finally {
    record.statusLoading = false
  }
}

// 删除模板
const deleteTemplate = async (record: MessageTemplate) => {
  try {
    await templateStore.actions.delete(record.id)
    ElMessage.success('删除成功')
  } catch (error) {
    ElMessage.error('删除失败')
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
    ElMessage.success(`已启用 ${selectedRowKeys.value.length} 个模板`)
    clearSelection()
    await loadTemplates()
  } catch (error) {
    ElMessage.error('批量启用失败')
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
    ElMessage.success(`已禁用 ${selectedRowKeys.value.length} 个模板`)
    clearSelection()
    await loadTemplates()
  } catch (error) {
    ElMessage.error('批量禁用失败')
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
    ElMessage.success(`已删除 ${selectedRowKeys.value.length} 个模板`)
    clearSelection()
  } catch (error) {
    ElMessage.error('批量删除失败')
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
  background: #ecf5ff;
  border: 1px solid #b3d8ff;
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
  color: #409eff;
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

.variable-tag {
  margin-right: 4px;
}

.text-muted {
  color: #999;
  font-style: italic;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
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
