<template>
  <div class="admin-template-form">
    <div class="form-header">
      <div class="header-left">
        <el-button @click="goBack" text class="back-btn">
          <el-icon><ArrowLeft /></el-icon>
          返回
        </el-button>
        <h2>{{ isEdit ? '编辑模板' : '创建模板' }}</h2>
      </div>
      <div class="header-right">
        <el-button @click="previewTemplate" :disabled="!canPreview">
          预览模板
        </el-button>
        <el-button @click="saveTemplate" :loading="saving">
          保存模板
        </el-button>
      </div>
    </div>

    <el-form
      :model="formData"
      :rules="rules"
      ref="formRef"
      label-position="top"
      class="template-form"
    >
      <el-row :gutter="24">
        <el-col :span="16">
          <!-- 基本信息 -->
          <el-card header="基本信息" class="form-card">
            <el-form-item label="模板名称" prop="name" required>
              <el-input 
                v-model="formData.name" 
                placeholder="请输入模板名称"
                maxlength="100"
                show-word-limit
              />
            </el-form-item>

            <el-form-item label="模板描述" prop="description">
              <el-input 
                v-model="formData.description" 
                type="textarea"
                placeholder="请输入模板描述"
                :rows="3"
                maxlength="500"
                show-word-limit
              />
            </el-form-item>

            <el-form-item label="模板类型" prop="type" required>
              <el-select v-model="formData.type" placeholder="选择模板类型">
                <el-option value="system" label="系统模板" />
                <el-option value="announcement" label="公告模板" />
                <el-option value="alert" label="警报模板" />
                <el-option value="reminder" label="提醒模板" />
                <el-option value="marketing" label="营销模板" />
              </el-select>
            </el-form-item>

            <el-form-item label="模板状态" prop="status">
              <el-radio-group v-model="formData.status">
                <el-radio value="active">启用</el-radio>
                <el-radio value="inactive">禁用</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-card>

          <!-- 模板内容 -->
          <el-card header="模板内容" class="form-card">
            <el-form-item label="模板内容" prop="content" required>
              <div class="content-editor">
                <div class="editor-toolbar">
                  <el-button @click="insertVariable" size="small">
                    插入变量
                  </el-button>
                  <el-button @click="formatContent" size="small">
                    格式化
                  </el-button>
                  <el-button @click="clearContent" size="small">
                    清空内容
                  </el-button>
                </div>
                
                <el-input 
                  v-model="formData.content" 
                  type="textarea"
                  placeholder="请输入模板内容，使用 {{变量名}} 格式插入变量"
                  :rows="12"
                  class="content-textarea"
                  @input="analyzeVariables"
                />
                
                <div class="content-help">
                  <div class="help-item">
                    <strong>变量语法：</strong>使用 <code>{{变量名}}</code> 格式插入变量
                  </div>
                  <div class="help-item">
                    <strong>示例：</strong>尊敬的 <code>{{用户名}}</code>，您有一条新消息：<code>{{消息内容}}</code>
                  </div>
                </div>
              </div>
            </el-form-item>
          </el-card>

          <!-- 变量管理 -->
          <el-card header="模板变量" class="form-card">
            <div class="variables-section">
              <div class="variables-detected" v-if="detectedVariables.length > 0">
                <h4>检测到的变量</h4>
                <div class="variables-list">
                  <el-tag 
                    v-for="variable in detectedVariables" 
                    :key="variable"
                    type="primary"
                    class="variable-tag"
                  >
                    {{ variable }}
                  </el-tag>
                </div>
              </div>
              
              <div class="variables-custom">
                <h4>变量说明</h4>
                <div class="variable-descriptions">
                  <div 
                    v-for="variable in detectedVariables" 
                    :key="variable"
                    class="variable-desc-item"
                  >
                    <label>{{ variable }}</label>
                    <el-input 
                      v-model="variableDescriptions[variable]"
                      placeholder="请输入变量说明"
                    />
                  </div>
                </div>
                
                <div class="common-variables">
                  <h4>常用变量</h4>
                  <div class="common-vars-list">
                    <el-tag 
                      v-for="commonVar in commonVariables" 
                      :key="commonVar.name"
                      @click="insertCommonVariable(commonVar.name)"
                      class="common-var-tag"
                    >
                      {{ commonVar.name }} - {{ commonVar.description }}
                    </el-tag>
                  </div>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>

        <el-col :span="8">
          <!-- 预览区域 -->
          <el-card header="实时预览" class="form-card preview-card">
            <div class="preview-section">
              <div class="preview-controls">
                <el-button @click="refreshPreview" size="small" type="primary">
                  刷新预览
                </el-button>
              </div>
              
              <div class="preview-variables" v-if="detectedVariables.length > 0">
                <h4>预览变量值</h4>
                <div class="preview-vars">
                  <div 
                    v-for="variable in detectedVariables" 
                    :key="variable"
                    class="preview-var-item"
                  >
                    <label>{{ variable }}</label>
                    <el-input 
                      v-model="previewValues[variable]"
                      size="small"
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
          </el-card>

          <!-- 使用统计 -->
          <el-card header="使用统计" class="form-card" v-if="isEdit">
            <div class="usage-stats">
              <el-statistic
                title="使用次数"
                :value="formData.usage_count || 0"
                class="stat-item"
              />
              <el-statistic
                title="最后使用"
                :value="formData.last_used_at ? formatTime(formData.last_used_at) : '从未使用'"
                class="stat-item"
              />
            </div>
          </el-card>

          <!-- 操作历史 -->
          <el-card header="操作历史" class="form-card" v-if="isEdit">
            <div class="history-list">
              <div class="history-item">
                <div class="history-action">创建模板</div>
                <div class="history-time">{{ formatTime(formData.created_at) }}</div>
              </div>
              <div class="history-item" v-if="formData.updated_at !== formData.created_at">
                <div class="history-action">最后更新</div>
                <div class="history-time">{{ formatTime(formData.updated_at) }}</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </el-form>

    <!-- 变量插入弹窗 -->
    <el-dialog
      v-model="variableModalVisible"
      title="插入变量"
      width="400px"
    >
      <el-form label-position="top">
        <el-form-item label="变量名称">
          <el-input 
            v-model="newVariableName" 
            placeholder="请输入变量名称"
            @keyup.enter="confirmInsertVariable"
          />
        </el-form-item>
        <el-form-item label="变量说明">
          <el-input 
            v-model="newVariableDesc" 
            placeholder="请输入变量说明（可选）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="variableModalVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmInsertVariable">确定</el-button>
      </template>
    </el-dialog>

    <!-- 模板预览弹窗 -->
    <el-dialog
      v-model="previewModalVisible"
      title="模板预览"
      width="800px"
    >
      <div class="modal-preview">
        <div class="preview-header">
          <h3>{{ formData.name || '未命名模板' }}</h3>
          <el-tag :type="getTypeTagType(formData.type)">
            {{ getTypeLabel(formData.type) }}
          </el-tag>
        </div>
        
        <el-divider />
        
        <div class="modal-preview-content" v-html="previewContent"></div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTemplateStore } from '../../store/template'
import { ElMessage } from 'element-plus'
import { ArrowLeft } from '@element-plus/icons-vue'
import type { CreateTemplateData } from '../../api/template'
import dayjs from 'dayjs'

const route = useRoute()
const router = useRouter()
const templateStore = useTemplateStore()

// 表单引用
const formRef = ref()

// 状态
const saving = ref(false)
const variableModalVisible = ref(false)
const previewModalVisible = ref(false)

// 是否编辑模式
const isEdit = computed(() => !!route.params.id)

// 表单数据
const formData = reactive<CreateTemplateData & { 
  id?: number
  usage_count?: number
  last_used_at?: string
  created_at?: string
  updated_at?: string
}>({
  name: '',
  description: '',
  type: 'system',
  content: '',
  status: 'active'
})

// 变量相关
const detectedVariables = ref<string[]>([])
const variableDescriptions = ref<Record<string, string>>({})
const previewValues = ref<Record<string, string>>({})
const previewContent = ref('')

// 新变量
const newVariableName = ref('')
const newVariableDesc = ref('')

// 常用变量
const commonVariables = [
  { name: '用户名', description: '接收消息的用户名称' },
  { name: '用户ID', description: '接收消息的用户ID' },
  { name: '消息标题', description: '消息的标题' },
  { name: '消息内容', description: '消息的具体内容' },
  { name: '发送时间', description: '消息发送的时间' },
  { name: '系统名称', description: '发送消息的系统名称' },
  { name: '链接地址', description: '相关的链接地址' }
]

// 表单验证规则
const rules = {
  name: [
    { required: true, message: '请输入模板名称', trigger: 'blur' },
    { max: 100, message: '模板名称长度不能超过100字符', trigger: 'blur' }
  ],
  type: [
    { required: true, message: '请选择模板类型', trigger: 'change' }
  ],
  content: [
    { required: true, message: '请输入模板内容', trigger: 'blur' },
    { max: 5000, message: '模板内容长度不能超过5000字符', trigger: 'blur' }
  ]
}

// 计算属性
const canPreview = computed(() => {
  return formData.name && formData.content
})

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
  return dayjs(time).format('YYYY-MM-DD HH:mm:ss')
}

// 返回上一页
const goBack = () => {
  router.back()
}

// 分析模板变量
const analyzeVariables = () => {
  const matches = formData.content.match(/\{\{(\w+)\}\}/g)
  const variables = matches ? [...new Set(matches.map(m => m.replace(/[{}]/g, '')))] : []
  
  detectedVariables.value = variables
  
  // 初始化预览值
  variables.forEach(variable => {
    if (!previewValues.value[variable]) {
      previewValues.value[variable] = `[${variable}示例值]`
    }
  })
  
  updatePreview()
}

// 更新预览内容
const updatePreview = () => {
  let content = formData.content
  Object.entries(previewValues.value).forEach(([key, value]) => {
    const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g')
    content = content.replace(regex, value || `[${key}]`)
  })
  
  previewContent.value = content.replace(/\n/g, '<br>')
}

// 刷新预览
const refreshPreview = () => {
  analyzeVariables()
}

// 插入变量
const insertVariable = () => {
  newVariableName.value = ''
  newVariableDesc.value = ''
  variableModalVisible.value = true
}

// 确认插入变量
const confirmInsertVariable = () => {
  if (!newVariableName.value.trim()) {
    ElMessage.error('请输入变量名称')
    return
  }
  
  const variableName = newVariableName.value.trim()
  const variableText = `{{${variableName}}}`
  
  // 插入到内容中
  formData.content += variableText
  
  // 添加变量说明
  if (newVariableDesc.value.trim()) {
    variableDescriptions.value[variableName] = newVariableDesc.value.trim()
  }
  
  // 重新分析变量
  analyzeVariables()
  
  variableModalVisible.value = false
  ElMessage.success('变量插入成功')
}

// 插入常用变量
const insertCommonVariable = (variableName: string) => {
  const variableText = `{{${variableName}}}`
  formData.content += variableText
  analyzeVariables()
  ElMessage.success(`已插入变量：${variableName}`)
}

// 格式化内容
const formatContent = () => {
  // 简单的格式化：添加适当的换行
  formData.content = formData.content
    .replace(/。/g, '。\n')
    .replace(/！/g, '！\n')
    .replace(/？/g, '？\n')
    .replace(/\n+/g, '\n')
    .trim()
  
  analyzeVariables()
  ElMessage.success('内容格式化完成')
}

// 清空内容
const clearContent = () => {
  formData.content = ''
  detectedVariables.value = []
  previewValues.value = {}
  previewContent.value = ''
}

// 预览模板
const previewTemplate = () => {
  if (!canPreview.value) {
    ElMessage.error('请先填写模板名称和内容')
    return
  }
  
  updatePreview()
  previewModalVisible.value = true
}

// 保存模板
const saveTemplate = async () => {
  try {
    await formRef.value.validate()
  } catch (error) {
    return
  }
  
  saving.value = true
  try {
    const data = {
      name: formData.name,
      description: formData.description,
      type: formData.type,
      content: formData.content,
      status: formData.status,
      variables: detectedVariables.value,
      variable_descriptions: variableDescriptions.value
    }
    
    if (isEdit.value) {
      await templateStore.actions.update(Number(route.params.id), data)
      ElMessage.success('模板更新成功')
    } else {
      await templateStore.actions.create(data)
      ElMessage.success('模板创建成功')
    }
    
    router.push('/admin/template')
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 加载模板详情（编辑模式）
const loadTemplateDetail = async () => {
  if (!isEdit.value) return
  
  try {
    const response = await templateStore.actions.getDetail(Number(route.params.id))
    const template = response.data
    
    Object.assign(formData, {
      id: template.id,
      name: template.name,
      description: template.description,
      type: template.type,
      content: template.content,
      status: template.status,
      usage_count: template.usage_count,
      last_used_at: template.last_used_at,
      created_at: template.created_at,
      updated_at: template.updated_at
    })
    
    // 分析变量
    analyzeVariables()
    
    // 加载变量说明
    if (template.variable_descriptions) {
      variableDescriptions.value = template.variable_descriptions
    }
  } catch (error) {
    ElMessage.error('加载模板详情失败')
    router.push('/admin/template')
  }
}

// 初始化
onMounted(async () => {
  await loadTemplateDetail()
  
  // 如果是复制模式
  const duplicateId = route.query.duplicate
  if (duplicateId && !isEdit.value) {
    try {
      const response = await templateStore.actions.getDetail(Number(duplicateId))
      const template = response.data
      
      Object.assign(formData, {
        name: `${template.name} - 副本`,
        description: template.description,
        type: template.type,
        content: template.content,
        status: 'inactive' // 复制的模板默认禁用
      })
      
      analyzeVariables()
      
      if (template.variable_descriptions) {
        variableDescriptions.value = template.variable_descriptions
      }
    } catch (error) {
      ElMessage.error('加载模板失败')
    }
  }
})
</script>

<style scoped>
.admin-template-form {
  padding: 24px;
  background: #f5f5f5;
  min-height: 100vh;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding: 16px 24px;
  background: #fff;
  border-radius: 6px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 8px;
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

.template-form {
  background: transparent;
}

.form-card {
  margin-bottom: 16px;
}

.preview-card {
  position: sticky;
  top: 24px;
}

.content-editor {
  border: 1px solid #dcdfe6;
  border-radius: 6px;
}

.editor-toolbar {
  padding: 8px 12px;
  border-bottom: 1px solid #dcdfe6;
  background: #fafafa;
  display: flex;
  gap: 8px;
}

.content-textarea :deep(.el-textarea__inner) {
  border: none !important;
  box-shadow: none !important;
}

.content-help {
  padding: 12px;
  background: #f9f9f9;
  border-top: 1px solid #dcdfe6;
  font-size: 12px;
}

.help-item {
  margin-bottom: 4px;
}

.help-item code {
  background: #f0f0f0;
  padding: 2px 4px;
  border-radius: 3px;
  font-family: monospace;
}

.variables-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.variables-detected h4,
.variables-custom h4,
.common-variables h4 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
}

.variables-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.variable-tag {
  cursor: default;
}

.variable-descriptions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.variable-desc-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.variable-desc-item label {
  min-width: 80px;
  font-size: 12px;
  color: #666;
}

.common-vars-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.common-var-tag {
  cursor: pointer;
  transition: all 0.2s;
}

.common-var-tag:hover {
  background: #409eff;
  color: white;
}

.preview-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.preview-controls {
  display: flex;
  justify-content: center;
}

.preview-variables h4,
.preview-content h4 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
}

.preview-vars {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.preview-var-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.preview-var-item label {
  font-size: 12px;
  color: #666;
}

.content-preview {
  padding: 12px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  background: #fafafa;
  line-height: 1.6;
  min-height: 100px;
}

.usage-stats {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.stat-item {
  text-align: center;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px;
  background: #fafafa;
  border-radius: 4px;
}

.history-action {
  font-size: 12px;
  color: #666;
}

.history-time {
  font-size: 12px;
  color: #999;
}

.modal-preview {
  padding: 16px 0;
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

.modal-preview-content {
  padding: 16px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  background: #fafafa;
  line-height: 1.6;
  min-height: 200px;
}
</style>
