import type { PageList, ResponseStruct } from '#/global'

export type AgentStatus = 'online' | 'busy' | 'away' | 'offline'
export type ConversationStatus = 'queued' | 'active' | 'closed'
export type MessageType = 'text' | 'image' | 'product_card' | 'system'

export interface AgentVo {
  id: number
  name: string
  avatar?: string
  status: AgentStatus
  active_conversation_count?: number
  max_conversations?: number
}

export interface ConversationVo {
  id: number
  no: string
  status: ConversationStatus
  member_name: string
  member_avatar?: string
  member_id?: number
  last_message?: string
  last_message_at?: string
  unread_count?: number
  queue_position?: number
  agent_name?: string
  source?: string
  order_no?: string
}

export interface MessageVo {
  id: number
  type: MessageType
  content: string
  sender_type: 'member' | 'agent' | 'system'
  sender_name?: string
  created_at: string
  extra?: {
    image_url?: string
    product_name?: string
    product_image?: string
    product_price?: string | number
    product_id?: number
  }
}

export interface FaqVo {
  id: number
  question: string
  answer: string
  category_name?: string
  sort?: number
  enabled?: boolean
  updated_at?: string
}

export interface StatisticsVo {
  queued_count: number
  active_count: number
  today_closed_count: number
  average_response_seconds?: number
}

export function getQueue(): Promise<ResponseStruct<ConversationVo[]>> {
  return useHttp().get('/admin/customer-service/queue')
}

export function getConversations(params: Record<string, unknown>): Promise<ResponseStruct<PageList<ConversationVo>>> {
  return useHttp().get('/admin/customer-service/conversations', { params })
}

export function getMessages(no: string): Promise<ResponseStruct<MessageVo[]>> {
  return useHttp().get(`/admin/customer-service/conversations/${no}/messages`)
}

export function sendMessage(no: string, payload: Pick<MessageVo, 'type' | 'content'> & { extra?: MessageVo['extra'] }): Promise<ResponseStruct<MessageVo>> {
  return useHttp().post(`/admin/customer-service/conversations/${no}/messages`, payload)
}

export function acceptConversation(no: string): Promise<ResponseStruct<ConversationVo>> {
  return useHttp().post(`/admin/customer-service/conversations/${no}/accept`)
}

export function transferConversation(no: string, agentId: number): Promise<ResponseStruct<null>> {
  return useHttp().post(`/admin/customer-service/conversations/${no}/transfer`, { agent_id: agentId })
}

export function closeConversation(no: string): Promise<ResponseStruct<null>> {
  return useHttp().post(`/admin/customer-service/conversations/${no}/close`)
}

export function getAgents(): Promise<ResponseStruct<AgentVo[]>> {
  return useHttp().get('/admin/customer-service/agents')
}

export function getCurrentAgent(): Promise<ResponseStruct<AgentVo>> {
  return useHttp().get('/admin/customer-service/agents/me')
}

export function updateAgentStatus(id: number, status: AgentStatus): Promise<ResponseStruct<null>> {
  return useHttp().post(`/admin/customer-service/agents/${id}/status`, { status })
}

export function getStatistics(): Promise<ResponseStruct<StatisticsVo>> {
  return useHttp().get('/admin/customer-service/statistics')
}

export function getFaqs(params: Record<string, unknown> = {}): Promise<ResponseStruct<PageList<FaqVo>>> {
  return useHttp().get('/admin/customer-service/faqs', { params })
}

export function saveFaq(payload: Partial<FaqVo>): Promise<ResponseStruct<FaqVo>> {
  return payload.id
    ? useHttp().put(`/admin/customer-service/faqs/${payload.id}`, payload)
    : useHttp().post('/admin/customer-service/faqs', payload)
}

export function removeFaq(id: number): Promise<ResponseStruct<null>> {
  return useHttp().delete(`/admin/customer-service/faqs/${id}`)
}
