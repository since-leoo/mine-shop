<template>
  <div class="admin-message-form">
    <div class="form-header">
      <div class="header-left">
        <a-button @click="goBack" type="text" class="back-btn">
          <template #icon>
            <ArrowLeftOutlined />
          </template>
          返回
        </a-button>
        <h2>{{ isEdit ? '编辑消息' : '创建消息' }}</h2>
      </div>
      <div class="header-right">
        <a-button @click="saveDraft" :loading="saving">
          保存草稿
        </a-button>
        <a-button type="primary" @click="sendMessage" :loading="sending">
          {{ formData.scheduled_at ? '调度发送' : '立即发送' }}
        </a-button>
      </div>
    </div>

    <a-form
      :model="formData"
      :rules="rules"
      ref="formRef"
      layout="vertical"
      class="message-form"
    >
      <a-row :gutter="24">
        <a-col :span="16">
          <!-- 基本信息 -->
          <a-card title="基本信息" class="form-card">
            <a-form-item label="消息标题" name="title" required>
              <a-input 
                v-model:value="formData.title" 
                placeholder="请输入消息标题"
                maxlength="200"
                show-count
              />
            </a-form-item>

            <a-form-item label="消息内容" name="content" required>
              <a-textarea 
                v-model:value="formData.content" 
                placeholder="请输入消息内容"
                :rows="8"
                maxlength="2000"
                show-count
              />
            </a-form-item>

            <a-form-item label="消息类型" name="type" required>
              <a-select v-model:value="formData.type" placeholder="选择消息类型">
                <a-select-option value="system">系统消息</a-select-option>
                <a-select-option value="announcement">公告</a-select-option>
                <a-select-option value="alert">警报</a-select-option>
                <a-select-option value="reminder">提醒</a-select-option>
                <a-select-option value="marketing">营销消息</a-select-option>
              </a-select>
            </a-form-item>

            <a-form-item label="优先级" name="priority">
              <a-select v-model:value="formData.priority" placeholder="选择优先级">
                <a-select-option :value="1">低优先级</a-select-option>
                <a-select-option :value="2">较低优先级</a-select-option>
                <a-select-option :value="3">中等优先级</a-select-option>
                <a-select-option :value="4">较高优先级</a-select-option>
                <a-select-option :value="5">高优先级</a-select-option>
              </a-select>
            </a-form-item>
          </a-card>

          <!-- 模板设置 -->
          <a-card title="模板设置" class="form-card">
            <a-form-item label="使用模板" name="template_id">
              <a-select 
                v-model:value="formData.template_id" 
                placeholder="选择消息模板（可选）"
                allowClear
                @change="handleTemplateChange"
              >
                <a-select-option 
                  v-for="template in templates" 
                  :key="template.id" 
                  :value="template.id"
                >
                  {{ template.name }}
                </a-select-option>
              </a-select>
            </a-form-item>

            <div v-if="formData.template_id && selectedTemplate" class="template-variables">
              <h4>模板变量</h4>
              <div class="variables-grid">
                <div 
                  v-for="variable in templateVariables" 
                  :key="variable"
                  class="variable-item"
                >
                  <label>{{ variable }}</label>
                  <a-input 
                    v-model:value="formData.template_variables[variable]"
                    :placeholder="`请输入 ${variable} 的值`"
                  />
                </div>
              </div>
              <a-button @click="previewTemplate" type="link">
                预览模板效果
              </a-button>
            </div>
          </a-card>

          <!-- 附加数据 -->
          <a-card title="附加数据" class="form-card">
            <div class="extra-data-section">
              <div class="section-header">
                <span>自定义数据（JSON格式）</span>
                <a-button @click="addExtraDataField" type="link" size="small">
                  添加字段
                </a-button>
              </div>
              
              <div class="extra-data-fields">
                <div 
                  v-for="(field, index) in extraDataFields" 
                  :key="index"
                  class="extra-field"
                >
                  <a-input 
                    v-model:value="field.key"
                    placeholder="字段名"
                    style="width: 30%"
                    @change="updateExtraData"
                  />
                  <a-input 
                    v-model:value="field.value"
                    placeholder="字段值"
                    style="width: 60%"
                    @change="updateExtraData"
                  />
                  <a-button 
                    @click="removeExtraDataField(index)"
                    type="text"
                    danger
                    size="small"
                  >
                    删除
                  </a-button>
                </div>
              </div>
            </div>
          </a-card>
        </a-col>

        <a-col :span="8">
          <!-- 收件人设置 -->
          <a-card title="收件人设置" class="form-card">
            <a-form-item label="收件人类型" name="recipient_type" required>
              <a-radio-group v-model:value="formData.recipient_type">
                <a-radio value="all">全部用户</a-radio>
                <a-radio value="role">按角色</a-radio>
                <a-radio value="user">指定用户</a-radio>
                <a-radio value="department">按部门</a-radio>
              </a-radio-group>
            </a-form-item>

            <a-form-item 
              v-if="formData.recipient_type !== 'all'"
              label="选择收件人" 
              name="recipient_ids"
              required
            >
              <a-select
                v-model:value="formData.recipient_ids"
                mode="multiple"
                :placeholder="getRecipientPlaceholder()"
                style="width: 100%"
                :loading="recipientLoading"
                @focus="loadRecipients"
              >
                <a-select-option 
                  v-for="recipient in recipients" 
                  :key="recipient.id" 
                  :value="recipient.id"
                >
                  {{ recipient.name }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-card>

          <!-- 发送设置 -->
          <a-card title="发送设置" class="form-card">
            <a-form-item label="发送渠道" name="channels">
              <a-checkbox-group v-model:value="formData.channels">
                <a-checkbox value="socketio">实时通知</a-checkbox>
                <a-checkbox value="email">邮件</a-checkbox>
                <a-checkbox value="sms">短信</a-checkbox>
                <a-checkbox value="push">推送</a-checkbox>
              </a-checkbox-group>
            </a-form-item>

            <a-form-item label="发送时间">
              <a-radio-group v-model:value="sendTimeType">
                <a-radio value="now">立即发送</a-radio>
                <a-radio value="scheduled">定时发送</a-radio>
              </a-radio-group>
            </a-form-item>

            <a-form-item 
              v-if="sendTimeType === 'scheduled'"
              label="调度时间" 
              name="scheduled_at"
            >
              <a-date-picker
                v-model:value="scheduledTime"
                show-time
                format="YYYY-MM-DD HH:mm:ss"
                placeholder="选择发送时间"
                style="width: 100%"
                @change="handleScheduledTimeChange"
              />
            </a-form-item>
          </a-card>

          <!-- 预览区域 -->
          <a-card title="消息预览" class="form-card">
            <div class="message-preview">
              <div class="preview-header">
                <div class="preview-title">{{ formData.title || '消息标题' }}</div>
                <a-tag :color="getTypeColor(formData.type)">
                  {{ getTypeLabel(formData.type) }}
                </a-tag>
              </div>
              <div class="preview-content">
                {{ formData.content || '消息内容' }}
              </div>
              <div class="preview-meta">
                <span class="priority" :class="`priority-${formData.priority}`">
                  {{ getPriorityLabel(formData.priority) }}
                </span>
                <span class="channels">
                  渠道：{{ formData.channels?.join(', ') || '无' }}
                </span>
              </div>
            </div>
          </a-card>
        </a-col>
      </a-row>
    </a-form>

    <!-- 模板预览弹窗 -->
    <a-modal
      v-model:open="templatePreviewVisible"
      title="模板预览"
      :footer="null"
      width="600px"
    >
      <div class="template-preview" v-html="templatePreviewContent"></div>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMessageStore } from '../../store/message'
import { useTemplateStore } from '../../store/template'
import { message } from 'ant-design-vue'
import { ArrowLeftOutlined } from '@ant-design/icons-vue'
import type { CreateMessageData, UpdateMessageData } from '../../api/message'
import type { MessageTemplate } from '../../api/template'
import dayjs from 'dayjs'
import type { Dayjs } from 'dayjs'

const route = useRoute()
const router = useRouter()
const messageStore = useMessageStore()
const templateStore = useTemplateStore()

// 表单引用
const formRef = ref()

// 状态
const saving = ref(false)
const sending = ref(false)
const recipientLoading = ref(false)
const templatePreviewVisible = ref(false)
const templatePreviewContent = ref('')

// 是否编辑模式
const isEdit = computed(() => !!route.params.id)

// 发送时间类型
const sendTimeType = ref<'now' | 'scheduled'>('now')
const scheduledTime = ref<Dayjs>()

// 表单数据
const formData = reactive<CreateMessageData & { id?: number }>({
  title: '',
  content: '',
  type: 'system',
  priority: 3,
  recipient_type: 'all',
  recipient_ids: [],
  channels: ['socketio'],
  scheduled_at: undefined,
  template_id: undefined,
  template_variables: {},
  extra_data: {}
})

// 附加数据字段
const extraDataFields = ref<Array<{ key: string; value: string }>>([])

// 模板和收件人数据
const templates = ref<MessageTemplate[]>([])
const recipients = ref<Array<{ id: number; name: string }>>([])

// 选中的模板
const selectedTemplate = computed(() => 
  templates.value.find(t => t.id === formData.template_id)
)

// 模板变量
const templateVariables = computed(() => {
  if (!selectedTemplate.value) return []
  
  // 从模板内容中提取变量（简单实现）
  const matches = selectedTemplate.value.content.match(/\{\{(\w+)\}\}/g)
  return matches ? matches.map(m => m.replace(/[{}]/g, '')) : []
})

// 表单验证规则
const rules = {
  title: [
    { required: true, message: '请输入消息标题', trigger: 'blur' },
    { max: 200, message: '标题长度不能超过200字符', trigger: 'blur' }
  ],
  content: [
    { required: true, message: '请输入消息内容', trigger: 'blur' },
    { max: 2000, message: '内容长度不能超过2000字符', trigger: 'blur' }
  ],
  type: [
    { required: true, message: '请选择消息类型', trigger: 'change' }
  ],
  recipient_type: [
    { required: true, message: '请选择收件人类型', trigger: 'change' }
  ],
  recipient_ids: [
    { 
      validator: (rule: any, value: any) => {
        if (formData.recipient_type !== 'all' && (!value || value.length === 0)) {
          return Promise.reject('请选择收件人')
        }
        return Promise.resolve()
      },
      trigger: 'change'
    }
  ]
}

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

// 获取收件人占位符
const getRecipientPlaceholder = () => {
  const placeholders: Record<string, string> = {
    role: '选择角色',
    user: '选择用户',
    department: '选择部门'
  }
  return placeholders[formData.recipient_type] || '选择收件人'
}

// 返回上一页
const goBack = () => {
  router.back()
}

// 处理模板变化
const handleTemplateChange = (templateId: number | undefined) => {
  if (templateId && selectedTemplate.value) {
    // 初始化模板变量
    formData.template_variables = {}
    templateVariables.value.forEach(variable => {
      formData.template_variables![variable] = ''
    })
  } else {
    formData.template_variables = {}
  }
}

// 处理调度时间变化
const handleScheduledTimeChange = (time: Dayjs | null) => {
  formData.scheduled_at = time ? time.toISOString() : undefined
}

// 添加附加数据字段
const addExtraDataField = () => {
  extraDataFields.value.push({ key: '', value: '' })
}

// 移除附加数据字段
const removeExtraDataField = (index: number) => {
  extraDataFields.value.splice(index, 1)
  updateExtraData()
}

// 更新附加数据
const updateExtraData = () => {
  const extraData: Record<string, any> = {}
  extraDataFields.value.forEach(field => {
    if (field.key && field.value) {
      extraData[field.key] = field.value
    }
  })
  formData.extra_data = extraData
}

// 加载模板列表
const loadTemplates = async () => {
  try {
    const response = await templateStore.actions.getList()
    templates.value = response.data.data
  } catch (error) {
    console.error('Failed to load templates:', error)
  }
}

// 加载收件人列表
const loadRecipients = async () => {
  if (formData.recipient_type === 'all') return
  
  recipientLoading.value = true
  try {
    // 这里应该根据 recipient_type 加载不同的数据
    // 暂时使用模拟数据
    recipients.value = [
      { id: 1, name: '管理员' },
      { id: 2, name: '普通用户' }
    ]
  } catch (error) {
    console.error('Failed to load recipients:', error)
  } finally {
    recipientLoading.value = false
  }
}

// 预览模板
const previewTemplate = async () => {
  if (!selectedTemplate.value) return
  
  try {
    const response = await templateStore.actions.preview(
      selectedTemplate.value.id,
      formData.template_variables || {}
    )
    templatePreviewContent.value = response.data.content
    templatePreviewVisible.value = true
  } catch (error) {
    message.error('预览失败')
  }
}

// 保存草稿
const saveDraft = async () => {
  try {
    await formRef.value.validate()
  } catch (error) {
    return
  }
  
  saving.value = true
  try {
    const data = { ...formData, status: 'draft' }
    
    if (isEdit.value) {
      await messageStore.adminActions.update(Number(route.params.id), data)
      message.success('草稿保存成功')
    } else {
      await messageStore.adminActions.create(data)
      message.success('草稿创建成功')
      router.push('/admin/message')
    }
  } catch (error) {
    message.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 发送消息
const sendMessage = async () => {
  try {
    await formRef.value.validate()
  } catch (error) {
    return
  }
  
  sending.value = true
  try {
    const data = { 
      ...formData, 
      status: formData.scheduled_at ? 'scheduled' : 'sent'
    }
    
    if (isEdit.value) {
      await messageStore.adminActions.update(Number(route.params.id), data)
      if (!formData.scheduled_at) {
        await messageStore.adminActions.send(Number(route.params.id))
      }
    } else {
      const response = await messageStore.adminActions.create(data)
      if (!formData.scheduled_at) {
        await messageStore.adminActions.send(response.data.id)
      }
    }
    
    message.success(formData.scheduled_at ? '消息调度成功' : '消息发送成功')
    router.push('/admin/message')
  } catch (error) {
    message.error('操作失败')
  } finally {
    sending.value = false
  }
}

// 加载消息详情（编辑模式）
const loadMessageDetail = async () => {
  if (!isEdit.value) return
  
  try {
    const response = await messageStore.adminActions.getDetail(Number(route.params.id))
    const msg = response.data
    
    Object.assign(formData, {
      id: msg.id,
      title: msg.title,
      content: msg.content,
      type: msg.type,
      priority: msg.priority,
      recipient_type: msg.recipient_type,
      recipient_ids: msg.recipient_ids || [],
      channels: msg.channels || [],
      scheduled_at: msg.scheduled_at,
      template_id: msg.template_id,
      template_variables: msg.template_variables || {},
      extra_data: msg.extra_data || {}
    })
    
    // 设置发送时间类型
    sendTimeType.value = msg.scheduled_at ? 'scheduled' : 'now'
    if (msg.scheduled_at) {
      scheduledTime.value = dayjs(msg.scheduled_at)
    }
    
    // 设置附加数据字段
    if (msg.extra_data) {
      extraDataFields.value = Object.entries(msg.extra_data).map(([key, value]) => ({
        key,
        value: String(value)
      }))
    }
  } catch (error) {
    message.error('加载消息详情失败')
    router.push('/admin/message')
  }
}

// 初始化
onMounted(async () => {
  await Promise.all([
    loadTemplates(),
    loadMessageDetail()
  ])
})
</script>

<style scoped>
.admin-message-form {
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

.message-form {
  background: transparent;
}

.form-card {
  margin-bottom: 16px;
}

.template-variables {
  margin-top: 16px;
  padding: 16px;
  background: #fafafa;
  border-radius: 6px;
}

.template-variables h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
}

.variables-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 12px;
}

.variable-item label {
  display: block;
  margin-bottom: 4px;
  font-size: 12px;
  color: #666;
}

.extra-data-section {
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  padding: 16px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.extra-data-fields {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.extra-field {
  display: flex;
  gap: 8px;
  align-items: center;
}

.message-preview {
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  padding: 16px;
  background: #fafafa;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.preview-title {
  font-weight: 600;
  font-size: 16px;
}

.preview-content {
  margin-bottom: 12px;
  line-height: 1.6;
  color: #666;
}

.preview-meta {
  display: flex;
  gap: 12px;
  align-items: center;
  font-size: 12px;
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

.channels {
  color: #999;
}

.template-preview {
  padding: 16px;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  background: #fafafa;
  line-height: 1.6;
}
</style>