/**
 * 实时通知组合式函数
 */
import { ref, onMounted, onUnmounted } from 'vue'
import { useMessageStore } from '../store/message'
import { usePreferenceStore } from '../store/preference'
import { message, notification } from 'ant-design-vue'
import { io, Socket } from 'socket.io-client'
import type { UserMessage } from '../api/message'

interface NotificationOptions {
  // 是否启用实时通知
  enabled?: boolean
  // Socket.IO 服务器地址
  serverUrl?: string
  // 重连配置
  reconnect?: {
    enabled: boolean
    maxAttempts: number
    delay: number
  }
  // 通知配置
  notification?: {
    showDesktop: boolean
    showInApp: boolean
    sound: boolean
  }
}

interface RealTimeNotification {
  type: 'new_message' | 'message_read' | 'message_deleted'
  data: any
  timestamp: string
}

export function useRealTimeNotifications(options: NotificationOptions = {}) {
  const messageStore = useMessageStore()
  const preferenceStore = usePreferenceStore()
  
  // 默认配置
  const defaultOptions: Required<NotificationOptions> = {
    enabled: true,
    serverUrl: `${window.location.protocol === 'https:' ? 'https:' : 'http:'}//${window.location.host}`,
    reconnect: {
      enabled: true,
      maxAttempts: 5,
      delay: 3000
    },
    notification: {
      showDesktop: true,
      showInApp: true,
      sound: true
    }
  }
  
  const config = { ...defaultOptions, ...options }
  
  // 状态
  const connected = ref(false)
  const connecting = ref(false)
  const error = ref<string | null>(null)
  const reconnectAttempts = ref(0)
  
  // Socket.IO 连接
  let socket: Socket | null = null
  
  // 连接到 Socket.IO 服务器
  const connect = () => {
    if (!config.enabled || connecting.value || connected.value) {
      return
    }
    
    connecting.value = true
    error.value = null
    
    try {
      socket = io(config.serverUrl, {
        path: '/socket.io',
        namespace: '/system-message',
        transports: ['websocket', 'polling'],
        auth: {
          token: getAuthToken()
        },
        reconnection: config.reconnect.enabled,
        reconnectionAttempts: config.reconnect.maxAttempts,
        reconnectionDelay: config.reconnect.delay
      })
      
      socket.on('connect', () => {
        connected.value = true
        connecting.value = false
        reconnectAttempts.value = 0
        error.value = null
        
        console.log('[RealTime] Connected to Socket.IO server')
        
        // 加入用户房间
        socket?.emit('join_user_room', {
          user_id: getCurrentUserId()
        })
      })
      
      socket.on('disconnect', (reason) => {
        connected.value = false
        connecting.value = false
        
        console.log('[RealTime] Socket.IO disconnected:', reason)
      })
      
      socket.on('connect_error', (err) => {
        error.value = 'Socket.IO connection error: ' + err.message
        connecting.value = false
        console.error('[RealTime] Socket.IO error:', err)
      })
      
      socket.on('reconnect', (attemptNumber) => {
        console.log(`[RealTime] Socket.IO reconnected after ${attemptNumber} attempts`)
        reconnectAttempts.value = attemptNumber
      })
      
      socket.on('reconnect_attempt', (attemptNumber) => {
        console.log(`[RealTime] Socket.IO reconnecting... (${attemptNumber}/${config.reconnect.maxAttempts})`)
        reconnectAttempts.value = attemptNumber
      })
      
      socket.on('reconnect_failed', () => {
        error.value = 'Socket.IO reconnection failed'
        console.error('[RealTime] Socket.IO reconnection failed')
      })
      
      // 监听消息事件
      socket.on('new_message', (data: UserMessage) => {
        handleNotification({
          type: 'new_message',
          data,
          timestamp: new Date().toISOString()
        })
      })
      
      socket.on('message_read', (data: { message_id: number; user_id: number }) => {
        handleNotification({
          type: 'message_read',
          data,
          timestamp: new Date().toISOString()
        })
      })
      
      socket.on('message_deleted', (data: { message_id: number; user_id: number }) => {
        handleNotification({
          type: 'message_deleted',
          data,
          timestamp: new Date().toISOString()
        })
      })
      
    } catch (err) {
      connecting.value = false
      error.value = 'Failed to create Socket.IO connection'
      console.error('[RealTime] Connection error:', err)
    }
  }
  
  // 断开连接
  const disconnect = () => {
    if (socket) {
      socket.disconnect()
      socket = null
    }
    
    connected.value = false
    connecting.value = false
    reconnectAttempts.value = 0
  }
  
  // 发送消息
  const sendMessage = (event: string, data: any) => {
    if (socket && connected.value) {
      socket.emit(event, data)
    }
  }
  
  // 处理实时通知
  const handleNotification = async (notification: RealTimeNotification) => {
    console.log('[RealTime] Received notification:', notification)
    
    switch (notification.type) {
      case 'new_message':
        await handleNewMessage(notification.data)
        break
        
      case 'message_read':
        handleMessageRead(notification.data)
        break
        
      case 'message_deleted':
        handleMessageDeleted(notification.data)
        break
        
      default:
        console.warn('[RealTime] Unknown notification type:', notification.type)
    }
  }
  
  // 处理新消息通知
  const handleNewMessage = async (data: UserMessage) => {
    // 更新消息存储
    await messageStore.userActions.getUnreadCount()
    
    // 检查用户偏好设置
    const preference = preferenceStore.preference
    if (!preference) return
    
    // 检查消息类型是否启用
    if (!preference.type_preferences[data.message.type as keyof typeof preference.type_preferences]) {
      return
    }
    
    // 检查优先级过滤
    if (data.message.priority < preference.min_priority) {
      return
    }
    
    // 检查免打扰时间
    if (preferenceStore.isDoNotDisturbActive) {
      return
    }
    
    // 显示应用内通知
    if (config.notification.showInApp) {
      showInAppNotification(data)
    }
    
    // 显示桌面通知
    if (config.notification.showDesktop && 'Notification' in window) {
      showDesktopNotification(data)
    }
    
    // 播放提示音
    if (config.notification.sound) {
      playNotificationSound()
    }
  }
  
  // 处理消息已读通知
  const handleMessageRead = (data: { message_id: number; user_id: number }) => {
    // 更新本地消息状态
    const userMessage = messageStore.userMessages.find((msg: any) => msg.message_id === data.message_id)
    if (userMessage) {
      userMessage.is_read = true
      userMessage.read_at = new Date().toISOString()
      messageStore.unreadCount = Math.max(0, messageStore.unreadCount - 1)
    }
  }
  
  // 处理消息删除通知
  const handleMessageDeleted = (data: { message_id: number; user_id: number }) => {
    // 从本地消息列表中移除
    const index = messageStore.userMessages.findIndex((msg: any) => msg.message_id === data.message_id)
    if (index !== -1) {
      const userMessage = messageStore.userMessages[index]
      if (!userMessage.is_read) {
        messageStore.unreadCount = Math.max(0, messageStore.unreadCount - 1)
      }
      messageStore.userMessages.splice(index, 1)
    }
  }
  
  // 显示应用内通知
  const showInAppNotification = (data: UserMessage) => {
    notification.open({
      message: data.message.title,
      description: getMessagePreview(data.message.content),
      placement: 'topRight',
      duration: 4.5,
      onClick: () => {
        // 跳转到消息详情
        window.location.href = `#/message-center/detail/${data.message_id}`
      }
    })
  }
  
  // 显示桌面通知
  const showDesktopNotification = async (data: UserMessage) => {
    // 检查通知权限
    if (Notification.permission === 'default') {
      const permission = await Notification.requestPermission()
      if (permission !== 'granted') {
        return
      }
    }
    
    if (Notification.permission === 'granted') {
      const notification = new Notification(data.message.title, {
        body: getMessagePreview(data.message.content),
        icon: '/favicon.ico',
        tag: `message-${data.message_id}`,
        requireInteraction: data.message.priority >= 4 // 高优先级消息需要用户交互
      })
      
      notification.onclick = () => {
        window.focus()
        window.location.href = `#/message-center/detail/${data.message_id}`
        notification.close()
      }
      
      // 自动关闭低优先级通知
      if (data.message.priority < 4) {
        setTimeout(() => {
          notification.close()
        }, 5000)
      }
    }
  }
  
  // 播放通知提示音
  const playNotificationSound = () => {
    try {
      const audio = new Audio('/sounds/notification.mp3')
      audio.volume = 0.3
      audio.play().catch(err => {
        console.warn('[RealTime] Failed to play notification sound:', err)
      })
    } catch (err) {
      console.warn('[RealTime] Notification sound not available:', err)
    }
  }
  
  // 获取消息预览
  const getMessagePreview = (content: string, maxLength = 100) => {
    if (content.length <= maxLength) {
      return content
    }
    return content.substring(0, maxLength) + '...'
  }
  
  // 获取认证令牌
  const getAuthToken = () => {
    // 这里应该从用户认证系统获取令牌
    return localStorage.getItem('auth_token') || ''
  }
  
  // 获取当前用户ID
  const getCurrentUserId = () => {
    // 这里应该从用户认证系统获取用户ID
    const userInfo = localStorage.getItem('user_info')
    if (userInfo) {
      try {
        return JSON.parse(userInfo).id
      } catch (err) {
        console.warn('[RealTime] Failed to parse user info:', err)
      }
    }
    return null
  }
  
  // 请求通知权限
  const requestNotificationPermission = async () => {
    if (!('Notification' in window)) {
      message.warning('您的浏览器不支持桌面通知')
      return false
    }
    
    if (Notification.permission === 'granted') {
      return true
    }
    
    if (Notification.permission === 'default') {
      const permission = await Notification.requestPermission()
      return permission === 'granted'
    }
    
    return false
  }
  
  // 生命周期
  onMounted(() => {
    if (config.enabled) {
      connect()
    }
  })
  
  onUnmounted(() => {
    disconnect()
  })
  
  return {
    // 状态
    connected,
    connecting,
    error,
    reconnectAttempts,
    
    // 方法
    connect,
    disconnect,
    sendMessage,
    requestNotificationPermission,
    
    // 配置
    config
  }
}