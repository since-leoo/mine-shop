/**
 * 用户偏好设置相关API接口
 */
import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

// 偏好设置类型定义
export interface NotificationPreference {
  id: number
  user_id: number
  channel_preferences: {
    socketio: boolean
    websocket: boolean
    email: boolean
    sms: boolean
    push: boolean
  }
  type_preferences: {
    system: boolean
    announcement: boolean
    alert: boolean
    reminder: boolean
    marketing: boolean
  }
  do_not_disturb_enabled: boolean
  do_not_disturb_start: string
  do_not_disturb_end: string
  min_priority: number
  created_at: string
  updated_at: string
}

export interface UpdatePreferenceData {
  channel_preferences?: Partial<NotificationPreference['channel_preferences']>
  type_preferences?: Partial<NotificationPreference['type_preferences']>
  do_not_disturb_enabled?: boolean
  do_not_disturb_start?: string
  do_not_disturb_end?: string
  min_priority?: number
}

// 偏好设置API
export const preferenceApi = {
  // 获取用户偏好设置
  get() {
    return http.get('/system-message/preference/index')
  },

  // 更新用户偏好设置
  update(data: UpdatePreferenceData) {
    return http.put('/system-message/preference/update', data)
  },

  // 重置偏好设置为默认值
  reset() {
    return http.post('/system-message/preference/reset')
  },

  // 获取默认偏好设置
  getDefaults() {
    return http.get('/system-message/preference/defaults')
  },

  // 更新渠道偏好设置
  updateChannelPreferences(channels: Partial<NotificationPreference['channel_preferences']>) {
    return http.put('/system-message/preference/updateChannels', {
      channels
    })
  },

  // 更新消息类型偏好设置
  updateTypePreferences(types: Partial<NotificationPreference['type_preferences']>) {
    return http.put('/system-message/preference/updateTypes', {
      types
    })
  },

  // 设置免打扰时间
  setDoNotDisturbTime(startTime: string, endTime: string, enabled = true) {
    return http.put('/system-message/preference/setDoNotDisturbTime', {
      start_time: startTime,
      end_time: endTime,
      enabled
    })
  },

  // 启用/禁用免打扰
  toggleDoNotDisturb(enabled: boolean) {
    return http.put('/system-message/preference/toggleDoNotDisturb', {
      enabled
    })
  },

  // 设置最小优先级
  setMinPriority(priority: number) {
    return http.put('/system-message/preference/setMinPriority', {
      priority
    })
  },

  // 检查是否在免打扰时间内
  checkDoNotDisturb() {
    return http.get('/system-message/preference/checkDoNotDisturb')
  }
}

// 偏好设置工具函数
export const preferenceUtils = {
  // 获取渠道显示名称
  getChannelDisplayName(channel: string): string {
    const names: Record<string, string> = {
      socketio: '实时通知',
      websocket: 'WebSocket',
      email: '邮件通知',
      sms: '短信通知',
      push: '推送通知'
    }
    return names[channel] || channel
  },

  // 获取消息类型显示名称
  getTypeDisplayName(type: string): string {
    const names: Record<string, string> = {
      system: '系统消息',
      announcement: '公告通知',
      alert: '警报消息',
      reminder: '提醒消息',
      marketing: '营销消息'
    }
    return names[type] || type
  },

  // 获取优先级显示名称
  getPriorityDisplayName(priority: number): string {
    const names: Record<number, string> = {
      1: '低优先级',
      2: '较低优先级',
      3: '中等优先级',
      4: '较高优先级',
      5: '高优先级'
    }
    return names[priority] || `优先级 ${priority}`
  },

  // 格式化时间显示
  formatTime(time: string): string {
    return time.substring(0, 5) // HH:mm
  },

  // 检查是否在免打扰时间内
  isInDoNotDisturbTime(startTime: string, endTime: string): boolean {
    const now = new Date()
    const currentTime = now.getHours() * 60 + now.getMinutes()
    
    const [startHour, startMinute] = startTime.split(':').map(Number)
    const [endHour, endMinute] = endTime.split(':').map(Number)
    
    const start = startHour * 60 + startMinute
    const end = endHour * 60 + endMinute
    
    if (start <= end) {
      // 同一天内的时间段
      return currentTime >= start && currentTime <= end
    } else {
      // 跨天的时间段
      return currentTime >= start || currentTime <= end
    }
  },

  // 验证时间格式
  validateTimeFormat(time: string): boolean {
    const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/
    return timeRegex.test(time)
  },

  // 验证偏好设置数据
  validatePreferenceData(data: UpdatePreferenceData): string[] {
    const errors: string[] = []

    // 验证免打扰时间
    if (data.do_not_disturb_start && !this.validateTimeFormat(data.do_not_disturb_start)) {
      errors.push('免打扰开始时间格式不正确')
    }

    if (data.do_not_disturb_end && !this.validateTimeFormat(data.do_not_disturb_end)) {
      errors.push('免打扰结束时间格式不正确')
    }

    // 验证优先级
    if (data.min_priority !== undefined && (data.min_priority < 1 || data.min_priority > 5)) {
      errors.push('最小优先级必须在1-5之间')
    }

    return errors
  }
}