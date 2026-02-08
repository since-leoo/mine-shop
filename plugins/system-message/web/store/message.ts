/**
 * 消息状态管理
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Message, UserMessage, MessageListParams, UserMessageListParams } from '../api/message'
import { messageAdminApi, messageUserApi } from '../api/message'

export const useMessageStore = defineStore('system-message', () => {
  // 状态
  const messages = ref<Message[]>([])
  const userMessages = ref<UserMessage[]>([])
  const currentMessage = ref<Message | null>(null)
  const currentUserMessage = ref<UserMessage | null>(null)
  const loading = ref(false)
  const unreadCount = ref(0)
  const total = ref(0)
  const currentPage = ref(1)
  const pageSize = ref(20)

  // 计算属性
  const hasUnreadMessages = computed(() => unreadCount.value > 0)
  const totalPages = computed(() => Math.ceil(total.value / pageSize.value))

  // 管理端消息操作
  const adminActions = {
    // 获取消息列表
    async getList(params: MessageListParams = {}) {
      loading.value = true
      try {
        const response = await messageAdminApi.getList({
          page: currentPage.value,
          page_size: pageSize.value,
          ...params
        })
        
        messages.value = response.data.data
        total.value = response.data.total
        currentPage.value = response.data.page
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 获取消息详情
    async getDetail(id: number) {
      loading.value = true
      try {
        const response = await messageAdminApi.getDetail(id)
        currentMessage.value = response.data
        return response
      } finally {
        loading.value = false
      }
    },

    // 创建消息
    async create(data: any) {
      loading.value = true
      try {
        const response = await messageAdminApi.create(data)
        // 刷新列表
        await adminActions.getList()
        return response
      } finally {
        loading.value = false
      }
    },

    // 更新消息
    async update(id: number, data: any) {
      loading.value = true
      try {
        const response = await messageAdminApi.update(id, data)
        // 更新当前消息
        if (currentMessage.value?.id === id) {
          currentMessage.value = response.data
        }
        // 更新列表中的消息
        const index = messages.value.findIndex(msg => msg.id === id)
        if (index !== -1) {
          messages.value[index] = response.data
        }
        return response
      } finally {
        loading.value = false
      }
    },

    // 删除消息
    async delete(id: number) {
      loading.value = true
      try {
        const response = await messageAdminApi.delete(id)
        // 从列表中移除
        messages.value = messages.value.filter(msg => msg.id !== id)
        total.value--
        return response
      } finally {
        loading.value = false
      }
    },

    // 发送消息
    async send(id: number) {
      loading.value = true
      try {
        const response = await messageAdminApi.send(id)
        // 更新消息状态
        const message = messages.value.find(msg => msg.id === id)
        if (message) {
          message.status = 'sent'
        }
        if (currentMessage.value?.id === id) {
          currentMessage.value.status = 'sent'
        }
        return response
      } finally {
        loading.value = false
      }
    },

    // 批量删除
    async batchDelete(ids: number[]) {
      loading.value = true
      try {
        const response = await messageAdminApi.batchDelete(ids)
        // 从列表中移除
        messages.value = messages.value.filter(msg => !ids.includes(msg.id))
        total.value -= ids.length
        return response
      } finally {
        loading.value = false
      }
    },

    // 搜索消息
    async search(keyword: string, params: any = {}) {
      loading.value = true
      try {
        const response = await messageAdminApi.search(keyword, {
          page: currentPage.value,
          page_size: pageSize.value,
          ...params
        })
        
        messages.value = response.data.data
        total.value = response.data.total
        currentPage.value = response.data.page
        
        return response
      } finally {
        loading.value = false
      }
    }
  }

  // 用户端消息操作
  const userActions = {
    // 获取用户消息列表
    async getList(params: UserMessageListParams = {}) {
      loading.value = true
      try {
        const response = await messageUserApi.getList({
          page: currentPage.value,
          page_size: pageSize.value,
          ...params
        })
        
        userMessages.value = response.data.data
        total.value = response.data.total
        currentPage.value = response.data.page
        
        return response
      } finally {
        loading.value = false
      }
    },

    // 获取消息详情
    async getDetail(messageId: number) {
      loading.value = true
      try {
        const response = await messageUserApi.getDetail(messageId)
        currentUserMessage.value = response.data
        return response
      } finally {
        loading.value = false
      }
    },

    // 标记消息为已读
    async markAsRead(messageId: number) {
      try {
        const response = await messageUserApi.markAsRead(messageId)
        
        // 更新本地状态
        const userMessage = userMessages.value.find(msg => msg.message_id === messageId)
        if (userMessage && !userMessage.is_read) {
          userMessage.is_read = true
          userMessage.read_at = new Date().toISOString()
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        
        if (currentUserMessage.value?.message_id === messageId) {
          currentUserMessage.value.is_read = true
          currentUserMessage.value.read_at = new Date().toISOString()
        }
        
        return response
      } catch (error) {
        console.error('Failed to mark message as read:', error)
        throw error
      }
    },

    // 批量标记为已读
    async batchMarkAsRead(messageIds: number[]) {
      try {
        const response = await messageUserApi.batchMarkAsRead(messageIds)
        
        // 更新本地状态
        let markedCount = 0
        userMessages.value.forEach(userMessage => {
          if (messageIds.includes(userMessage.message_id) && !userMessage.is_read) {
            userMessage.is_read = true
            userMessage.read_at = new Date().toISOString()
            markedCount++
          }
        })
        
        unreadCount.value = Math.max(0, unreadCount.value - markedCount)
        return response
      } catch (error) {
        console.error('Failed to batch mark messages as read:', error)
        throw error
      }
    },

    // 标记所有消息为已读
    async markAllAsRead() {
      try {
        const response = await messageUserApi.markAllAsRead()
        
        // 更新本地状态
        userMessages.value.forEach(userMessage => {
          if (!userMessage.is_read) {
            userMessage.is_read = true
            userMessage.read_at = new Date().toISOString()
          }
        })
        
        unreadCount.value = 0
        return response
      } catch (error) {
        console.error('Failed to mark all messages as read:', error)
        throw error
      }
    },

    // 删除用户消息
    async delete(messageId: number) {
      try {
        const response = await messageUserApi.delete(messageId)
        
        // 从列表中移除
        const index = userMessages.value.findIndex(msg => msg.message_id === messageId)
        if (index !== -1) {
          const userMessage = userMessages.value[index]
          if (!userMessage.is_read) {
            unreadCount.value = Math.max(0, unreadCount.value - 1)
          }
          userMessages.value.splice(index, 1)
          total.value--
        }
        
        return response
      } catch (error) {
        console.error('Failed to delete user message:', error)
        throw error
      }
    },

    // 批量删除
    async batchDelete(messageIds: number[]) {
      try {
        const response = await messageUserApi.batchDelete(messageIds)
        
        // 从列表中移除
        let deletedUnreadCount = 0
        userMessages.value = userMessages.value.filter(userMessage => {
          if (messageIds.includes(userMessage.message_id)) {
            if (!userMessage.is_read) {
              deletedUnreadCount++
            }
            return false
          }
          return true
        })
        
        unreadCount.value = Math.max(0, unreadCount.value - deletedUnreadCount)
        total.value -= messageIds.length
        
        return response
      } catch (error) {
        console.error('Failed to batch delete user messages:', error)
        throw error
      }
    },

    // 获取未读消息数量
    async getUnreadCount() {
      try {
        const response = await messageUserApi.getUnreadCount()
        unreadCount.value = response.data.count
        return response
      } catch (error) {
        console.error('Failed to get unread count:', error)
        throw error
      }
    },

    // 搜索用户消息
    async search(keyword: string, params: any = {}) {
      loading.value = true
      try {
        const response = await messageUserApi.search(keyword, {
          page: currentPage.value,
          page_size: pageSize.value,
          ...params
        })
        
        userMessages.value = response.data.data
        total.value = response.data.total
        currentPage.value = response.data.page
        
        return response
      } finally {
        loading.value = false
      }
    }
  }

  // 通用操作
  const setPage = (page: number) => {
    currentPage.value = page
  }

  const setPageSize = (size: number) => {
    pageSize.value = size
    currentPage.value = 1
  }

  const clearCurrentMessage = () => {
    currentMessage.value = null
    currentUserMessage.value = null
  }

  const reset = () => {
    messages.value = []
    userMessages.value = []
    currentMessage.value = null
    currentUserMessage.value = null
    loading.value = false
    unreadCount.value = 0
    total.value = 0
    currentPage.value = 1
  }

  return {
    // 状态
    messages,
    userMessages,
    currentMessage,
    currentUserMessage,
    loading,
    unreadCount,
    total,
    currentPage,
    pageSize,
    
    // 计算属性
    hasUnreadMessages,
    totalPages,
    
    // 操作
    adminActions,
    userActions,
    setPage,
    setPageSize,
    clearCurrentMessage,
    reset
  }
})