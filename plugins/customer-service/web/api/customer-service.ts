import type { PageList, ResponseStruct } from '#/global'
import useHttp from '@/hooks/auto-imports/useHttp'

export type AgentStatus = 'online' | 'busy' | 'away' | 'offline'
export type ConversationStatus = 'queued' | 'active' | 'closed'
export type MessageType = 'text' | 'image' | 'product_card' | 'faq' | 'system'

export interface AgentVo {
  id: number
  name: string
  avatar?: string
  status: AgentStatus
  active_conversation_count?: number
  max_conversations?: number
  enabled?: boolean
  priority?: number
  weight?: number
  schedule_json?: Schedule
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
  reception_state?: ReceptionState
  assigned_agent_id?: number
  category_id?: number
  handoff_reason?: string
  classification?: { path?: string[] }
}

export interface MessageVo {
  id: number
  type: MessageType
  content: string
  sender_type: 'member' | 'agent' | 'system' | 'ai'
  sequence?: number
  client_message_id?: string
  delivery?: 'sending' | 'sent' | 'failed'
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
  category_id?: number
  alternate_questions?: string[]
  publish?: boolean
  published_revision_id?: number | null
  effective_at?: string
  expires_at?: string
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

export interface SocketTicketVo { ticket: string, expires_in: number, gateway_url?: string, socket_path?: string }

export function getSocketTicket(): Promise<ResponseStruct<SocketTicketVo>> {
  return useHttp().post('/admin/customer-service/socket-ticket')
}

export function getQueue(): Promise<ResponseStruct<ConversationVo[]>> {
  return useHttp().get('/admin/customer-service/queue')
}

export function getConversations(params: Record<string, unknown>): Promise<ResponseStruct<PageList<ConversationVo>>> {
  return useHttp().get('/admin/customer-service/conversations', { params })
}

export function getMessages(no: string, params: Record<string, unknown> = {}): Promise<ResponseStruct<MessageVo[]>> {
  return useHttp().get(`/admin/customer-service/conversations/${encodeURIComponent(no)}/messages`, { params })
}

export function sendMessage(no: string, payload: Pick<MessageVo, 'type' | 'content'> & { extra?: MessageVo['extra'], client_message_id: string }): Promise<ResponseStruct<MessageVo>> {
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

export type ReceptionState = 'ai_active' | 'human_waiting' | 'human_assigned' | 'human_active' | 'human_deferred' | 'closed'
export const receptionLabels: Record<ReceptionState, string> = { ai_active: 'AI 接待中', human_waiting: '等待人工', human_assigned: '等待坐席确认', human_active: '人工接待中', human_deferred: '等待服务恢复', closed: '已结束' }
export interface Schedule { timezone?: string, weekly?: Record<string, string[][]>, exceptions?: Record<string, string[][]> }
export interface ConfigurationEntry { id: number, name?: string, enabled?: boolean, [key: string]: any }
export interface Category extends ConfigurationEntry { name: string, parent_id: number, code?: string, depth?: number }
export type Resource = 'categories' | 'keyword_rules' | 'skill_groups' | 'agent_skills' | 'ai_connections' | 'ai_agents'
export interface ConfigurationSnapshot { _next_ids?: Partial<Record<Resource, number>>, settings: Record<string, any>, categories: Category[], keyword_rules: ConfigurationEntry[], skill_groups: ConfigurationEntry[], agent_skills: ConfigurationEntry[], ai_connections: ConfigurationEntry[], ai_agents: ConfigurationEntry[] }
export interface ConfigurationDocument { revision: number, snapshot: ConfigurationSnapshot, published_revision?: number, history?: Array<{ id: number, published: boolean | number, operator_id?: number, created_at?: string }> }
export function getConfiguration(): Promise<ResponseStruct<ConfigurationDocument>> { return useHttp().get('/admin/customer-service/configuration') }
export function saveConfiguration(snapshot: ConfigurationSnapshot, revision: number, publish: boolean): Promise<ResponseStruct<ConfigurationDocument>> { return useHttp().put('/admin/customer-service/configuration', { snapshot, expected_revision: revision, publish }) }
export function rollbackConfiguration(revision: number, expected_revision: number): Promise<ResponseStruct<ConfigurationDocument>> { return useHttp().post('/admin/customer-service/configuration/rollback', { revision, expected_revision }) }
export function simulateRouting(question: string, category_id?: number): Promise<ResponseStruct<Record<string, any>>> { return useHttp().post('/admin/customer-service/configuration/simulate', { question, category_id }) }
export function testAgent(agent_id: number, question: string): Promise<ResponseStruct<Record<string, any>>> { return useHttp().post('/admin/customer-service/configuration/test-agent', { agent_id, question }) }
export function getAiRuns(): Promise<ResponseStruct<Record<string, any>[]>> { return useHttp().get('/admin/customer-service/ai-runs') }
export function configureAgent(agent: AgentVo): Promise<ResponseStruct<AgentVo>> { return useHttp().put(`/admin/customer-service/agents/${agent.id}`, { ...agent, display_name: agent.name }) }
export function getCategories(): Promise<ResponseStruct<Category[]>> { return useHttp().get('/admin/customer-service/categories') }
export function takeoverConversation(no: string): Promise<ResponseStruct<ConversationVo>> { return useHttp().post(`/admin/customer-service/conversations/${encodeURIComponent(no)}/takeover`) }
export function classifyConversation(no: string, category_id: number): Promise<ResponseStruct<ConversationVo>> { return useHttp().post(`/admin/customer-service/conversations/${encodeURIComponent(no)}/category`, { category_id }) }
