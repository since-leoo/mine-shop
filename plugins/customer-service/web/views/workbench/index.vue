<script setup lang="ts">
import type { AgentStatus, AgentVo, Category, ConversationVo, FaqVo, MessageVo, StatisticsVo } from '../../api/customer-service'
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { ArrowDown, ChatDotRound, Check, CircleClose, Connection, DocumentCopy, MoreFilled, Picture, Promotion, Search, Service, ShoppingBag, SwitchButton } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import hasAuth from '@/utils/permission/hasAuth'
import { acceptConversation, classifyConversation, closeConversation, getAgents, getCategories, getConversations, getCurrentAgent, getFaqs, getMessages, getQueue, getSocketTicket, getStatistics, receptionLabels, sendMessage, takeoverConversation, transferConversation, updateAgentStatus } from '../../api/customer-service'
import { categoryPath } from '../configuration/model'
defineOptions({ name: 'customer-service:workbench' })
const loading = ref(false), messageLoading = ref(false), draft = ref(''), search = ref('')
const activeFilter = ref('all'), transferVisible = ref(false), faqVisible = ref(false), connectionLabel = ref('正在连接')
const selected = ref<ConversationVo>(), conversations = ref<ConversationVo[]>([]), queuedConversations = ref<ConversationVo[]>([])
const messages = ref<MessageVo[]>([]), agents = ref<AgentVo[]>([]), categories = ref<Category[]>([]), faqs = ref<FaqVo[]>([])
const statistics = ref<StatisticsVo>({ queued_count: 0, active_count: 0, today_closed_count: 0 })
const currentAgent = ref<AgentVo>({ id: 0, name: '当前坐席', status: 'offline', active_conversation_count: 0, max_conversations: 5 })
const messageScroller = ref<HTMLElement>()
let refreshTimer: ReturnType<typeof setInterval> | undefined, heartbeat: ReturnType<typeof setInterval> | undefined, reconnect: ReturnType<typeof setTimeout> | undefined, authTimeout: ReturnType<typeof setTimeout> | undefined
let socket: WebSocket | undefined, stopped = false, authenticated = false, socketGeneration = 0, selectionGeneration = 0, cursor = 0, syncingGeneration = -1, refreshing = false, lastPong = 0
const handoffReasons: Record<string, string> = { requested: '用户主动转人工', agent_takeover: '人工主动接管', ai_disabled: 'AI 接待已关闭', ai_unavailable: 'AI 暂不可用', classification_conflict: '问题匹配到多个类目', category_requires_human: '该类目由人工接待', round_limit: '达到 AI 接待轮次上限', unsupported_message_type: '需要人工查看图片或商品', outside_business_hours: '当前不在营业时间', queue_timeout: '等待超过排队时限', legacy_client: '客户端版本需要人工接待' }
const statusOptions: Array<{ value: AgentStatus, label: string }> = [{ value: 'online', label: '在线接待' }, { value: 'busy', label: '忙碌中' }, { value: 'away', label: '暂时离开' }, { value: 'offline', label: '离线' }]
function matchesFilter(item: ConversationVo) {
  if (activeFilter.value === 'all') return true
  if (activeFilter.value === 'ai_active') return item.reception_state === 'ai_active'
  if (activeFilter.value === 'queued' && item.reception_state) return ['human_waiting', 'human_assigned', 'human_deferred'].includes(item.reception_state)
  return item.status === activeFilter.value
}
const filteredConversations = computed(() => conversations.value.filter(item => matchesFilter(item) && (!search.value.trim() || `${item.member_name}${item.no}`.toLowerCase().includes(search.value.trim().toLowerCase()))))
const activeLabel = computed(() => selected.value?.reception_state ? receptionLabels[selected.value.reception_state] : selected.value?.status === 'queued' ? '等待接待' : selected.value?.status === 'closed' ? '会话已关闭' : '接待中')
const activeStatusClass = computed(() => selected.value?.status || 'active')
const agentLoad = computed(() => `${currentAgent.value.active_conversation_count || 0}/${currentAgent.value.max_conversations || 5}`)
const canReply = computed(() => hasAuth('customer-service:conversation:reply') && selected.value?.reception_state === 'human_active' && selected.value.assigned_agent_id === currentAgent.value.id)
const canAccept = computed(() => hasAuth('customer-service:conversation:accept') && ['human_waiting', 'human_assigned'].includes(selected.value?.reception_state || '') && (!selected.value?.assigned_agent_id || selected.value.assigned_agent_id === currentAgent.value.id))
const canTakeover = computed(() => hasAuth('customer-service:conversation:accept') && ['ai_active', 'human_deferred'].includes(selected.value?.reception_state || ''))
function apiData<T>(response: { data: T }): T { return response.data }
function errorText(e: any) { return e?.response?.data?.message || e?.response?.data?.msg || e?.message || '操作失败，请重试' }
function normalizeSocketMessage(message: any): MessageVo {
  if (message.type) return message
  const content = message.content_json || message.contentJson || {}
  return { id: Number(message.id || 0), sequence: Number(message.sequence || 0), client_message_id: message.client_message_id || message.clientMessageId, type: message.message_type === 'product' ? 'product_card' : message.message_type || 'text', content: content.text || content.answer || content.title || '', sender_type: message.sender_type || 'system', created_at: message.sent_at || '', extra: { image_url: content.url, product_id: content.product_id, product_name: content.title, product_image: content.image, product_price: content.price } }
}
function merge(incoming: MessageVo) {
  const index = messages.value.findIndex(item => (incoming.id > 0 && item.id === incoming.id) || (incoming.client_message_id && item.client_message_id === incoming.client_message_id))
  if (index < 0) messages.value.push(incoming)
  else messages.value[index] = { ...messages.value[index], ...incoming }
  messages.value.sort((a, b) => (a.sequence || Number.MAX_SAFE_INTEGER) - (b.sequence || Number.MAX_SAFE_INTEGER))
}
function socketUrl(ticket: { gateway_url?: string, socket_path?: string }) { return import.meta.env.VITE_CUSTOMER_SERVICE_SOCKET_URL || `${(ticket.gateway_url || `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}`).replace(/\/$/, '')}${ticket.socket_path || '/customer-service'}` }
function scheduleReconnect() { if (stopped) return; authenticated = false; clearInterval(heartbeat); clearTimeout(authTimeout); connectionLabel.value = '连接中断，正在重连'; clearTimeout(reconnect); reconnect = setTimeout(() => { void connectSocket() }, 2000) }
async function connectSocket() {
  const generation = ++socketGeneration; socket?.close(); authenticated = false; clearInterval(heartbeat); clearTimeout(authTimeout)
  try {
    const socketConfig = apiData(await getSocketTicket()); const ticket = socketConfig.ticket
    if (stopped || generation !== socketGeneration) return
    const transport = new WebSocket(socketUrl(socketConfig)); socket = transport
    const current = () => !stopped && generation === socketGeneration
    transport.onopen = () => { if (!current()) return; connectionLabel.value = '正在认证'; transport.send(JSON.stringify({ event: 'auth', payload: { ticket } })); authTimeout = setTimeout(() => { if (!authenticated && current()) transport.close() }, 10000) }
    transport.onmessage = event => {
      if (!current()) return
      let data: any; try { data = JSON.parse(event.data) } catch { return }
      if (data.event === 'authenticated') { if (authenticated) return; authenticated = true; connectionLabel.value = '已连接'; clearTimeout(authTimeout); lastPong = Date.now(); heartbeat = setInterval(() => { if (Date.now() - lastPong > 60000) { transport.close(); return }; if (transport.readyState === WebSocket.OPEN) transport.send(JSON.stringify({ event: 'ping', payload: {} })) }, 25000); void refreshWorkbench(); return }
      if (data.event === 'pong') { lastPong = Date.now(); return }
      if (!authenticated) return
      const no = data.conversation_no || data.payload?.conversation_no
      if (data.event === 'message:created' && no === selected.value?.no) { merge({ ...normalizeSocketMessage(data.payload || {}), delivery: 'sent' }); void scrollBottom() }
      if (data.event?.startsWith('conversation:')) void refreshWorkbench()
      if (data.event === 'system:error') ElMessage.error(data.payload?.message || '客服连接异常')
    }
    transport.onclose = () => { if (current()) scheduleReconnect() }
    transport.onerror = () => transport.close()
  } catch { if (generation === socketGeneration) scheduleReconnect() }
}
async function scrollBottom() { await nextTick(); messageScroller.value?.scrollTo({ top: messageScroller.value.scrollHeight }) }
async function syncMessages() {
  if (!selected.value || syncingGeneration === selectionGeneration) return
  const no = selected.value.no, generation = selectionGeneration; syncingGeneration = generation
  try {
    while (!stopped && generation === selectionGeneration) {
      const page = apiData(await getMessages(no, { after_sequence: cursor, limit: 100 })) || []
      if (generation !== selectionGeneration) return
      page.forEach(message => merge({ ...message, delivery: 'sent' }))
      const next = Math.max(cursor, ...page.map(m => m.sequence || 0))
      if (next <= cursor || page.length < 100) { cursor = next; break }; cursor = next
    }
  } finally { if (syncingGeneration === generation) syncingGeneration = -1 }
}
async function selectConversation(conversation: ConversationVo) {
  selected.value = conversation; ++selectionGeneration; cursor = 0; messages.value = []; messageLoading.value = true
  try { await syncMessages(); await scrollBottom() } catch (e) { ElMessage.error(errorText(e)) } finally { messageLoading.value = false }
}
async function loadWorkbench(initial = false) {
  if (refreshing) return; refreshing = true; if (initial) loading.value = true
  try {
    const [queueResponse, conversationResponse, agentResponse, statisticResponse, currentAgentResponse] = await Promise.all([getQueue(), getConversations({ page: 1, page_size: 100 }), getAgents(), getStatistics(), getCurrentAgent()])
    if (stopped) return
    queuedConversations.value = apiData(queueResponse); conversations.value = apiData(conversationResponse).list || []; agents.value = apiData(agentResponse); statistics.value = apiData(statisticResponse); currentAgent.value = apiData(currentAgentResponse)
    if (selected.value) { const current = conversations.value.find(item => item.no === selected.value?.no); if (current) selected.value = current; else { selected.value = undefined; messages.value = []; ++selectionGeneration } }
    if (!selected.value && conversations.value[0]) await selectConversation(conversations.value[0])
  } catch (e) { if (initial) ElMessage.error(errorText(e)) } finally { loading.value = false; refreshing = false }
}
async function refreshWorkbench() { await loadWorkbench(); try { await syncMessages() } catch {} }
async function accept(conversation: ConversationVo = selected.value!, takeover = false) {
  if (!conversation) return
  try { if (!['online', 'busy'].includes(currentAgent.value.status)) await changeStatus('online'); await (takeover ? takeoverConversation(conversation.no) : acceptConversation(conversation.no)); await refreshWorkbench(); ElMessage.success('已开始接待') } catch (e) { ElMessage.error(errorText(e)) }
}
async function changeStatus(status: AgentStatus) { try { await updateAgentStatus(currentAgent.value.id, status); currentAgent.value.status = status } catch (e) { ElMessage.error(errorText(e)) } }
async function transfer(agent: AgentVo) { if (!selected.value) return; try { await transferConversation(selected.value.no, agent.id); transferVisible.value = false; await refreshWorkbench(); ElMessage.success(`已转接给 ${agent.name}`) } catch (e) { ElMessage.error(errorText(e)) } }
async function classify(categoryId: number) { if (!selected.value) return; try { await classifyConversation(selected.value.no, categoryId); await refreshWorkbench() } catch (e) { ElMessage.error(errorText(e)) } }
async function close() {
  if (!selected.value) return
  try { await ElMessageBox.confirm('确认结束本次会话？', '关闭会话', { type: 'warning' }); await closeConversation(selected.value.no); await refreshWorkbench() } catch (e) { if (e !== 'cancel' && e !== 'close') ElMessage.error(errorText(e)) }
}
async function submitMessage(existing?: MessageVo) {
  const content = existing?.content || draft.value.trim(); if (!content || !selected.value || !canReply.value) return
  const no = selected.value.no, id = existing?.client_message_id || `${Date.now()}-${Math.random().toString(16).slice(2)}`
  const pending: MessageVo = { id: 0, type: 'text', content, sender_type: 'agent', created_at: new Date().toLocaleTimeString(), client_message_id: id, delivery: 'sending' }
  merge(pending); if (!existing) draft.value = ''; await scrollBottom()
  try { const saved = apiData(await sendMessage(no, { type: 'text', content, client_message_id: id })); if (selected.value?.no === no) merge({ ...saved, delivery: 'sent' }) }
  catch (e) { if (selected.value?.no === no) { const current = messages.value.find(m => m.client_message_id === id); if (!current?.id) merge({ ...pending, delivery: 'failed' }) }; ElMessage.error(errorText(e)) }
}
async function showFaq() { try { faqs.value = apiData(await getFaqs({ page: 1, page_size: 100 })).list || []; faqVisible.value = true } catch (e) { ElMessage.error(errorText(e)) } }
function setFilter(value: string) { activeFilter.value = value }
onMounted(async () => { await loadWorkbench(true); void getCategories().then(r => { categories.value = r.data }).catch(() => undefined); void connectSocket(); refreshTimer = setInterval(() => { void refreshWorkbench() }, 15000) })
onBeforeUnmount(() => { stopped = true; ++socketGeneration; ++selectionGeneration; clearInterval(refreshTimer); clearInterval(heartbeat); clearTimeout(reconnect); clearTimeout(authTimeout); socket?.close(); socket = undefined })
</script>

<template>
  <main v-loading="loading" class="customer-workbench">
    <header class="workbench-header">
      <div class="title-group">
        <div class="service-mark"><el-icon><Service /></el-icon></div>
        <div>
          <div class="eyebrow">CUSTOMER OPERATIONS</div>
          <h1>客服工作台</h1>
          <p>快速响应每一位正在等待的客户</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="connection"><span class="live-dot" :style="{ background: connectionLabel === '已连接' ? '#18bc86' : '#e49a2b' }" /> {{ connectionLabel }}</div>
        <el-dropdown trigger="click" @command="changeStatus">
          <button class="agent-profile">
            <el-avatar :size="34" class="agent-avatar">客</el-avatar>
            <span><b>{{ currentAgent.name }}</b><small>{{ statusOptions.find(item => item.value === currentAgent.status)?.label }}</small></span>
            <el-icon><ArrowDown /></el-icon>
          </button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item v-for="option in statusOptions" :key="option.value" :command="option.value" :disabled="option.value === currentAgent.status">
                <span class="status-dot" :class="option.value" /> {{ option.label }}
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </header>

    <section class="metric-grid" aria-label="客服指标">
      <article class="metric-card accent-violet"><span class="metric-icon"><el-icon><ChatDotRound /></el-icon></span><div><small>等待接待</small><strong>{{ statistics.queued_count }}</strong><em>请优先处理排队会话</em></div></article>
      <article class="metric-card accent-blue"><span class="metric-icon"><el-icon><Connection /></el-icon></span><div><small>正在接待</small><strong>{{ statistics.active_count }}</strong><em>个人负载 {{ agentLoad }} 个会话</em></div></article>
      <article class="metric-card accent-mint"><span class="metric-icon"><el-icon><Check /></el-icon></span><div><small>今日已解决</small><strong>{{ statistics.today_closed_count }}</strong><em>保持高质量服务体验</em></div></article>
      <article class="metric-card accent-amber"><span class="metric-icon"><el-icon><Promotion /></el-icon></span><div><small>平均首次响应</small><strong>{{ statistics.average_response_seconds ? `${statistics.average_response_seconds}s` : '—' }}</strong><em>目标：小于 60 秒</em></div></article>
    </section>

    <section class="workspace">
      <aside class="conversation-panel">
        <div class="panel-heading"><div><h2>会话列表</h2><span>{{ filteredConversations.length }} 个会话</span></div><el-button circle plain :icon="Search" aria-label="搜索会话" /></div>
        <el-input v-model="search" class="conversation-search" :prefix-icon="Search" placeholder="搜索用户或会话编号" clearable />
        <div class="filter-tabs" role="tablist">
          <button v-for="tab in [{ value: 'all', label: '全部' }, { value: 'ai_active', label: 'AI' }, { value: 'queued', label: '排队' }, { value: 'active', label: '接待中' }, { value: 'closed', label: '已关闭' }]" :key="tab.value" :class="{ active: activeFilter === tab.value }" @click="setFilter(tab.value)">
            {{ tab.label }}<span v-if="tab.value === 'queued' && statistics.queued_count">{{ statistics.queued_count }}</span>
          </button>
        </div>
        <div class="conversation-list">
          <button v-for="conversation in filteredConversations" :key="conversation.no" class="conversation-item" :class="{ selected: selected?.no === conversation.no }" @click="selectConversation(conversation)">
            <el-avatar :size="42" :src="conversation.member_avatar">{{ conversation.member_name.slice(0, 1) }}</el-avatar>
            <span class="conversation-copy"><b>{{ conversation.member_name }}</b><small>{{ conversation.last_message || (conversation.reception_state === 'ai_active' ? 'AI 正在接待' : conversation.status === 'queued' ? '正在等待客服接待' : '暂无新消息') }}</small></span>
            <span class="conversation-meta"><time>{{ conversation.last_message_at || '刚刚' }}</time><i v-if="conversation.unread_count">{{ conversation.unread_count }}</i><em :class="conversation.status">{{ conversation.reception_state ? receptionLabels[conversation.reception_state] : conversation.status === 'queued' ? `排队 ${conversation.queue_position || ''}` : conversation.status === 'active' ? '接待中' : '已关闭' }}</em></span>
          </button>
          <el-empty v-if="!filteredConversations.length" :image-size="82" description="暂无匹配的会话" />
        </div>
      </aside>

      <section class="chat-panel">
        <template v-if="selected">
          <header class="chat-heading"><div class="customer-title"><el-avatar :size="42" :src="selected.member_avatar">{{ selected.member_name.slice(0, 1) }}</el-avatar><div><h2>{{ selected.member_name }}</h2><p><span class="status-dot" :class="activeStatusClass" /> {{ activeLabel }} · {{ selected.source || '微信小程序' }}</p></div></div><div class="chat-actions"><el-button v-if="canAccept" type="primary" @click="accept()">立即接待</el-button><el-button v-if="canTakeover" type="primary" @click="accept(selected, true)">接管 AI / 留言</el-button><el-button v-if="canReply && hasAuth('customer-service:conversation:transfer')" plain @click="transferVisible = true">转接</el-button><el-dropdown><el-button circle plain :icon="MoreFilled" aria-label="更多会话操作" /></el-dropdown></div></header>
          <div ref="messageScroller" v-loading="messageLoading" class="message-stream">
            <div class="session-note"><span>会话编号 {{ selected.no }}</span><span> · </span><span>{{ selected.order_no ? `关联订单 ${selected.order_no}` : '来自客户服务入口' }}</span></div>
            <div v-for="message in messages" :key="message.id || message.client_message_id" class="message-row" :class="message.sender_type">
              <el-avatar v-if="message.sender_type !== 'system'" :size="30" :src="message.sender_type === 'member' ? selected.member_avatar : undefined">{{ message.sender_type === 'member' ? selected.member_name.slice(0, 1) : message.sender_type === 'ai' ? 'AI' : '客' }}</el-avatar>
              <div v-if="message.type === 'system'" class="system-message">{{ message.content }}</div>
              <div v-else class="message-content"><div v-if="message.type === 'product_card'" class="product-card"><img :src="message.extra?.product_image" alt=""><div><b>{{ message.extra?.product_name }}</b><small>￥{{ message.extra?.product_price }}</small><span>查看商品 <el-icon><ArrowDown /></el-icon></span></div></div><img v-else-if="message.type === 'image'" class="chat-image" :src="message.extra?.image_url" alt="客户发送的图片"><p v-else>{{ message.content }}</p><time>{{ message.created_at }}<span v-if="message.delivery === 'sending'"> · 发送中</span><el-button v-if="message.delivery === 'failed'" link type="danger" @click="submitMessage(message)">未确认，点击重试</el-button></time></div>
            </div>
            <el-empty v-if="!messageLoading && !messages.length" :image-size="96" description="还没有消息，开始服务吧" />
          </div>
          <footer class="composer"><div class="composer-tools"><el-tooltip content="当前工作台支持文本回复，可查看用户图片"><el-button text :icon="Picture" aria-label="图片回复暂未开放" disabled /></el-tooltip><el-tooltip content="可查看用户发送的商品卡片"><el-button text :icon="ShoppingBag" aria-label="商品卡回复暂未开放" disabled /></el-tooltip><el-button text :icon="DocumentCopy" aria-label="插入常见问题" :disabled="!canReply" @click="showFaq" /></div><el-input v-model="draft" type="textarea" :autosize="{ minRows: 2, maxRows: 4 }" resize="none" :disabled="!canReply" placeholder="输入回复内容，按 Enter 发送，Shift + Enter 换行" @keydown.enter.exact.prevent="submitMessage()" /><div class="composer-footer"><span>{{ draft.length }}/1000</span><div><el-button v-if="canReply && hasAuth('customer-service:conversation:close')" text type="danger" :icon="CircleClose" @click="close">关闭会话</el-button><el-button type="primary" :disabled="!draft.trim() || !canReply" @click="submitMessage()">发送 <kbd>Enter</kbd></el-button></div></div></footer>
        </template>
        <el-empty v-else class="selection-empty" :image-size="120" description="从左侧选择一个会话开始接待" />
      </section>

      <aside class="detail-panel">
        <template v-if="selected"><section class="detail-card"><div class="detail-title"><h3>客户信息</h3><el-button link type="primary">查看档案</el-button></div><div class="member-summary"><el-avatar :size="48" :src="selected.member_avatar">{{ selected.member_name.slice(0, 1) }}</el-avatar><div><b>{{ selected.member_name }}</b><span>ID: {{ selected.member_id || '—' }}</span></div></div><dl><div><dt>会话来源</dt><dd>{{ selected.source || '微信小程序' }}</dd></div><div><dt>当前坐席</dt><dd>{{ selected.reception_state === 'ai_active' ? 'AI Agent' : selected.agent_name || '尚未分配' }}</dd></div><div><dt>关联订单</dt><dd class="order-link">{{ selected.order_no || '暂无关联订单' }}</dd></div></dl><el-select :model-value="selected.category_id" :disabled="selected.status === 'closed' || !hasAuth('customer-service:conversation:accept')" placeholder="问题类目" @change="classify"><el-option v-for="c in categories" :key="c.id" :value="c.id" :label="categoryPath(categories, c.id)" /></el-select><p v-if="selected.handoff_reason">转人工原因：{{ handoffReasons[selected.handoff_reason] || 'AI 未能解决，请人工处理' }}</p></section><section class="detail-card queue-card"><div class="detail-title"><h3>接待状态</h3><span class="status-chip" :class="currentAgent.status"><span class="status-dot" :class="currentAgent.status" />{{ statusOptions.find(item => item.value === currentAgent.status)?.label }}</span></div><p>当前已接待 <b>{{ agentLoad }}</b> 个会话</p><el-progress :percentage="Math.round(((currentAgent.active_conversation_count || 0) / (currentAgent.max_conversations || 5)) * 100)" :show-text="false" :stroke-width="6" color="#6366f1" /><el-button class="block-button" plain :icon="SwitchButton" @click="changeStatus(currentAgent.status === 'online' ? 'busy' : 'online')">{{ currentAgent.status === 'online' ? '暂停接待' : '恢复接待' }}</el-button></section><section class="detail-card"><div class="detail-title"><h3>快捷回复</h3><el-button link type="primary">管理</el-button></div><button class="quick-reply" @click="draft = '您好，已收到您的问题，我正在为您核实，请稍候。'">您好，已收到您的问题，我正在为您核实。</button><button class="quick-reply" @click="draft = '感谢您的耐心等待，还有其他需要帮助的吗？'">感谢您的耐心等待，还有其他需要帮助的吗？</button></section></template>
        <el-empty v-else :image-size="80" description="选择会话后查看客户信息" />
      </aside>
    </section>

    <el-dialog v-model="transferVisible" title="转接会话" width="440px" destroy-on-close><p class="transfer-hint">选择一位在线坐席，当前会话将转交给对方继续处理。</p><div class="agent-list"><button v-for="agent in agents.filter(item => item.id !== currentAgent.id && item.status === 'online' && item.enabled !== false)" :key="agent.id" class="transfer-agent" @click="transfer(agent)"><el-avatar :size="38" :src="agent.avatar">{{ agent.name.slice(0, 1) }}</el-avatar><span><b>{{ agent.name }}</b><small><span class="status-dot online" />在线 · {{ agent.active_conversation_count || 0 }}/{{ agent.max_conversations || 5 }} 会话</small></span><el-icon><ArrowDown /></el-icon></button><el-empty v-if="!agents.filter(item => item.id !== currentAgent.id && item.status === 'online' && item.enabled !== false).length" :image-size="64" description="当前没有可转接的在线坐席" /></div></el-dialog>
    <el-dialog v-model="faqVisible" title="选择常见问题回复" width="620px"><el-empty v-if="!faqs.length" description="暂无可用知识" /><el-button v-for="faq in faqs.filter(f => f.enabled)" :key="faq.id" class="quick-reply" @click="draft = faq.answer; faqVisible = false">{{ faq.question }}</el-button></el-dialog>
  </main>
</template>

<style scoped lang="scss">
.customer-workbench { --ink:#18223d; --muted:#8290aa; --line:#e8ecf4; --panel:#fff; --canvas:#f5f7fc; min-width: 1060px; padding: 22px; color: var(--ink); background: var(--canvas); font-family: 'Alibaba PuHuiTi 3.0', system-ui, sans-serif; }
.workbench-header,.header-actions,.title-group,.agent-profile,.metric-card,.panel-heading,.customer-title,.chat-heading,.chat-actions,.detail-title,.member-summary,.composer-footer,.connection { display:flex; align-items:center; }
.workbench-header { justify-content:space-between; padding:0 2px 20px; }.title-group { gap:12px; }.service-mark { display:grid; width:44px; height:44px; color:#fff; font-size:22px; place-items:center; border-radius:14px; background:linear-gradient(140deg,#7768f6,#5045cf); box-shadow:0 9px 20px #6557e03d; }.eyebrow { margin-bottom:3px; color:#7782a3; font-size:10px; font-weight:700; letter-spacing:1.3px; }.title-group h1 { margin:0; font-size:21px; letter-spacing:-.5px; }.title-group p { margin:3px 0 0; color:var(--muted); font-size:12px; }.header-actions { gap:16px; }.connection { gap:7px; color:#63708a; font-size:12px; }.live-dot { width:7px; height:7px; border-radius:50%; background:#18bc86; box-shadow:0 0 0 4px #18bc8619; }.agent-profile { gap:9px; padding:4px 9px 4px 4px; cursor:pointer; color:var(--ink); border:1px solid var(--line); border-radius:11px; background:#fff; }.agent-profile span { display:grid; text-align:left; }.agent-profile b { font-size:12px; }.agent-profile small { color:var(--muted); font-size:10px; }.agent-avatar { color:#fff; background:#8b82ef; }
.metric-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:13px; margin-bottom:14px; }.metric-card { min-height:94px; gap:12px; padding:16px; overflow:hidden; border:1px solid var(--line); border-radius:15px; background:var(--panel); }.metric-icon { display:grid; width:40px; height:40px; font-size:19px; place-items:center; border-radius:12px; }.metric-card div { display:grid; gap:1px; }.metric-card small,.metric-card em { color:var(--muted); font-size:11px; font-style:normal; }.metric-card strong { font-size:25px; line-height:1.1; letter-spacing:-1px; }.accent-violet .metric-icon { color:#6559dc; background:#eeebff; }.accent-blue .metric-icon { color:#3285dd; background:#e8f3ff; }.accent-mint .metric-icon { color:#12a879; background:#e4f8f0; }.accent-amber .metric-icon { color:#d88d1f; background:#fff4db; }
.workspace { display:grid; grid-template-columns:300px minmax(400px,1fr) 242px; height:calc(100vh - 210px); min-height:620px; overflow:hidden; border:1px solid var(--line); border-radius:16px; background:#fff; box-shadow:0 12px 34px #202b4b0a; }.conversation-panel,.detail-panel { overflow:auto; background:#fff; }.conversation-panel { border-right:1px solid var(--line); }.detail-panel { padding:15px; border-left:1px solid var(--line); }.panel-heading { justify-content:space-between; padding:17px 15px 11px; }.panel-heading h2,.chat-heading h2,.detail-title h3 { margin:0; font-size:14px; }.panel-heading span { color:var(--muted); font-size:11px; }.conversation-search { padding:0 14px; }.conversation-search :deep(.el-input__wrapper) { border-radius:9px; background:#f6f8fc; box-shadow:none; }.filter-tabs { display:flex; gap:3px; padding:13px 12px 8px; }.filter-tabs button { display:flex; gap:4px; padding:6px 8px; cursor:pointer; color:#7b869f; font-size:11px; border:0; border-radius:7px; background:transparent; }.filter-tabs button.active { color:#574bd1; font-weight:700; background:#eeecff; }.filter-tabs span { display:grid; min-width:15px; height:15px; color:#fff; font-size:9px; place-items:center; border-radius:6px; background:#f06c72; }.conversation-list { padding:0 7px 14px; }.conversation-item { display:grid; grid-template-columns:42px minmax(0,1fr) auto; gap:9px; width:100%; padding:11px 8px; cursor:pointer; text-align:left; border:0; border-radius:11px; background:transparent; transition:background .2s; }.conversation-item:hover { background:#f7f8fe; }.conversation-item.selected { background:#eeedff; }.conversation-copy { min-width:0; }.conversation-copy b,.conversation-copy small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }.conversation-copy b { margin:2px 0 4px; font-size:12px; }.conversation-copy small { color:#8a95ad; font-size:10px; }.conversation-meta { display:grid; justify-items:end; gap:3px; }.conversation-meta time { color:#a5aec0; font-size:9px; }.conversation-meta i { display:grid; min-width:15px; height:15px; color:#fff; font-size:9px; font-style:normal; place-items:center; border-radius:50%; background:#6c61e5; }.conversation-meta em { color:#8a95a9; font-size:9px; font-style:normal; }.conversation-meta em.queued { color:#d3881f; }.conversation-meta em.active { color:#13a878; }
.chat-panel { display:grid; min-width:0; grid-template-rows:auto 1fr auto; background:#fbfcff; }.chat-heading { justify-content:space-between; min-height:72px; padding:0 18px; border-bottom:1px solid var(--line); background:#fff; }.customer-title { gap:10px; }.customer-title h2 { font-size:14px; }.customer-title p { display:flex; gap:5px; align-items:center; margin:3px 0 0; color:var(--muted); font-size:10px; }.chat-actions { gap:7px; }.message-stream { padding:21px 24px; overflow:auto; }.session-note { width:max-content; max-width:100%; padding:5px 10px; margin:0 auto 22px; color:#9aa4b8; font-size:10px; text-align:center; border-radius:20px; background:#f1f3f9; }.message-row { display:flex; gap:8px; align-items:flex-start; max-width:76%; margin:13px 0; }.message-row.agent { flex-direction:row-reverse; margin-left:auto; }.message-content { min-width:0; }.message-content p { padding:9px 12px; margin:0; color:#38435a; font-size:13px; line-height:1.6; white-space:pre-wrap; border-radius:4px 13px 13px; background:#fff; box-shadow:0 3px 9px #16214a0b; }.agent .message-content p { color:#fff; border-radius:13px 4px 13px 13px; background:#6559dc; }.message-content time { display:block; margin-top:4px; color:#a4adbf; font-size:9px; }.agent .message-content time { text-align:right; }.system-message { width:max-content; padding:5px 9px; margin:8px auto; color:#8b94a8; font-size:10px; border-radius:5px; background:#f0f2f7; }.message-row:has(.system-message) { display:block; max-width:none; }.chat-image { display:block; max-width:210px; max-height:220px; border-radius:10px; }.product-card { display:flex; width:240px; gap:9px; padding:8px; border:1px solid #e9e5ff; border-radius:10px; background:#fff; }.product-card img { width:51px; height:51px; object-fit:cover; border-radius:7px; background:#f3f3f7; }.product-card div { display:grid; min-width:0; }.product-card b { overflow:hidden; font-size:11px; text-overflow:ellipsis; white-space:nowrap; }.product-card small { color:#ed6a56; font-size:12px; }.product-card span { color:#7b73d6; font-size:10px; }.composer { padding:9px 16px 11px; border-top:1px solid var(--line); background:#fff; }.composer-tools { display:flex; gap:1px; }.composer :deep(.el-textarea__inner) { padding:6px 3px; font-size:13px; box-shadow:none; }.composer-footer { justify-content:space-between; }.composer-footer > span { color:#a5adbd; font-size:10px; }.composer-footer :deep(.el-button) { height:28px; }.composer kbd { padding:1px 4px; margin-left:4px; color:#dddaff; font-size:9px; border:1px solid #ffffff33; border-radius:3px; }.selection-empty { display:grid; place-items:center; }
.detail-card { padding-bottom:15px; margin-bottom:15px; border-bottom:1px solid var(--line); }.detail-title { justify-content:space-between; margin-bottom:13px; }.detail-title h3 { font-size:13px; }.member-summary { gap:9px; }.member-summary div { display:grid; gap:3px; }.member-summary b { font-size:13px; }.member-summary span { color:var(--muted); font-size:10px; }.detail-card dl { margin:13px 0 0; }.detail-card dl div { display:flex; justify-content:space-between; padding:6px 0; font-size:11px; }.detail-card dt { color:#929caf; }.detail-card dd { max-width:120px; margin:0; overflow:hidden; color:#4c5871; text-align:right; text-overflow:ellipsis; white-space:nowrap; }.detail-card .order-link { color:#6458d7; }.queue-card p { color:#8190a7; font-size:11px; }.status-chip { display:flex; gap:4px; align-items:center; padding:3px 7px; color:#516079; font-size:10px; border-radius:10px; background:#f4f6fa; }.status-dot { display:inline-block; width:6px; height:6px; border-radius:50%; background:#bac3d2; }.status-dot.online,.status-dot.active { background:#12b880; }.status-dot.busy,.status-dot.queued { background:#e49a2b; }.status-dot.away { background:#8c93a5; }.block-button { width:100%; margin-top:13px; }.quick-reply { width:100%; padding:9px; margin-top:6px; cursor:pointer; color:#66738b; font-size:11px; line-height:1.5; text-align:left; border:1px solid #e9edf5; border-radius:8px; background:#fafbfe; transition:.2s; }.quick-reply:hover { color:#5e51d3; border-color:#cdc8ff; background:#f2f0ff; }.transfer-hint { margin:0 0 14px; color:#7d879b; font-size:12px; }.agent-list { display:grid; gap:5px; }.transfer-agent { display:flex; gap:10px; align-items:center; width:100%; padding:9px; cursor:pointer; text-align:left; border:1px solid #edf0f5; border-radius:9px; background:#fff; }.transfer-agent:hover { border-color:#cfcaff; background:#f8f7ff; }.transfer-agent span { display:grid; flex:1; gap:3px; }.transfer-agent b { font-size:12px; }.transfer-agent small { color:#8290a7; font-size:10px; }
:global(html.dark) {
  .customer-workbench {
    --ink: #edf1fa;
    --muted: #9aa6bc;
    --line: #2a3448;
    --panel: #161e2d;
    --canvas: #0d1420;
  }

  .agent-profile,
  .workspace,
  .conversation-panel,
  .detail-panel,
  .chat-heading,
  .composer,
  .message-content p,
  .product-card,
  .transfer-agent {
    background: var(--panel);
  }

  .workspace { box-shadow: 0 16px 38px rgb(0 0 0 / 28%); }
  .chat-panel { background: #111a29; }
  .conversation-search :deep(.el-input__wrapper) { background: #202a3a; }
  .conversation-item:hover { background: #202a3a; }
  .conversation-item.selected { background: rgb(101 89 220 / 26%); }
  .filter-tabs button { color: #aab5c8; }
  .filter-tabs button.active { color: #c4beff; background: rgb(101 89 220 / 28%); }
  .session-note,
  .system-message { color: #aab5c8; background: #202a3a; }
  .message-content p { color: #e8edf8; box-shadow: 0 3px 10px rgb(0 0 0 / 20%); }
  .product-card { border-color: #3b3565; }
  .product-card img { background: #283246; }
  .detail-card dt,
  .detail-card dd,
  .status-chip,
  .transfer-hint,
  .quick-reply,
  .transfer-agent small { color: #aab5c8; }
  .status-chip { background: #202a3a; }
  .quick-reply { border-color: #303b50; background: #1c2636; }
  .quick-reply:hover { color: #c4beff; border-color: #6258ad; background: #292544; }
  .transfer-agent { border-color: #303b50; }
  .transfer-agent:hover { border-color: #6258ad; background: #202a3a; }
  .accent-violet .metric-icon { color: #c4beff; background: #302b58; }
  .accent-blue .metric-icon { color: #8fc5ff; background: #1d3855; }
  .accent-mint .metric-icon { color: #67d9b4; background: #183d39; }
  .accent-amber .metric-icon { color: #f6c46e; background: #493818; }
}
:global(body.dark) {
  .customer-workbench {
    --ink: #edf1fa;
    --muted: #9aa6bc;
    --line: #2a3448;
    --panel: #161e2d;
    --canvas: #0d1420;
  }
  .agent-profile, .workspace, .conversation-panel, .detail-panel, .chat-heading, .composer,
  .message-content p, .product-card, .transfer-agent { background: var(--panel); }
  .chat-panel { background: #111a29; }
  .conversation-search :deep(.el-input__wrapper) { background: #202a3a; }
  .conversation-item:hover { background: #202a3a; }
  .conversation-item.selected { background: rgb(101 89 220 / 26%); }
  .filter-tabs button { color: #aab5c8; }
  .filter-tabs button.active { color: #c4beff; background: rgb(101 89 220 / 28%); }
  .session-note, .system-message { color: #aab5c8; background: #202a3a; }
  .message-content p { color: #e8edf8; }
  .detail-card dt, .detail-card dd, .status-chip, .transfer-hint,
  .quick-reply, .transfer-agent small { color: #aab5c8; }
  .status-chip { background: #202a3a; }
  .quick-reply { border-color: #303b50; background: #1c2636; }
  .transfer-agent { border-color: #303b50; }
}
.chat-panel { min-height: 0; grid-template-rows: auto minmax(0, 1fr) auto; }
.message-stream { min-height: 0; }
@media (max-width: 1180px) { .customer-workbench { min-width:960px; }.workspace { grid-template-columns:280px minmax(400px,1fr) 220px; }.metric-card { padding:13px; }.metric-card em { display:none; } }
</style>

<style lang="scss">
html.dark .customer-workbench,
body.dark .customer-workbench {
  --ink: #edf1fa;
  --muted: #9aa6bc;
  --line: #2a3448;
  --panel: #161e2d;
  --canvas: #0d1420;
  color: var(--ink);
  background: var(--canvas);
}

html.dark .customer-workbench .agent-profile,
html.dark .customer-workbench .workspace,
html.dark .customer-workbench .conversation-panel,
html.dark .customer-workbench .detail-panel,
html.dark .customer-workbench .chat-heading,
html.dark .customer-workbench .composer,
html.dark .customer-workbench .message-content p,
html.dark .customer-workbench .product-card,
html.dark .customer-workbench .transfer-agent,
body.dark .customer-workbench .agent-profile,
body.dark .customer-workbench .workspace,
body.dark .customer-workbench .conversation-panel,
body.dark .customer-workbench .detail-panel,
body.dark .customer-workbench .chat-heading,
body.dark .customer-workbench .composer,
body.dark .customer-workbench .message-content p,
body.dark .customer-workbench .product-card,
body.dark .customer-workbench .transfer-agent {
  background: var(--panel);
}

html.dark .customer-workbench .chat-panel,
body.dark .customer-workbench .chat-panel {
  background: #111a29;
}

html.dark .customer-workbench .conversation-search .el-input__wrapper,
body.dark .customer-workbench .conversation-search .el-input__wrapper {
  background: #202a3a;
}

html.dark .customer-workbench .conversation-item:hover,
body.dark .customer-workbench .conversation-item:hover {
  background: #202a3a;
}

html.dark .customer-workbench .conversation-item.selected,
body.dark .customer-workbench .conversation-item.selected {
  background: rgb(101 89 220 / 26%);
}

html.dark .customer-workbench .filter-tabs button,
body.dark .customer-workbench .filter-tabs button,
html.dark .customer-workbench .detail-card dt,
html.dark .customer-workbench .detail-card dd,
html.dark .customer-workbench .status-chip,
html.dark .customer-workbench .transfer-hint,
html.dark .customer-workbench .quick-reply,
html.dark .customer-workbench .transfer-agent small,
body.dark .customer-workbench .detail-card dt,
body.dark .customer-workbench .detail-card dd,
body.dark .customer-workbench .status-chip,
body.dark .customer-workbench .transfer-hint,
body.dark .customer-workbench .quick-reply,
body.dark .customer-workbench .transfer-agent small {
  color: #aab5c8;
}

html.dark .customer-workbench .filter-tabs button.active,
body.dark .customer-workbench .filter-tabs button.active {
  color: #c4beff;
  background: rgb(101 89 220 / 28%);
}

html.dark .customer-workbench .session-note,
html.dark .customer-workbench .system-message,
body.dark .customer-workbench .session-note,
body.dark .customer-workbench .system-message {
  color: #aab5c8;
  background: #202a3a;
}

html.dark .customer-workbench .message-content p,
body.dark .customer-workbench .message-content p {
  color: #e8edf8;
}

html.dark .customer-workbench .status-chip,
body.dark .customer-workbench .status-chip {
  background: #202a3a;
}

html.dark .customer-workbench .quick-reply,
body.dark .customer-workbench .quick-reply {
  border-color: #303b50;
  background: #1c2636;
}

html.dark .customer-workbench .transfer-agent,
body.dark .customer-workbench .transfer-agent {
  border-color: #303b50;
}
</style>
