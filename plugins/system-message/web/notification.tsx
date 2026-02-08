/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import { useMessageStore } from '@/plugins/since/system-message/store/message'
import type { UserMessage } from '@/plugins/since/system-message/api/message'
import dayjs from 'dayjs'

export default defineComponent({
  name: 'notification',
  setup() {
    const router = useRouter()
    const selected = ref<string>('message')
    const loading = ref(false)
    const messages = ref<UserMessage[]>([])
    
    // 尝试获取消息 store
    let messageStore: ReturnType<typeof useMessageStore> | null = null
    try {
      messageStore = useMessageStore()
    } catch (e) {
      console.warn('System message plugin not available')
    }

    // 未读消息数量
    const unreadCount = computed(() => messageStore?.unreadCount ?? 0)

    // 格式化时间
    const formatTime = (time: string) => {
      const now = dayjs()
      const msgTime = dayjs(time)
      const diffMinutes = now.diff(msgTime, 'minute')
      
      if (diffMinutes < 1) {
        return '刚刚'
      } else if (diffMinutes < 60) {
        return `${diffMinutes}分钟前`
      } else if (diffMinutes < 1440) {
        return `${Math.floor(diffMinutes / 60)}小时前`
      } else {
        return msgTime.format('MM-DD HH:mm')
      }
    }

    // 获取消息预览
    const getMessagePreview = (content: string, maxLength = 40) => {
      // 移除 HTML 标签
      const text = content.replace(/<[^>]*>/g, '')
      if (text.length <= maxLength) {
        return text
      }
      return text.substring(0, maxLength) + '...'
    }

    // 加载消息列表
    const loadMessages = async () => {
      if (!messageStore) return
      
      loading.value = true
      try {
        const response = await messageStore.userActions.getList({
          page: 1,
          page_size: 10
        })
        messages.value = response.data.data || []
      } catch (error) {
        console.error('Failed to load messages:', error)
        messages.value = []
      } finally {
        loading.value = false
      }
    }

    // 获取未读数量
    const refreshUnreadCount = async () => {
      if (!messageStore) return
      try {
        await messageStore.userActions.getUnreadCount()
      } catch (error) {
        console.error('Failed to get unread count:', error)
      }
    }

    // 标记消息为已读
    const markAsRead = async (msg: UserMessage) => {
      if (!messageStore || msg.is_read) return
      try {
        await messageStore.userActions.markAsRead(msg.message_id)
        // 更新本地状态
        const index = messages.value.findIndex(m => m.id === msg.id)
        if (index !== -1) {
          messages.value[index].is_read = true
        }
      } catch (error) {
        console.error('Failed to mark as read:', error)
      }
    }

    // 标记所有为已读
    const markAllAsRead = async () => {
      if (!messageStore) return
      try {
        await messageStore.userActions.markAllAsRead()
        // 更新本地状态
        messages.value.forEach(msg => {
          msg.is_read = true
        })
      } catch (error) {
        console.error('Failed to mark all as read:', error)
      }
    }

    // 查看消息详情
    const viewMessage = (msg: UserMessage) => {
      markAsRead(msg)
      router.push(`/user/system-message/detail/${msg.message_id}`)
    }

    // 跳转到消息列表
    const goToList = () => {
      router.push('/user/system-message/list')
    }

    // 初始化
    onMounted(() => {
      refreshUnreadCount()
      loadMessages()
      
      // 定时刷新未读数量
      const timer = setInterval(() => {
        refreshUnreadCount()
      }, 30000)
      
      onUnmounted(() => {
        clearInterval(timer)
      })
    })

    return () => (
      <div class="hidden lg:block">
        <m-dropdown
          class="max-w-[300px] min-w-[300px]"
          triggers={['click']}
          style="position: relative; top: 3px"
          onOpen={loadMessages}
          v-slots={{
            default: () => (
              <div class="relative">
                <ma-svg-icon
                  className="tool-icon"
                  name="heroicons:bell"
                  size={20}
                />
                {unreadCount.value > 0 && (
                  <div class="absolute -right-1 -top-1 h-4 min-w-4 flex items-center justify-center rounded-full bg-red-5 px-1 text-[10px] text-white">
                    {unreadCount.value > 99 ? '99+' : unreadCount.value}
                  </div>
                )}
              </div>
            ),
            popper: () => (
              <div>
                <m-tabs
                  v-model={selected.value}
                  options={[
                    { icon: 'i-ph:chat-circle-text', label: useTrans('mineAdmin.notification.message'), value: 'message' },
                    { icon: 'i-ic:baseline-notifications-none', label: useTrans('mineAdmin.notification.notice'), value: 'notice' },
                    { icon: 'i-pajamas:todo-done', label: useTrans('mineAdmin.notification.todo'), value: 'todo' },
                  ]}
                />
                <div class="notification-box">
                  {selected.value === 'message' && (
                    <ul class="message-box">
                      {loading.value ? (
                        <li class="flex items-center justify-center py-8">
                          <span class="text-gray-4">加载中...</span>
                        </li>
                      ) : messages.value.length === 0 ? (
                        <li class="flex items-center justify-center py-8">
                          <span class="text-gray-4">暂无消息</span>
                        </li>
                      ) : (
                        messages.value.map(msg => (
                          <li 
                            key={msg.id} 
                            onClick={() => viewMessage(msg)}
                            class={{
                              'cursor-pointer': true,
                              'bg-blue-50 dark:bg-blue-900/20': !msg.is_read
                            }}
                          >
                            <div class="w-2/12 flex items-start justify-center">
                              <div class={{
                                'h-8 w-8 rounded-full flex items-center justify-center text-white text-sm': true,
                                'bg-blue-5': msg.message?.type === 'system',
                                'bg-green-5': msg.message?.type === 'announcement',
                                'bg-red-5': msg.message?.type === 'alert',
                                'bg-orange-5': msg.message?.type === 'reminder',
                                'bg-purple-5': msg.message?.type === 'marketing',
                                'bg-gray-5': !msg.message?.type
                              }}>
                                {msg.message?.type === 'system' && '系'}
                                {msg.message?.type === 'announcement' && '公'}
                                {msg.message?.type === 'alert' && '警'}
                                {msg.message?.type === 'reminder' && '提'}
                                {msg.message?.type === 'marketing' && '营'}
                                {!msg.message?.type && '消'}
                              </div>
                            </div>
                            <div class="w-10/12">
                              <div class="flex items-center justify-between">
                                <span class={{
                                  'font-medium': !msg.is_read,
                                  'text-[rgb(var(--ui-primary))]': !msg.is_read
                                }}>
                                  {msg.message?.title || '无标题'}
                                </span>
                                {!msg.is_read && (
                                  <span class="h-2 w-2 rounded-full bg-red-5"></span>
                                )}
                              </div>
                              <div class="mt-1 truncate text-gray-5 dark:text-gray-4">
                                {getMessagePreview(msg.message?.content || '')}
                              </div>
                              <div class="mt-1 text-xs text-gray-4">
                                {formatTime(msg.created_at)}
                              </div>
                            </div>
                          </li>
                        ))
                      )}
                    </ul>
                  )}
                  {selected.value === 'notice' && (
                    <ul class="notice-box">
                      {loading.value ? (
                        <li class="flex items-center justify-center py-8">
                          <span class="text-gray-4">加载中...</span>
                        </li>
                      ) : messages.value.filter(m => m.message?.type === 'announcement').length === 0 ? (
                        <li class="flex items-center justify-center py-8">
                          <span class="text-gray-4">暂无公告</span>
                        </li>
                      ) : (
                        messages.value
                          .filter(m => m.message?.type === 'announcement')
                          .map(msg => (
                            <li key={msg.id} onClick={() => viewMessage(msg)} class="cursor-pointer">
                              <div class="flex items-center justify-between">
                                <span class="w-8/12 truncate">{msg.message?.title}</span>
                                <span class="text-gray-5">{dayjs(msg.created_at).format('YYYY-MM-DD')}</span>
                              </div>
                              <div>
                                <div class="mt-2 truncate text-gray-5 dark:text-gray-4">
                                  {getMessagePreview(msg.message?.content || '')}
                                </div>
                              </div>
                            </li>
                          ))
                      )}
                    </ul>
                  )}
                  {selected.value === 'todo' && (
                    <ul class="todo-box">
                      {loading.value ? (
                        <li class="flex items-center justify-center py-8">
                          <span class="text-gray-4">加载中...</span>
                        </li>
                      ) : messages.value.filter(m => m.message?.type === 'reminder').length === 0 ? (
                        <li class="flex items-center justify-center py-8">
                          <span class="text-gray-4">暂无待办</span>
                        </li>
                      ) : (
                        messages.value
                          .filter(m => m.message?.type === 'reminder')
                          .map(msg => (
                            <li key={msg.id} onClick={() => viewMessage(msg)} class="cursor-pointer">
                              <div class="flex items-center justify-between">
                                <span class="w-9/12 truncate">{msg.message?.title}</span>
                                <span class={{
                                  'block rounded p-1 px-2 text-[12px]': true,
                                  'bg-blue-1 text-blue-5': !msg.is_read,
                                  'bg-gray-1 text-gray-5': msg.is_read
                                }}>
                                  {msg.is_read ? '已读' : '未读'}
                                </span>
                              </div>
                              <div class="mt-2 truncate text-gray-5 dark:text-gray-4">
                                {getMessagePreview(msg.message?.content || '')}
                              </div>
                            </li>
                          ))
                      )}
                    </ul>
                  )}
                </div>
                <div class="box-footer">
                  <a class="link cursor-pointer" onClick={markAllAsRead}>
                    {useTrans('mineAdmin.notification.allRead')}
                  </a>
                  <a class="link cursor-pointer" onClick={goToList}>
                    {useTrans('mineAdmin.notification.gotoTheList')}
                  </a>
                </div>
              </div>
            ),
          }}
        />
      </div>
    )
  },
})
