<template>
  <div class="notification-settings">
    <div class="settings-header">
      <h2>通知设置</h2>
      <p class="settings-desc">管理您的消息通知偏好设置</p>
    </div>

    <el-skeleton :loading="preferenceStore.loading" animated :rows="10">
      <div class="settings-content" v-if="preference">
        <!-- 通知渠道设置 -->
        <el-card class="settings-card" shadow="never">
          <template #header>
            <div class="card-header">
              <span>通知渠道</span>
              <el-button size="small" @click="resetChannelPreferences">
                重置默认
              </el-button>
            </div>
          </template>
          
          <div class="channel-settings">
            <div class="channel-item" v-for="(enabled, channel) in preference.channel_preferences" :key="channel">
              <div class="channel-info">
                <div class="channel-name">
                  {{ preferenceStore.utils.getChannelDisplayName(channel) }}
                </div>
                <div class="channel-desc">{{ getChannelDescription(channel) }}</div>
              </div>
              <el-switch 
                v-model="preference.channel_preferences[channel]"
                @change="(val: boolean) => updateChannelPreference(channel, val)"
              />
            </div>
          </div>
        </el-card>

        <!-- 消息类型设置 -->
        <el-card class="settings-card" shadow="never">
          <template #header>
            <div class="card-header">
              <span>消息类型</span>
              <el-button size="small" @click="resetTypePreferences">
                重置默认
              </el-button>
            </div>
          </template>
          
          <div class="type-settings">
            <div class="type-item" v-for="(enabled, type) in preference.type_preferences" :key="type">
              <div class="type-info">
                <div class="type-name">
                  {{ preferenceStore.utils.getTypeDisplayName(type) }}
                </div>
                <div class="type-desc">{{ getTypeDescription(type) }}</div>
              </div>
              <el-switch 
                v-model="preference.type_preferences[type]"
                @change="(val: boolean) => updateTypePreference(type, val)"
              />
            </div>
          </div>
        </el-card>

        <!-- 免打扰设置 -->
        <el-card class="settings-card" shadow="never">
          <template #header>
            <span>免打扰时间</span>
          </template>
          <div class="dnd-settings">
            <div class="dnd-enable">
              <div class="setting-item">
                <div class="setting-info">
                  <div class="setting-name">启用免打扰</div>
                  <div class="setting-desc">在指定时间段内不接收通知</div>
                </div>
                <el-switch 
                  v-model="preference.do_not_disturb_enabled"
                  @change="updateDoNotDisturbEnabled"
                />
              </div>
            </div>
            
            <div class="dnd-time" v-if="preference.do_not_disturb_enabled">
              <el-row :gutter="16">
                <el-col :span="12">
                  <div class="time-setting">
                    <label>开始时间</label>
                    <el-time-picker
                      v-model="dndStartTime"
                      format="HH:mm"
                      @change="updateDoNotDisturbTime"
                      style="width: 100%"
                    />
                  </div>
                </el-col>
                <el-col :span="12">
                  <div class="time-setting">
                    <label>结束时间</label>
                    <el-time-picker
                      v-model="dndEndTime"
                      format="HH:mm"
                      @change="updateDoNotDisturbTime"
                      style="width: 100%"
                    />
                  </div>
                </el-col>
              </el-row>
              
              <div class="dnd-status" v-if="preferenceStore.isDoNotDisturbActive">
                <el-alert
                  title="免打扰模式已激活"
                  description="当前时间在免打扰时间段内，您不会收到通知"
                  type="info"
                  show-icon
                  :closable="false"
                />
              </div>
            </div>
          </div>
        </el-card>

        <!-- 优先级过滤 -->
        <el-card class="settings-card" shadow="never">
          <template #header>
            <span>优先级过滤</span>
          </template>
          <div class="priority-settings">
            <div class="setting-item">
              <div class="setting-info">
                <div class="setting-name">最小优先级</div>
                <div class="setting-desc">只接收指定优先级及以上的消息通知</div>
              </div>
              <el-select
                v-model="preference.min_priority"
                style="width: 120px"
                @change="updateMinPriority"
              >
                <el-option :value="1" label="低" />
                <el-option :value="2" label="较低" />
                <el-option :value="3" label="中等" />
                <el-option :value="4" label="较高" />
                <el-option :value="5" label="高" />
              </el-select>
            </div>
          </div>
        </el-card>

        <!-- 操作按钮 -->
        <div class="settings-actions">
          <el-button type="primary" @click="saveAllSettings" :loading="saving">
            保存设置
          </el-button>
          <el-button @click="resetAllSettings" :loading="resetting">
            重置所有设置
          </el-button>
        </div>
      </div>
    </el-skeleton>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePreferenceStore } from '../store/preference'
import { ElMessage } from 'element-plus'
import dayjs from 'dayjs'
import type { Dayjs } from 'dayjs'

const preferenceStore = usePreferenceStore()
const saving = ref(false)
const resetting = ref(false)

// 计算属性
const preference = computed(() => preferenceStore.preference || preferenceStore.defaults)

// 免打扰时间
const dndStartTime = ref<Date>()
const dndEndTime = ref<Date>()

// 渠道描述
const getChannelDescription = (channel: string) => {
  const descriptions: Record<string, string> = {
    database: '站内信通知，登录后可查看',
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
    ElMessage.success('设置已更新')
  } catch (error) {
    ElMessage.error('更新失败')
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
    ElMessage.success('设置已更新')
  } catch (error) {
    ElMessage.error('更新失败')
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
    ElMessage.success('设置已更新')
  } catch (error) {
    ElMessage.error('更新失败')
    // 回滚状态
    if (preference.value) {
      preference.value.do_not_disturb_enabled = !enabled
    }
  }
}

// 更新免打扰时间
const updateDoNotDisturbTime = async () => {
  if (!dndStartTime.value || !dndEndTime.value || !preference.value) return
  
  const startTime = dayjs(dndStartTime.value).format('HH:mm:ss')
  const endTime = dayjs(dndEndTime.value).format('HH:mm:ss')
  
  try {
    await preferenceStore.actions.setDoNotDisturbTime(
      startTime,
      endTime,
      preference.value.do_not_disturb_enabled
    )
    ElMessage.success('免打扰时间已更新')
  } catch (error) {
    ElMessage.error('更新失败')
  }
}

// 更新最小优先级
const updateMinPriority = async (priority: number) => {
  try {
    await preferenceStore.actions.setMinPriority(priority)
    ElMessage.success('优先级设置已更新')
  } catch (error) {
    ElMessage.error('更新失败')
  }
}

// 重置渠道偏好
const resetChannelPreferences = async () => {
  if (!preferenceStore.defaults) return
  
  try {
    await preferenceStore.actions.updateChannelPreferences(
      preferenceStore.defaults.channel_preferences
    )
    ElMessage.success('渠道设置已重置')
  } catch (error) {
    ElMessage.error('重置失败')
  }
}

// 重置消息类型偏好
const resetTypePreferences = async () => {
  if (!preferenceStore.defaults) return
  
  try {
    await preferenceStore.actions.updateTypePreferences(
      preferenceStore.defaults.type_preferences
    )
    ElMessage.success('消息类型设置已重置')
  } catch (error) {
    ElMessage.error('重置失败')
  }
}

// 保存所有设置
const saveAllSettings = async () => {
  if (!preference.value) return
  
  saving.value = true
  try {
    await preferenceStore.actions.update(preference.value)
    ElMessage.success('所有设置已保存')
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 重置所有设置
const resetAllSettings = async () => {
  resetting.value = true
  try {
    await preferenceStore.actions.reset()
    ElMessage.success('所有设置已重置为默认值')
    initTimeValues()
  } catch (error) {
    ElMessage.error('重置失败')
  } finally {
    resetting.value = false
  }
}

// 初始化时间值
const initTimeValues = () => {
  if (preference.value) {
    dndStartTime.value = dayjs(preference.value.do_not_disturb_start, 'HH:mm:ss').toDate()
    dndEndTime.value = dayjs(preference.value.do_not_disturb_end, 'HH:mm:ss').toDate()
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
  background: var(--el-bg-color-page);
  min-height: 100%;
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
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.settings-content {
  max-width: 100%;
}

.settings-card {
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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
  background: var(--el-fill-color-light);
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
  color: var(--el-text-color-secondary);
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
  background: var(--el-fill-color-light);
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
  color: var(--el-text-color-secondary);
}

.dnd-time {
  padding: 16px;
  background: var(--el-fill-color-light);
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
