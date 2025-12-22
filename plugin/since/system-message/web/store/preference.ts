/**
 * 偏好设置状态管理
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { NotificationPreference, UpdatePreferenceData } from '../api/preference'
import { preferenceApi, preferenceUtils } from '../api/preference'

export const usePreferenceStore = defineStore('notification-preference', () => {
  // 状态
  const preference = ref<NotificationPreference | null>(null)
  const loading = ref(false)
  const defaults = ref<NotificationPreference | null>(null)

  // 计算属性
  const isDoNotDisturbActive = computed(() => {
    if (!preference.value?.do_not_disturb_enabled) return false
    
    return preferenceUtils.isInDoNotDisturbTime(
      preference.value.do_not_disturb_start,
      preference.value.do_not_disturb_end
    )
  })

  const enabledChannels = computed(() => {
    if (!preference.value) return []
    
    return Object.entries(preference.value.channel_preferences)
      .filter(([_, enabled]) => enabled)
      .map(([channel]) => channel)
  })

  const enabledTypes = computed(() => {
    if (!preference.value) return []
    
    return Object.entries(preference.value.type_preferences)
      .filter(([_, enabled]) => enabled)
      .map(([type]) => type)
  })

  const channelCount = computed(() => enabledChannels.value.length)
  const typeCount = computed(() => enabledTypes.value.length)

  // 操作
  const actions = {
    // 获取用户偏好设置
    async get() {
      loading.value = true
      try {
        const response = await preferenceApi.get()
        preference.value = response.data
        return response
      } finally {
        loading.value = false
      }
    },

    // 更新用户偏好设置
    async update(data: UpdatePreferenceData) {
      loading.value = true
      try {
        // 验证数据
        const errors = preferenceUtils.validatePreferenceData(data)
        if (errors.length > 0) {
          throw new Error(errors.join('; '))
        }

        const response = await preferenceApi.update(data)
        
        // 更新本地状态
        if (preference.value) {
          Object.assign(preference.value, data)
        }
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 重置偏好设置为默认值
    async reset() {
      loading.value = true
      try {
        const response = await preferenceApi.reset()
        
        // 重新获取偏好设置
        await actions.get()
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 获取默认偏好设置
    async getDefaults() {
      try {
        const response = await preferenceApi.getDefaults()
        defaults.value = response.data
        return response
      } catch (error) {
        console.error('Failed to get default preferences:', error)
        throw error
      }
    },

    // 更新渠道偏好设置
    async updateChannelPreferences(channels: Partial<NotificationPreference['channel_preferences']>) {
      try {
        const response = await preferenceApi.updateChannelPreferences(channels)
        
        // 更新本地状态
        if (preference.value) {
          Object.assign(preference.value.channel_preferences, channels)
        }
        
        return response
      } catch (error) {
        console.error('Failed to update channel preferences:', error)
        throw error
      }
    },

    // 更新消息类型偏好设置
    async updateTypePreferences(types: Partial<NotificationPreference['type_preferences']>) {
      try {
        const response = await preferenceApi.updateTypePreferences(types)
        
        // 更新本地状态
        if (preference.value) {
          Object.assign(preference.value.type_preferences, types)
        }
        
        return response
      } catch (error) {
        console.error('Failed to update type preferences:', error)
        throw error
      }
    },

    // 设置免打扰时间
    async setDoNotDisturbTime(startTime: string, endTime: string, enabled = true) {
      try {
        // 验证时间格式
        if (!preferenceUtils.validateTimeFormat(startTime)) {
          throw new Error('开始时间格式不正确')
        }
        if (!preferenceUtils.validateTimeFormat(endTime)) {
          throw new Error('结束时间格式不正确')
        }

        const response = await preferenceApi.setDoNotDisturbTime(startTime, endTime, enabled)
        
        // 更新本地状态
        if (preference.value) {
          preference.value.do_not_disturb_enabled = enabled
          preference.value.do_not_disturb_start = startTime
          preference.value.do_not_disturb_end = endTime
        }
        
        return response
      } catch (error) {
        console.error('Failed to set do not disturb time:', error)
        throw error
      }
    },

    // 启用/禁用免打扰
    async toggleDoNotDisturb(enabled: boolean) {
      try {
        const response = await preferenceApi.toggleDoNotDisturb(enabled)
        
        // 更新本地状态
        if (preference.value) {
          preference.value.do_not_disturb_enabled = enabled
        }
        
        return response
      } catch (error) {
        console.error('Failed to toggle do not disturb:', error)
        throw error
      }
    },

    // 设置最小优先级
    async setMinPriority(priority: number) {
      try {
        if (priority < 1 || priority > 5) {
          throw new Error('优先级必须在1-5之间')
        }

        const response = await preferenceApi.setMinPriority(priority)
        
        // 更新本地状态
        if (preference.value) {
          preference.value.min_priority = priority
        }
        
        return response
      } catch (error) {
        console.error('Failed to set min priority:', error)
        throw error
      }
    }
  }

  // 工具方法
  const utils = {
    // 检查渠道是否启用
    isChannelEnabled(channel: string): boolean {
      return preference.value?.channel_preferences?.[channel as keyof typeof preference.value.channel_preferences] ?? false
    },

    // 检查消息类型是否启用
    isTypeEnabled(type: string): boolean {
      return preference.value?.type_preferences?.[type as keyof typeof preference.value.type_preferences] ?? false
    },

    // 获取渠道显示名称
    getChannelDisplayName: preferenceUtils.getChannelDisplayName,

    // 获取消息类型显示名称
    getTypeDisplayName: preferenceUtils.getTypeDisplayName,

    // 获取优先级显示名称
    getPriorityDisplayName: preferenceUtils.getPriorityDisplayName,

    // 格式化时间显示
    formatTime: preferenceUtils.formatTime,

    // 检查是否在免打扰时间内
    isInDoNotDisturbTime: preferenceUtils.isInDoNotDisturbTime,

    // 获取免打扰时间描述
    getDoNotDisturbDescription(): string {
      if (!preference.value?.do_not_disturb_enabled) {
        return '未启用'
      }

      const start = utils.formatTime(preference.value.do_not_disturb_start)
      const end = utils.formatTime(preference.value.do_not_disturb_end)
      
      return `${start} - ${end}`
    },

    // 获取偏好设置摘要
    getPreferenceSummary() {
      if (!preference.value) return null

      return {
        channels: {
          enabled: enabledChannels.value,
          count: channelCount.value,
          total: Object.keys(preference.value.channel_preferences).length
        },
        types: {
          enabled: enabledTypes.value,
          count: typeCount.value,
          total: Object.keys(preference.value.type_preferences).length
        },
        doNotDisturb: {
          enabled: preference.value.do_not_disturb_enabled,
          active: isDoNotDisturbActive.value,
          description: utils.getDoNotDisturbDescription()
        },
        minPriority: {
          value: preference.value.min_priority,
          display: utils.getPriorityDisplayName(preference.value.min_priority)
        }
      }
    },

    // 比较偏好设置是否有变化
    hasChanges(newData: UpdatePreferenceData): boolean {
      if (!preference.value) return true

      // 比较渠道偏好
      if (newData.channel_preferences) {
        for (const [channel, enabled] of Object.entries(newData.channel_preferences)) {
          if (preference.value.channel_preferences[channel as keyof typeof preference.value.channel_preferences] !== enabled) {
            return true
          }
        }
      }

      // 比较消息类型偏好
      if (newData.type_preferences) {
        for (const [type, enabled] of Object.entries(newData.type_preferences)) {
          if (preference.value.type_preferences[type as keyof typeof preference.value.type_preferences] !== enabled) {
            return true
          }
        }
      }

      // 比较其他设置
      const fields: (keyof UpdatePreferenceData)[] = [
        'do_not_disturb_enabled',
        'do_not_disturb_start',
        'do_not_disturb_end',
        'min_priority'
      ]

      for (const field of fields) {
        if (newData[field] !== undefined && newData[field] !== preference.value[field]) {
          return true
        }
      }

      return false
    }
  }

  // 初始化
  const init = async () => {
    await Promise.all([
      actions.get(),
      actions.getDefaults()
    ])
  }

  const reset = () => {
    preference.value = null
    loading.value = false
    defaults.value = null
  }

  return {
    // 状态
    preference,
    loading,
    defaults,
    
    // 计算属性
    isDoNotDisturbActive,
    enabledChannels,
    enabledTypes,
    channelCount,
    typeCount,
    
    // 操作
    actions,
    utils,
    init,
    reset
  }
})