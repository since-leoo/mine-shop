/**
 * 消息相关API接口
 */
import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

// 消息类型定义
export interface Message {
  id: number
  title: string
  content: string
  type: string
  priority: number
  sender_id: number
  recipient_type: string
  recipient_ids?: number[]
  channels: string[]
  status: string
  scheduled_at?: string
  template_id?: number
  template_variables?: Record<string, any>
  extra_data?: Record<string, any>
  created_at: string
  updated_at: string
}

export interface UserMessage {
  id: number
  user_id: number
  message_id: number
  is_read: boolean
  is_deleted: boolean
  read_at?: string
  created_at: string
  message: Message
}

export interface MessageListParams {
  page?: number
  page_size?: number
  type?: string
  status?: string
  sender_id?: number
  recipient_type?: string
  priority?: number
  date_from?: string
  date_to?: string
  keyword?: string
}

export interface UserMessageListParams {
  page?: number
  page_size?: number
  is_read?: boolean
  type?: string
  priority?: number
  date_from?: string
  date_to?: string
  keyword?: string
}

export interface CreateMessageData {
  title: string
  content: string
  type: string
  priority?: number
  recipient_type: string
  recipient_ids?: number[]
  channels?: string[]
  scheduled_at?: string
  template_id?: number
  template_variables?: Record<string, any>
  extra_data?: Record<string, any>
}

export interface UpdateMessageData extends Partial<CreateMessageData> {}

// 管理端消息API
export const messageAdminApi = {
  // 获取消息列表
  getList(params: MessageListParams = {}) {
    return http.get('/plugin/admin/system-message/index', { params })
  },

  // 获取消息详情
  getDetail(id: number) {
    return http.get(`/plugin/admin/system-message/read/${id}`)
  },

  // 创建消息
  create(data: CreateMessageData) {
    return http.post('/plugin/admin/system-message/save', data)
  },

  // 更新消息
  update(id: number, data: UpdateMessageData) {
    return http.put(`/plugin/admin/system-message/update/${id}`, data)
  },

  // 删除消息
  delete(id: number) {
    return http.delete('/plugin/admin/system-message/delete', {
      data: { ids: [id] }
    })
  },

  // 发送消息
  send(id: number) {
    return http.post('/plugin/admin/system-message/send', { id })
  },

  // 调度消息
  schedule(id: number, scheduledAt: string) {
    return http.post('/plugin/admin/system-message/schedule', {
      id,
      scheduled_at: scheduledAt
    })
  },

  // 批量删除消息
  batchDelete(ids: number[]) {
    return http.delete('/plugin/admin/system-message/delete', {
      data: { ids }
    })
  },

  // 批量发送消息
  batchSend(ids: number[]) {
    return http.post('/plugin/admin/system-message/batchSend', { ids })
  },

  // 搜索消息
  search(keyword: string, params: Omit<MessageListParams, 'keyword'> = {}) {
    return http.get('/plugin/admin/system-message/search', {
      params: { keyword, ...params }
    })
  },

  // 获取消息统计
  getStatistics() {
    return http.get('/plugin/admin/system-message/statistics')
  },

  // 获取热门消息
  getPopular(limit = 10) {
    return http.get('/plugin/admin/system-message/popular', {
      params: { limit }
    })
  },

  // 获取最近消息
  getRecent(days = 7, limit = 20) {
    return http.get('/plugin/admin/system-message/recent', {
      params: { days, limit }
    })
  }
}

// 用户端消息API
export const messageUserApi = {
  // 获取用户消息列表
  getList(params: UserMessageListParams = {}) {
    return http.get('/admin/system-message/user/index', { params })
  },

  // 获取消息详情
  getDetail(messageId: number) {
    return http.get(`/admin/system-message/user/read/${messageId}`)
  },

  // 标记消息为已读
  markAsRead(messageId: number) {
    return http.put(`/admin/system-message/user/markRead/${messageId}`)
  },

  // 批量标记消息为已读
  batchMarkAsRead(messageIds: number[]) {
    return http.put('/admin/system-message/user/batchMarkRead', {
      message_ids: messageIds
    })
  },

  // 标记所有消息为已读
  markAllAsRead() {
    return http.put('/admin/system-message/user/markAllRead')
  },

  // 删除用户消息
  delete(messageId: number) {
    return http.delete(`/admin/system-message/user/delete/${messageId}`)
  },

  // 批量删除用户消息
  batchDelete(messageIds: number[]) {
    return http.delete('/admin/system-message/user/batchDelete', {
      data: { message_ids: messageIds }
    })
  },

  // 获取未读消息数量
  getUnreadCount() {
    return http.get('/admin/system-message/user/unreadCount')
  },

  // 获取消息类型统计
  getTypeStats() {
    return http.get('/admin/system-message/user/typeStats')
  },

  // 搜索用户消息
  search(keyword: string, params: Omit<UserMessageListParams, 'keyword'> = {}) {
    return http.get('/admin/system-message/user/search', {
      params: { keyword, ...params }
    })
  }
}

// 公共API
export const messagePublicApi = {
  // 获取消息类型列表
  getMessageTypes() {
    return http.get('/admin/system-message/public/message-types')
  },

  // 获取收件人类型列表
  getRecipientTypes() {
    return http.get('/admin/system-message/public/recipient-types')
  }
}
