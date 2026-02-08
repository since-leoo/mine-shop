<template>
  <el-dialog
    v-model="dialogVisible"
    :title="dialogTitle"
    width="900px"
    :close-on-click-modal="false"
    destroy-on-close
    @close="handleClose"
  >
    <el-form
      :model="formData"
      :rules="rules"
      ref="formRef"
      label-position="top"
      class="message-form"
    >
      <el-row :gutter="24">
        <el-col :span="14">
          <!-- 基本信息 -->
          <el-form-item label="消息标题" prop="title" required>
            <el-input 
              v-model="formData.title" 
              placeholder="请输入消息标题"
              maxlength="200"
              show-word-limit
            />
          </el-form-item>

          <el-form-item label="消息内容" prop="content" required>
            <el-input 
              v-model="formData.content" 
              type="textarea"
              placeholder="请输入消息内容"
              :rows="6"
              maxlength="2000"
              show-word-limit
            />
          </el-form-item>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="消息类型" prop="type" required>
                <el-select v-model="formData.type" placeholder="选择消息类型" style="width: 100%">
                  <el-option value="system" label="系统消息" />
                  <el-option value="announcement" label="公告" />
                  <el-option value="alert" label="警报" />
                  <el-option value="reminder" label="提醒" />
                  <el-option value="marketing" label="营销消息" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="优先级" prop="priority">
                <el-select v-model="formData.priority" placeholder="选择优先级" style="width: 100%">
                  <el-option :value="1" label="低优先级" />
                  <el-option :value="2" label="较低优先级" />
                  <el-option :value="3" label="中等优先级" />
                  <el-option :value="4" label="较高优先级" />
                  <el-option :value="5" label="高优先级" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
        </el-col>

        <el-col :span="10">
          <!-- 收件人设置 -->
          <el-form-item label="收件人类型" prop="recipient_type" required>
            <el-radio-group v-model="formData.recipient_type">
              <el-radio value="all">全部用户</el-radio>
              <el-radio value="role">按角色</el-radio>
              <el-radio value="user">指定用户</el-radio>
            </el-radio-group>
          </el-form-item>

          <el-form-item 
            v-if="formData.recipient_type !== 'all'"
            label="选择收件人" 
            prop="recipient_ids"
          >
            <el-select
              v-model="formData.recipient_ids"
              multiple
              :placeholder="getRecipientPlaceholder()"
              style="width: 100%"
            >
              <el-option 
                v-for="recipient in recipients" 
                :key="recipient.id" 
                :value="recipient.id"
                :label="recipient.name"
              />
            </el-select>
          </el-form-item>

          <!-- 发送设置 -->
          <el-form-item label="发送渠道" prop="channels">
            <el-checkbox-group v-model="formData.channels">
              <el-checkbox value="database" label="站内信" />
              <el-checkbox value="email" label="邮件" />
              <el-checkbox value="sms" label="短信" />
            </el-checkbox-group>
          </el-form-item>

          <el-form-item label="发送时间">
            <el-radio-group v-model="sendTimeType">
              <el-radio value="now">立即发送</el-radio>
              <el-radio value="scheduled">定时发送</el-radio>
            </el-radio-group>
          </el-form-item>

          <el-form-item v-if="sendTimeType === 'scheduled'" label="调度时间" prop="scheduled_at">
            <el-date-picker
              v-model="scheduledTime"
              type="datetime"
              format="YYYY-MM-DD HH:mm:ss"
              value-format="YYYY-MM-DDTHH:mm:ss"
              placeholder="选择发送时间"
              style="width: 100%"
              @change="handleScheduledTimeChange"
            />
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button @click="saveDraft" :loading="saving">保存草稿</el-button>
        <el-button type="primary" @click="sendMessage" :loading="sending">
          {{ formData.scheduled_at ? '调度发送' : '立即发送' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useMessageStore } from '../../store/message'
import { ElMessage } from 'element-plus'
import type { CreateMessageData, Message } from '../../api/message'
import dayjs from 'dayjs'

// 定义组件名称
defineOptions({
  name: 'AdminMessageForm'
})

const props = defineProps<{
  visible: boolean
  message: Message | null
  mode: 'create' | 'edit' | 'duplicate'
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'success': []
}>()

const messageStore = useMessageStore()

// 弹窗可见性
const dialogVisible = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val)
})

// 弹窗标题
const dialogTitle = computed(() => {
  if (props.mode === 'edit') return '编辑消息'
  if (props.mode === 'duplicate') return '复制消息'
  return '创建消息'
})

// 表单引用
const formRef = ref()

// 状态
const saving = ref(false)
const sending = ref(false)

// 发送时间类型
const sendTimeType = ref<'now' | 'scheduled'>('now')
const scheduledTime = ref<string>()

// 表单数据
const formData = reactive<CreateMessageData & { id?: number }>({
  title: '',
  content: '',
  type: 'system',
  priority: 3,
  recipient_type: 'all',
  recipient_ids: [],
  channels: ['database'],
  scheduled_at: undefined,
  template_id: undefined,
  template_variables: {},
  extra_data: {}
})

// 收件人数据
const recipients = ref<Array<{ id: number; name: string }>>([
  { id: 1, name: '管理员' },
  { id: 2, name: '普通用户' }
])

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
  ]
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

// 处理调度时间变化
const handleScheduledTimeChange = (time: string | null) => {
  formData.scheduled_at = time ? dayjs(time).toISOString() : undefined
}

// 重置表单
const resetForm = () => {
  Object.assign(formData, {
    id: undefined,
    title: '',
    content: '',
    type: 'system',
    priority: 3,
    recipient_type: 'all',
    recipient_ids: [],
    channels: ['database'],
    scheduled_at: undefined,
    template_id: undefined,
    template_variables: {},
    extra_data: {}
  })
  sendTimeType.value = 'now'
  scheduledTime.value = undefined
}

// 加载消息数据
const loadMessageData = () => {
  if (!props.message) {
    resetForm()
    return
  }

  const msg = props.message
  Object.assign(formData, {
    id: props.mode === 'edit' ? msg.id : undefined,
    title: props.mode === 'duplicate' ? `${msg.title} (副本)` : msg.title,
    content: msg.content,
    type: msg.type,
    priority: msg.priority,
    recipient_type: msg.recipient_type || 'all',
    recipient_ids: msg.recipient_ids || [],
    channels: msg.channels || ['database'],
    scheduled_at: msg.scheduled_at,
    template_id: msg.template_id,
    template_variables: msg.template_variables || {},
    extra_data: msg.extra_data || {}
  })

  sendTimeType.value = msg.scheduled_at ? 'scheduled' : 'now'
  if (msg.scheduled_at) {
    scheduledTime.value = dayjs(msg.scheduled_at).format('YYYY-MM-DDTHH:mm:ss')
  }
}

// 监听弹窗打开
watch(() => props.visible, (val) => {
  if (val) {
    loadMessageData()
  }
})

// 关闭弹窗
const handleClose = () => {
  resetForm()
  emit('update:visible', false)
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
    
    if (props.mode === 'edit' && formData.id) {
      await messageStore.adminActions.update(formData.id, data)
      ElMessage.success('草稿保存成功')
    } else {
      await messageStore.adminActions.create(data)
      ElMessage.success('草稿创建成功')
    }
    emit('success')
    handleClose()
  } catch (error) {
    ElMessage.error('保存失败')
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
    
    if (props.mode === 'edit' && formData.id) {
      await messageStore.adminActions.update(formData.id, data)
      if (!formData.scheduled_at) {
        await messageStore.adminActions.send(formData.id)
      }
    } else {
      const response = await messageStore.adminActions.create(data)
      if (!formData.scheduled_at && response?.data?.id) {
        await messageStore.adminActions.send(response.data.id)
      }
    }
    
    ElMessage.success(formData.scheduled_at ? '消息调度成功' : '消息发送成功')
    emit('success')
    handleClose()
  } catch (error) {
    ElMessage.error('操作失败')
  } finally {
    sending.value = false
  }
}
</script>

<style scoped>
.message-form {
  padding: 0 16px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>
