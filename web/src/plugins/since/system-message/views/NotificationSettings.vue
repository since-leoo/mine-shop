<template>
  <div class="notification-settings">
    <div class="settings-header">
      <h2>通知设置</h2>
      <p class="settings-desc">管理您的消息通知偏好设置</p>
    </div>

    <a-spin :spinning="preferenceStore.loading">
      <div class="settings-content" v-if="preference">
        <!-- 通知渠道设置 -->
        <a-card title="通知渠道" class="settings-card">
          <template #extra>
            <a-button @click="resetChannelPreferences" size="small">
              重置默认
            </a-button>
          </template>
          
          <div class="channel-settings">
            <div class="channel-item" v-for="(enabled, channel) in preference.channel_preferences" :key="channel">
              <div class="channel-info">
                <div class="channel-name">
                  {{ preferenceStore.utils.getChannelDisplayName(channel) }}
                </div>
                <div class="channel-desc">{{ getChannelDescription(channel) }}</div>
              </div>
              <a-switch 
                v-model:checked="preference.channel_preferences[channel]"
                @change="updateChannelPreference(channel, $event)"
              />
            </div>
          </div>
        </a-card>

        <!-- 消息类型设置 -->
        <a-card title="消息类型" class="settings-card">
          <template #extra>
            <a-button @click="resetTypePreferences" size="small">
              重置默认
            </a-button>
          </template>
          
          <div class="type-settings">
            <div class="type-item" v-for="(enabled, type) in preference.type_preferences" :key="type">
              <div class="type-info">
                <div class="type-name">
                  {{ preferenceStore.utils.getTypeDisplayName(type) }}
                </div>
                <div class="type-desc">{{ getTypeDescription(type) }}</div>
              </div>
              <a-switch 
                v-model:checked="preference.type_preferences[type]"
                @change="updateTypePreference(type, $event)"
              />
            </div>
          </div>
        </a-card>

        <!-- 免打扰设置 -->
        <a-card title="免打扰时间" class="settings-card">
          <div class="dnd-settings">
            <div class="dnd-enable">
              <div class="setting-item">
                <div class="setting-info">
                  <div class="setting-name">启用免打扰</div>
                  <div class="setting-desc">在指定时间段内不接收通知</div>
                </div>
                <a-switch 
                  v-model:checked="preference.do_not_disturb_enabled"
                  @change="updateDoNotDisturbEnabled"
                />
              </div>
            </div>
            
            <div class="dnd-time" v-if="preference.do_not_disturb_enabled">
              <a-row :gutter="16">
                <a-col :span="12">
                  <div class="time-setting">
                    <label>开始时间</label>
                    <a-time-picker
                      v-model:value="dndStartTime"
                      format="HH:mm"
                      @change="updateDoNotDisturbTime"
                      style="width: 100%"
                    />
                  </div>
                </a-col>
                <a-col :span="12">
                  <div class="time-setting">
                    <label>结束时间</label>
                    <a-time-picker
                      v-model:value="dndEndTime"
                      format="HH:mm"
                      @change="updateDoNotDisturbTime"
                      style="width: 100%"
                    />
                  </div>
                </a-col>
              </a-row>
              
              <div class="dnd-status" v-if="preferenceStore.isDoNotDisturbActive">
                <a-alert
                  message="免打扰模式已激活"
                  description="当前时间在免打扰时间段内，您不会收到通知"
                  type="info"
                  show-icon
                />
              </div>
            </div>
          </div>
        </a-card>

        <!-- 优先级过滤 -->
        <a-card title="优先级过滤" class="settings-card">
          <div class="priority-settings">
            <div class="setting-item">
              <div class="setting-info">
                <div class="setting-name">最小优先级</div>
                <div class="setting-desc">只接收指定优先级及以上的消息通知</div>
              </div>
              <a-select
                v-model:value="preference.min_priority"
                style="width: 120px"
                @change="updateMinPriority"
              >
                <a-select-option :value="1">低</a-select-option>
                <a-select-option :value="2">较低</a-select-option>
                <a-select-option :value="3">中等</a-select-option>
                <a-select-option :value="4">较高</a-select-option>
                <a-select-option :value="5">高</a-select-option>
              </a-select>
            </div>
          </div>
        </a-card>

        <!-- 操作按钮 -->
        <div class="settings-actions">
          <a-button type="primary" @click="saveAllSettings" :loading="saving">
            保存设置
          </a-button>
          <a-button @click="resetAllSettings" :loading="resetting">
            重置所有设置
          </a-button>
        </div>
      </div>
    </a-spin>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePreferenceStore } from '../store/preference'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import type { Dayjs } from 'dayjs'

const preferenceStore = usePreferenceStore()
const saving = ref(false)
const resetting = ref(false)

// 计算属性
const preference = computed(() => preferenceStore.preference)

// 免打扰时间
const dndStartTime = ref<Dayjs>()
const dndEndTime = ref<Dayjs>()

// 渠道描述
const getChannelDescription = (channel: string) => {
  const descriptions: Record<string, string> = {
    socketio: '实时网页通知，即时接收消息',
    websocket: '实时网页通知（备用方式）',
    email: '发送邮件通知到您的邮箱',
    sms: '发送短信通知到您的手机',
    push: '推送通知到您的设备'
  }
  return descriptions[channel] || ''
}

// 消息类型描述
const getTypeDescription = (type: string) => {
  const descriptions: Record<string, string> = {
    system: '系统运行状态、错误提醒等重要消息',
    announcement: '平台公告、活动通知等信息',
    alert: '紧急警报、安全提醒等重要通知',
    reminder: '任务提醒、截止日期等提醒消息',
    marketing: '产品推广、营销活动等商业消息'
  }
  return descriptions[type] || ''
}

// 更新渠道偏好
const updateChannelPreference = async (channel: string, enabled: boolean) => {
  try {
    await preferenceStore.actions.updateChannelPreferences({
      [channel]: enabled
    })
    message.success('设置已更新')
  } catch (error) {
    message.error('更新失败')
    // 回滚状态
    if (preference.value) {
      preference.value.channel_preferences[channel as keyof typeof preference.value.channel_preferences] = !enabled
    }
  }
}

// 更新消息类型偏好
const updateTypePreference = async (type: string, enabled: boolean) => {
  try {
    await preferenceStore.actions.updateTypePreferences({
      [type]: enabled
    })
    message.success('设置已更新')
  } catch (error) {
    message.error('更新失败')
    // 回滚状态
    if (preference.value) {
      preference.value.type_preferences[type as keyof typeof preference.value.type_preferences] = !enabled
    }
  }
}

// 更新免打扰启用状态
const updateDoNotDisturbEnabled = async (enabled: boolean) => {
  try {
    await preferenceStore.actions.toggleDoNotDisturb(enabled)
    message.success('设置已更新')
  } catch (error) {
    message.error('更新失败')
    // 回滚状态
    if (preference.value) {
      preference.value.do_not_disturb_enabled = !enabled
    }
  }
}

// 更新免打扰时间
const updateDoNotDisturbTime = async () => {
  if (!dndStartTime.value || !dndEndTime.value || !preference.value) return
  
  const startTime = dndStartTime.value.format('HH:mm:ss')
  const endTime = dndEndTime.value.format('HH:mm:ss')
  
  try {
    await preferenceStore.actions.setDoNotDisturbTime(
      startTime,
      endTime,
      preference.value.do_not_disturb_enabled
    )
    message.success('免打扰时间已更新')
  } catch (error) {
    message.error('更新失败')
  }
}

// 更新最小优先级
const updateMinPriority = async (priority: number) => {
  try {
    await preferenceStore.actions.setMinPriority(priority)
    message.success('优先级设置已更新')
  } catch (error) {
    message.error('更新失败')
  }
}

// 重置渠道偏好
const resetChannelPreferences = async () => {
  if (!preferenceStore.defaults) return
  
  try {
    await preferenceStore.actions.updateChannelPreferences(
      preferenceStore.defaults.channel_preferences
    )
    message.success('渠道设置已重置')
  } catch (error) {
    message.error('重置失败')
  }
}

// 重置消息类型偏好
const resetTypePreferences = async () => {
  if (!preferenceStore.defaults) return
  
  try {
    await preferenceStore.actions.updateTypePreferences(
      preferenceStore.defaults.type_preferences
    )
    message.success('消息类型设置已重置')
  } catch (error) {
    message.error('重置失败')
  }
}

// 保存所有设置
const saveAllSettings = async () => {
  if (!preference.value) return
  
  saving.value = true
  try {
    await preferenceStore.actions.update(preference.value)
    message.success('所有设置已保存')
  } catch (error) {
    message.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 重置所有设置
const resetAllSettings = async () => {
  resetting.value = true
  try {
    await preferenceStore.actions.reset()
    message.success('所有设置已重置为默认值')
    initTimeValues()
  } catch (error) {
    message.error('重置失败')
  } finally {
    resetting.value = false
  }
}

// 初始化时间值
const initTimeValues = () => {
  if (preference.value) {
    dndStartTime.value = dayjs(preference.value.do_not_disturb_start, 'HH:mm:ss')
    dndEndTime.value = dayjs(preference.value.do_not_disturb_end, 'HH:mm:ss')
  }
}

// 初始化
onMounted(async () => {
  await preferenceStore.init()
  initTimeValues()
})
</script>

<style scoped>
.notification-settings {
  padding: 24px;
  background: #f5f5f5;
  min-height: 100vh;
}

.settings-header {
  margin-bottom: 24px;
}

.settings-header h2 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
}

.settings-desc {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.settings-content {
  max-width: 800px;
}

.settings-card {
  margin-bottom: 24px;
}

.channel-settings,
.type-settings {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.channel-item,
.type-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #fafafa;
  border-radius: 6px;
}

.channel-info,
.type-info {
  flex: 1;
}

.channel-name,
.type-name {
  font-weight: 500;
  margin-bottom: 4px;
}

.channel-desc,
.type-desc {
  font-size: 12px;
  color: #666;
}

.dnd-settings {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #fafafa;
  border-radius: 6px;
}

.setting-info {
  flex: 1;
}

.setting-name {
  font-weight: 500;
  margin-bottom: 4px;
}

.setting-desc {
  font-size: 12px;
  color: #666;
}

.dnd-time {
  padding: 16px;
  background: #fafafa;
  border-radius: 6px;
}

.time-setting {
  margin-bottom: 16px;
}

.time-setting label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
}

.dnd-status {
  margin-top: 16px;
}

.priority-settings {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.settings-actions {
  display: flex;
  gap: 12px;
  padding: 24px 0;
}
</style>