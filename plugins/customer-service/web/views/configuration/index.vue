<script setup lang="ts">
import type { AgentVo, ConfigurationDocument, ConfigurationEntry, Resource } from '../../api/customer-service'
import { computed, onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { configureAgent, getAgents, getAiRuns, getConfiguration, rollbackConfiguration, saveConfiguration, simulateRouting, testAgent } from '../../api/customer-service'
import ScheduleEditor from './ScheduleEditor.vue'
import { categoryPath, emptyEntry, replaceEntry, resourceNames } from './model'

const document = ref<ConfigurationDocument>()
const active = ref('settings')
const loading = ref(false)
const busy = ref(false)
const dirty = ref(false)
const error = ref('')
const agents = ref<AgentVo[]>([])
const runs = ref<Record<string, any>[]>([])
const editing = ref<Resource>()
const entry = ref<ConfigurationEntry>({ id: 0 })
const dialog = ref(false)
const agentDialog = ref(false)
const agentForm = ref<AgentVo>({ id: 0, name: '', status: 'offline' })
const question = ref('')
const categoryId = ref<number>()
const testAgentId = ref<number>()
const result = ref<Record<string, any>>()
const revisionToRestore = ref<number>()
const snapshot = computed(() => document.value?.snapshot)
const resources = Object.keys(resourceNames) as Resource[]
const numbers: Array<{ key: string, label: string, max?: number, min?: number }> = [
  { key: 'max_queue_size', label: '最大排队人数' }, { key: 'queue_timeout_seconds', label: '排队超时（秒）' },
  { key: 'accept_timeout_seconds', label: '接单确认时限（秒）' }, { key: 'max_conversations_per_agent', label: '新坐席默认接待上限' },
  { key: 'history_page_size', label: '历史消息每页条数', max: 100 }, { key: 'max_message_length', label: '消息字数上限' },
  { key: 'max_image_size_mb', label: '图片上限（MB）', max: 100 }, { key: 'ai_timeout_seconds', label: 'AI 超时转人工（秒）', max: 60 },
  { key: 'ai_max_clarifications', label: 'AI 最多澄清次数', min: 0, max: 5 }, { key: 'ai_max_rounds', label: 'AI 最多对话轮次' }, { key: 'ai_daily_token_budget', label: 'AI 每日 Token 预算' },
]
function message(e: any) { return e?.response?.data?.message || e?.response?.data?.msg || e?.message || '操作失败，请重试' }
async function load() {
  loading.value = true; error.value = ''
  try { const [config, team] = await Promise.all([getConfiguration(), getAgents()]); document.value = config.data; agents.value = team.data; dirty.value = false }
  catch (e) { error.value = message(e) }
  finally { loading.value = false }
}
async function reload() {
  if (dirty.value) { try { await ElMessageBox.confirm('刷新会丢弃本地未保存的草稿改动。', '刷新配置', { type: 'warning' }) } catch { return } }
  await load()
}
async function save(publish = false) {
  if (!document.value) return
  busy.value = true; error.value = ''
  try {
    const response = await saveConfiguration(document.value.snapshot, document.value.revision, publish)
    document.value = { ...document.value, ...response.data, published_revision: publish ? response.data.revision : document.value.published_revision }
    dirty.value = false; ElMessage.success(publish ? '客服配置已发布' : '草稿已保存')
    // Refresh history only after a successful write; failed/conflicting writes keep the local draft.
    const current = await getConfiguration(); document.value = current.data
  }
  catch (e) { error.value = message(e); ElMessage.error(error.value) }
  finally { busy.value = false }
}
function edit(resource: Resource, item?: any) {
  if (!snapshot.value) return
  editing.value = resource; entry.value = JSON.parse(JSON.stringify(item || emptyEntry(resource, snapshot.value))); dialog.value = true
}
function applyEntry() {
  if (!document.value || !editing.value) return
  if (editing.value === 'keyword_rules' && (!entry.value.category_id || !entry.value.include_terms?.length)) { ElMessage.warning('关键词规则需要目标类目和至少一个正向词'); return }
  try { document.value.snapshot = replaceEntry(document.value.snapshot, editing.value, entry.value); dirty.value = true; dialog.value = false }
  catch (e) { ElMessage.error(message(e)) }
}
async function remove(resource: Resource, item: any) {
  if (!document.value) return
  try {
    await ElMessageBox.confirm('从草稿移除此项？引用关系会在保存时校验。', '移除配置', { type: 'warning' })
    document.value.snapshot[resource] = document.value.snapshot[resource].filter(i => i.id !== item.id) as any; dirty.value = true
  }
  catch {}
}
async function restore() {
  if (!document.value || !revisionToRestore.value) return
  try {
    await ElMessageBox.confirm(`将版本 ${revisionToRestore.value} 作为新版本发布${dirty.value ? '，并丢弃本地未保存改动' : ''}。`, '回滚已发布配置', { type: 'warning', confirmButtonText: '确认回滚', cancelButtonText: '取消' })
    busy.value = true
    await rollbackConfiguration(revisionToRestore.value, document.value.revision); await load(); ElMessage.success('已回滚并发布')
  }
  catch (e) { if (e !== 'cancel' && e !== 'close') ElMessage.error(message(e)) }
  finally { busy.value = false }
}
async function runTest(ai = false) {
  if (!question.value.trim() || (ai && !testAgentId.value)) return
  if (dirty.value) { ElMessage.warning('请先保存草稿，再测试服务器上的配置'); return }
  busy.value = true
  try { result.value = (await (ai ? testAgent(testAgentId.value!, question.value) : simulateRouting(question.value, categoryId.value))).data }
  catch (e) { ElMessage.error(message(e)) }
  finally { busy.value = false }
}
async function loadRuns() { try { runs.value = (await getAiRuns()).data } catch (e) { ElMessage.error(message(e)) } }
function editAgent(agent: any) { agentForm.value = JSON.parse(JSON.stringify(agent)); agentDialog.value = true }
async function saveAgent() {
  busy.value = true
  try { await configureAgent(agentForm.value); agents.value = (await getAgents()).data; agentDialog.value = false; ElMessage.success('坐席配置已生效') }
  catch (e) { ElMessage.error(message(e)) }
  finally { busy.value = false }
}
onMounted(load)
</script>

<template>
  <section v-loading="loading" class="support-config">
    <header class="config-header"><div><h2>接待配置</h2><p>先保存草稿并测试，后续消息使用发布版本，已发起的 AI 任务保留任务版本；关闭 AI 会影响正在接待的会话。坐席参数保存后立即生效。</p></div><div class="actions"><el-tag v-if="document" :type="dirty ? 'warning' : 'info'">{{ dirty ? '有未保存改动' : `草稿 v${document.revision}` }} · 已发布 v{{ document?.published_revision || 0 }}</el-tag><el-button :disabled="busy" @click="reload">刷新</el-button><el-button data-testid="save-draft" :loading="busy" :disabled="!document" @click="save(false)">保存草稿</el-button><el-button data-testid="publish" type="primary" :loading="busy" :disabled="!document" @click="save(true)">发布配置</el-button></div></header>
    <el-alert v-if="error" :title="error" type="error" :closable="false" show-icon class="error-banner" />
    <el-tabs v-if="snapshot" v-model="active">
      <el-tab-pane label="接待策略" name="settings">
        <el-form label-position="top" @change="dirty = true">
          <div class="form-grid"><el-form-item label="客服总开关"><el-switch v-model="snapshot.settings.enabled" @change="dirty = true" /></el-form-item><el-form-item label="AI 默认接待"><el-switch v-model="snapshot.settings.ai_enabled" data-testid="ai-enabled" active-text="AI 优先，无法解答转人工" @change="dirty = true" /></el-form-item><el-form-item label="默认 AI Agent"><el-select v-model="snapshot.settings.default_ai_agent_id" clearable @change="dirty = true"><el-option v-for="item in snapshot.ai_agents" :key="item.id" :value="item.id" :label="item.name || String(item.id)" :disabled="item.enabled === false" /></el-select></el-form-item><el-form-item label="人工兜底技能组"><el-select v-model="snapshot.settings.fallback_group_id" clearable @change="dirty = true"><el-option v-for="item in snapshot.skill_groups" :key="item.id" :value="item.id" :label="item.name" /></el-select></el-form-item><el-form-item label="坐席分配策略"><el-select v-model="snapshot.settings.assignment_strategy" @change="dirty = true"><el-option label="最少负载" value="least_load" /><el-option label="轮询" value="round_robin" /><el-option label="加权负载" value="weighted" /><el-option label="手动接单" value="manual" /></el-select></el-form-item><el-form-item label="无人可接时排队"><el-switch v-model="snapshot.settings.queue_enabled" @change="dirty = true" /></el-form-item><el-form-item label="用户发送图片"><el-switch v-model="snapshot.settings.allow_member_image" @change="dirty = true" /></el-form-item><el-form-item label="发送商品卡片"><el-switch v-model="snapshot.settings.allow_product_card" @change="dirty = true" /></el-form-item><el-form-item v-for="field in numbers" :key="field.key" :label="field.label"><el-input-number v-model="snapshot.settings[field.key]" :min="field.min ?? 1" :max="field.max ?? 10000000" :precision="0" @change="dirty = true" /></el-form-item></div>
          <div class="form-grid"><el-form-item label="Socket 网关地址（留空使用同域）"><el-input v-model="snapshot.settings.gateway_url" placeholder="wss://客服网关域名" @input="dirty = true" /></el-form-item><el-form-item label="Socket 路径"><el-input v-model="snapshot.settings.socket_path" placeholder="/customer-service" @input="dirty = true" /></el-form-item></div><el-form-item label="欢迎语"><el-input v-model="snapshot.settings.welcome_message" maxlength="1000" @input="dirty = true" /></el-form-item><el-form-item label="离线提示"><el-input v-model="snapshot.settings.offline_message" maxlength="1000" @input="dirty = true" /></el-form-item><el-form-item label="营业时间"><ScheduleEditor v-model="snapshot.settings.schedule" @update:model-value="dirty = true" /></el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane v-for="resource in resources" :key="resource" :name="resource" :label="resourceNames[resource]">
        <div class="resource-header"><p v-if="resource === 'categories'">最多三级。子类继承父类接待设置，父类强制人工不能被子类取消。</p><p v-else-if="resource === 'keyword_rules'">高优先级规则先匹配；同级冲突转人工处理。排除词优先于正向词。</p><p v-else-if="resource === 'ai_connections'">密钥变量须为 CUSTOMER_SERVICE_AI_*_KEY；服务端 CUSTOMER_SERVICE_AI_ALLOWED_HOSTS 须包含供应商域名。</p><p v-else-if="resource === 'agent_skills'">用类目和技能组匹配人工客服；空类目表示该组通用接待。</p><p v-else>配置将保存在当前草稿中，发布后生效。</p><el-button type="primary" :data-testid="`add-${resource}`" @click="edit(resource)">添加{{ resourceNames[resource] }}</el-button></div>
        <el-table :data="snapshot[resource]" row-key="id" empty-text="暂无配置"><el-table-column prop="id" label="编号" width="80" /><el-table-column label="名称 / 绑定" min-width="180"><template #default="{ row }">{{ resource === 'categories' ? categoryPath(snapshot.categories, row.id) : row.name || `坐席 #${row.agent_id} / 技能组 #${row.group_id}` }}</template></el-table-column><el-table-column v-if="resource === 'keyword_rules'" label="规则" min-width="200"><template #default="{ row }">{{ row.match_type }}: {{ row.include_terms?.join('、') }} → {{ categoryPath(snapshot.categories, row.category_id) }}</template></el-table-column><el-table-column v-if="resource === 'categories'" prop="depth" label="层级" width="70" /><el-table-column label="状态" width="80"><template #default="{ row }"><el-tag :type="row.enabled === false ? 'info' : 'success'">{{ row.enabled === false ? '停用' : '启用' }}</el-tag></template></el-table-column><el-table-column label="操作" width="150"><template #default="{ row }"><el-button link type="primary" @click="edit(resource, row)">编辑</el-button><el-button link type="danger" @click="remove(resource, row)">移除</el-button></template></el-table-column></el-table>
      </el-tab-pane>
      <el-tab-pane label="人工坐席" name="agents"><el-alert title="同技能匹配范围内，优先级数字越大越优先。加权负载策略中，权重越大分配越多。" :closable="false" /><el-table :data="agents"><el-table-column prop="name" label="坐席" /><el-table-column prop="status" label="在线状态" /><el-table-column prop="priority" label="优先级" /><el-table-column prop="weight" label="权重" /><el-table-column prop="max_conversations" label="接待上限" /><el-table-column label="操作"><template #default="{ row }"><el-button link type="primary" @click="editAgent(row)">配置</el-button></template></el-table-column></el-table></el-tab-pane>
      <el-tab-pane label="测试与发布记录" name="test"><el-form label-position="top"><el-form-item label="模拟用户问题"><el-input v-model="question" type="textarea" maxlength="2000" show-word-limit placeholder="输入实际的客户问题" /></el-form-item><div class="form-grid"><el-form-item label="用户已选类目（可选）"><el-select v-model="categoryId" clearable><el-option v-for="c in snapshot.categories" :key="c.id" :value="c.id" :label="categoryPath(snapshot.categories, c.id)" /></el-select></el-form-item><el-form-item label="试问 AI Agent"><el-select v-model="testAgentId"><el-option v-for="a in snapshot.ai_agents" :key="a.id" :value="a.id" :label="a.name" /></el-select></el-form-item></div><div class="actions"><el-button :loading="busy" :disabled="!question.trim()" @click="runTest(false)">模拟类目匹配</el-button><el-button type="primary" :loading="busy" :disabled="!question.trim() || !testAgentId" @click="runTest(true)">使用已配置模型试问</el-button></div><p class="hint">调用已配置模型，消耗 Token；不执行业务操作，不发送给客户，仅使用已发布 FAQ。</p><pre v-if="result" data-testid="simulation-result">{{ JSON.stringify(result, null, 2) }}</pre></el-form><el-divider /><h3>版本回滚</h3><div class="actions"><el-select v-model="revisionToRestore" placeholder="选择历史版本"><el-option v-for="history in (document?.history || []).filter(item => item.published)" :key="history.id" :value="history.id" :label="`v${history.id} · ${history.created_at || ''}`" /></el-select><el-button :disabled="!revisionToRestore" :loading="busy" @click="restore">回滚并发布</el-button></div><el-divider /><div class="resource-header"><h3>AI 运行记录</h3><el-button @click="loadRuns">加载运行记录</el-button></div><el-table :data="runs"><el-table-column prop="id" label="编号" width="80" /><el-table-column prop="status" label="状态" /><el-table-column prop="reason" label="原因" /><el-table-column prop="created_at" label="时间" /><el-table-column label="详情"><template #default="{ row }"><el-popover width="500" trigger="click"><pre>{{ JSON.stringify(row, null, 2) }}</pre><template #reference><el-button link>查看</el-button></template></el-popover></template></el-table-column></el-table></el-tab-pane>
    </el-tabs>
    <el-dialog v-model="dialog" :title="editing ? `编辑${resourceNames[editing]}` : ''" width="680px" destroy-on-close>
      <el-form v-if="snapshot" label-position="top" @submit.prevent>
        <div class="form-grid"><el-form-item label="名称" required><el-input v-model="entry.name" data-testid="entry-name" maxlength="60" /></el-form-item><el-form-item label="启用"><el-switch v-model="entry.enabled" /></el-form-item></div>
        <template v-if="editing === 'categories'"><el-form-item label="唯一编码（英文、数字、横线）" required><el-input v-model="entry.code" data-testid="category-code" /></el-form-item><el-form-item label="父级类目"><el-select v-model="entry.parent_id"><el-option :value="0" label="根类目" /><el-option v-for="c in snapshot.categories.filter(c => c.id !== entry.id)" :key="c.id" :value="c.id" :label="categoryPath(snapshot.categories, c.id)" /></el-select></el-form-item><div class="form-grid"><el-form-item label="排序"><el-input-number v-model="entry.sort" :precision="0" /></el-form-item><el-form-item label="排队优先级（空值继承父级）"><el-input-number v-model="entry.priority" :precision="0" /></el-form-item><el-form-item label="强制人工接待"><el-switch v-model="entry.force_human" /></el-form-item></div><el-form-item label="默认 AI Agent（空值继承）"><el-select v-model="entry.default_ai_agent_id" clearable><el-option v-for="a in snapshot.ai_agents" :key="a.id" :value="a.id" :label="a.name" /></el-select></el-form-item><el-form-item label="默认技能组（空值继承）"><el-select v-model="entry.default_group_id" clearable><el-option v-for="g in snapshot.skill_groups" :key="g.id" :value="g.id" :label="g.name" /></el-select></el-form-item></template>
        <template v-if="editing === 'keyword_rules' || editing === 'agent_skills'"><el-form-item :label="editing === 'keyword_rules' ? '目标类目' : '擅长类目（留空为通用）'"><el-select v-model="entry.category_id" clearable filterable><el-option v-for="c in snapshot.categories" :key="c.id" :value="c.id" :label="categoryPath(snapshot.categories, c.id)" /></el-select></el-form-item></template>
        <template v-if="editing === 'keyword_rules'"><el-form-item label="匹配方式"><el-select v-model="entry.match_type"><el-option label="完整问题精确匹配" value="exact" /><el-option label="包含任一词" value="contains" /><el-option label="任一关键词" value="any" /><el-option label="全部关键词" value="all" /></el-select></el-form-item><el-form-item label="正向词（输入后回车）" required><el-select v-model="entry.include_terms" multiple filterable allow-create default-first-option :reserve-keyword="false" /></el-form-item><el-form-item label="排除词（输入后回车）"><el-select v-model="entry.exclude_terms" multiple filterable allow-create default-first-option :reserve-keyword="false" /></el-form-item><el-form-item label="规则优先级"><el-input-number v-model="entry.priority" :precision="0" /></el-form-item></template>
        <template v-if="editing === 'agent_skills'"><el-form-item label="人工坐席" required><el-select v-model="entry.agent_id" filterable><el-option v-for="a in agents" :key="a.id" :value="a.id" :label="a.name" /></el-select></el-form-item><el-form-item label="技能组" required><el-select v-model="entry.group_id"><el-option v-for="g in snapshot.skill_groups" :key="g.id" :value="g.id" :label="g.name" /></el-select></el-form-item><el-form-item label="包含下级类目"><el-switch v-model="entry.include_descendants" /></el-form-item></template>
        <el-form-item v-if="editing === 'skill_groups' || editing === 'ai_agents'" label="兜底技能组"><el-select v-model="entry.fallback_group_id" clearable><el-option v-for="g in snapshot.skill_groups.filter(g => editing !== 'skill_groups' || g.id !== entry.id)" :key="g.id" :value="g.id" :label="g.name" /></el-select></el-form-item>
        <template v-if="editing === 'ai_connections'"><el-form-item label="兼容 OpenAI 的 HTTPS 基础地址" required><el-input v-model="entry.endpoint" placeholder="https://供应商域名/v1" /></el-form-item><el-form-item label="服务端密钥环境变量名" required><el-input v-model="entry.credential_env" placeholder="CUSTOMER_SERVICE_AI_PROVIDER_KEY" /></el-form-item></template>
        <template v-if="editing === 'ai_agents'"><el-form-item label="AI 连接" required><el-select v-model="entry.connection_id"><el-option v-for="c in snapshot.ai_connections" :key="c.id" :value="c.id" :label="c.name" /></el-select></el-form-item><el-form-item label="模型名称" required><el-input v-model="entry.model" placeholder="填写供应商提供的模型 ID" /></el-form-item><el-form-item label="角色说明" required><el-input v-model="entry.prompt" type="textarea" :rows="5" maxlength="4000" /></el-form-item><div class="form-grid"><el-form-item label="知识检索阈值"><el-input-number v-model="entry.retrieval_threshold" :min="0.2" :max="1" :step="0.05" /></el-form-item><el-form-item label="回复 Token 上限"><el-input-number v-model="entry.max_output_tokens" :min="128" :max="4096" :precision="0" /></el-form-item></div><el-form-item label="服务类目范围"><el-select v-model="entry.category_ids" multiple clearable><el-option v-for="c in snapshot.categories" :key="c.id" :value="c.id" :label="categoryPath(snapshot.categories, c.id)" /></el-select></el-form-item><el-form-item label="允许调用的工具"><el-checkbox-group v-model="entry.tool_allowlist"><el-checkbox value="order_status">查询当前用户订单状态</el-checkbox></el-checkbox-group></el-form-item></template>
      </el-form><template #footer><el-button @click="dialog = false">取消</el-button><el-button data-testid="apply-entry" type="primary" @click="applyEntry">加入草稿</el-button></template>
    </el-dialog>
    <el-dialog v-model="agentDialog" title="坐席接待配置（保存后立即生效）" width="680px"><el-form label-position="top"><el-form-item label="坐席名称"><el-input v-model="agentForm.name" /></el-form-item><div class="form-grid"><el-form-item label="参与接待"><el-switch v-model="agentForm.enabled" /></el-form-item><el-form-item label="优先级"><el-input-number v-model="agentForm.priority" :precision="0" /></el-form-item><el-form-item label="权重"><el-input-number v-model="agentForm.weight" :min="1" :max="100" :precision="0" /></el-form-item><el-form-item label="最大同时接待数"><el-input-number v-model="agentForm.max_conversations" :min="1" :precision="0" /></el-form-item></div><el-form-item label="坐席排班"><ScheduleEditor v-model="agentForm.schedule_json" /></el-form-item></el-form><template #footer><el-button @click="agentDialog = false">取消</el-button><el-button :loading="busy" type="primary" @click="saveAgent">保存坐席</el-button></template></el-dialog>
  </section>
</template>
<style scoped>.support-config { padding:16px; }.config-header,.resource-header,.actions { display:flex; align-items:center; gap:12px; }.config-header,.resource-header { justify-content:space-between; flex-wrap:wrap; margin-bottom:18px; }.config-header h2 { margin:0 0 8px; }.config-header p,.resource-header p,.hint { color:var(--el-text-color-secondary); font-size:13px; }.actions { flex-wrap:wrap; }.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:4px 24px; }.support-config :deep(.el-select) { min-width:180px; }.error-banner { margin-bottom:16px; }pre { max-height:360px; overflow:auto; white-space:pre-wrap; word-break:break-word; background:var(--el-fill-color-light); padding:12px; border-radius:8px; }.support-config :deep(.el-form-item__content) { min-width:0; }</style>
