<script setup lang="ts">
import type { Connection, ConnectionInput, ReturnCarriers } from '../api/supply-chain'
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import hasAuth from '@/utils/permission/hasAuth'
import { getConnections, saveConnection, testConnection } from '../api/supply-chain'
import { errorMessage, statusLabel, statusType } from './format'

const emit = defineEmits<{ saved: [] }>()
const canRead = computed(() => hasAuth('supply-chain:connections/read'))
const canUpdate = computed(() => hasAuth('supply-chain:connections/update'))
const canTest = computed(() => hasAuth('supply-chain:connections/test'))
const connections = ref<Connection[]>([])
const loading = ref(false)
const saving = ref(false)
const testing = ref<number>()
const error = ref('')
const dialog = ref(false)
const editingId = ref<number>()
const carrierJson = ref('{}')
const form = reactive<ConnectionInput>({ name: '', api_origin: '', app_key: '', app_secret: '', mode: 'pause_new' })

async function load() {
  if (!canRead.value) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await getConnections()
    connections.value = Array.isArray(data) ? data : data.list
  }
  catch (failure) { error.value = errorMessage(failure) }
  finally { loading.value = false }
}

function edit(row?: Connection) {
  editingId.value = row?.id
  Object.assign(form, { name: row?.name || '', api_origin: row?.api_origin || '', app_key: row?.app_key || '', app_secret: '', mode: row?.mode || 'pause_new' })
  carrierJson.value = JSON.stringify(row?.settings?.return_carriers || {}, null, 2)
  error.value = ''
  dialog.value = true
}

async function save() {
  if (!canUpdate.value || saving.value) return
  error.value = ''
  if (!form.name.trim() || !form.app_key.trim() || (!editingId.value && !form.app_secret?.trim())) {
    error.value = '请填写连接名称、AppKey 和新连接的 AppSecret'
    return
  }
  try {
    const url = new URL(form.api_origin.trim())
    if (url.protocol !== 'https:' || url.username || url.password || url.search || url.hash || !['', '/'].includes(url.pathname)) throw new Error('invalid')
  }
  catch {
    error.value = 'API 域名需为完整 HTTPS 地址，不包含路径、查询参数或账户密码'
    return
  }
  saving.value = true
  try {
    const data: ConnectionInput = { name: form.name.trim(), api_origin: form.api_origin.trim().replace(/\/$/, ''), app_key: form.app_key.trim(), mode: form.mode, settings: { return_carriers: parseCarriers() } }
    if (form.app_secret?.trim()) data.app_secret = form.app_secret.trim()
    await saveConnection(data, editingId.value)
    form.app_secret = ''
    dialog.value = false
    ElMessage.success('供应链连接已保存')
    emit('saved')
    await load()
  }
  catch (failure) { error.value = errorMessage(failure) }
  finally { saving.value = false }
}

function parseCarriers(): ReturnCarriers {
  let value: unknown
  try { value = JSON.parse(carrierJson.value.trim() || '{}') }
  catch { throw new Error('退货物流映射必须是有效 JSON 对象') }
  if (!value || typeof value !== 'object' || Array.isArray(value) || Object.keys(value).length > 100) {
    throw new Error('退货物流映射需为 JSON 对象，最多配置 100 家物流公司')
  }
  const entries: Array<[string, { name: string, code: string }]> = []
  for (const [localName, target] of Object.entries(value)) {
    const entry = target as { name?: unknown, code?: unknown } | null
    if (!localName.trim() || localName.length > 100 || !entry || Array.isArray(entry) || typeof entry !== 'object'
      || typeof entry.name !== 'string' || !entry.name.trim() || entry.name.length > 100
      || typeof entry.code !== 'string' || !entry.code.trim() || entry.code.length > 64
      || Object.keys(entry).some(key => !['name', 'code'].includes(key))) {
      throw new Error('退货物流映射格式无效：每个本地公司名称只允许 name、code 两个非空字符串字段')
    }
    if (entries.some(([key]) => key === localName.trim())) throw new Error('退货物流映射存在重复的本地公司名称')
    entries.push([localName.trim(), { name: entry.name.trim(), code: entry.code.trim() }])
  }
  return Object.fromEntries(entries)
}

async function test(row: Connection) {
  if (!canTest.value || testing.value) return
  testing.value = row.id
  error.value = ''
  try {
    const { data } = await testConnection(row.id)
    if (data?.success === false) throw new Error(data.message || '连接检测失败')
    ElMessage.success(data?.message || '供应链连接检测通过')
  }
  catch (failure) { error.value = errorMessage(failure) }
  finally { testing.value = undefined }
}
onMounted(load)
</script>

<template>
  <section v-loading="loading" class="supply-connections">
    <el-empty v-if="!canRead" description="暂无供应链连接查看权限" />
    <template v-else>
      <div class="toolbar">
        <div><h3>供应链连接</h3><p>每个连接独立管理凭证和运行状态。</p></div>
        <el-button v-if="canUpdate" type="primary" data-testid="new-connection" @click="edit()">新增连接</el-button>
        <el-button @click="load">刷新</el-button>
      </div>
      <el-alert v-if="error && !dialog" :title="error" type="error" :closable="false" show-icon />
      <el-alert title="暂停新业务：停止新的选品导入和交易接入；处理存量：继续处理既有订单、查询及补偿。停用前请先处理存量任务。" type="info" :closable="false" show-icon />
      <el-table :data="connections" row-key="id" empty-text="暂无连接，请先新增供应链连接">
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="连接名称" min-width="150" />
        <el-table-column prop="api_origin" label="API 域名" min-width="230" />
        <el-table-column label="凭证" width="130"><template #default="{ row }">{{ row.has_app_secret ? '已配置' : '待配置' }}</template></el-table-column>
        <el-table-column label="运行模式" width="140"><template #default="{ row }"><el-tag :type="statusType(row.mode)">{{ statusLabel(row.mode) }}</el-tag></template></el-table-column>
        <el-table-column label="操作" min-width="180"><template #default="{ row }">
          <el-button v-if="canUpdate" link type="primary" @click="edit(row as Connection)">编辑</el-button>
          <el-button v-if="canTest" link type="primary" :loading="testing === row.id" :disabled="!!testing && testing !== row.id" @click="test(row as Connection)">检测连接</el-button>
        </template></el-table-column>
      </el-table>
      <el-dialog v-model="dialog" :title="editingId ? '编辑供应链连接' : '新增供应链连接'" width="600px" :close-on-click-modal="false" @closed="form.app_secret = ''">
        <el-alert v-if="error" :title="error" type="error" :closable="false" show-icon />
        <el-form label-position="top" :disabled="saving" @submit.prevent="save">
          <el-form-item label="连接名称" required><el-input v-model="form.name" maxlength="80" data-testid="connection-name" /></el-form-item>
          <el-form-item label="API 域名" required><el-input v-model="form.api_origin" placeholder="https://supply.example.com" data-testid="connection-origin" /></el-form-item>
          <el-form-item label="AppKey" required><el-input v-model="form.app_key" autocomplete="off" data-testid="connection-key" /></el-form-item>
          <el-form-item label="AppSecret" :required="!editingId">
            <el-input v-model="form.app_secret" type="password" autocomplete="new-password" :placeholder="editingId ? '留空保留原密钥，原值不会回显' : '填写供应链 AppSecret'" data-testid="connection-secret" />
          </el-form-item>
          <el-form-item label="运行模式" required>
            <el-select v-model="form.mode" aria-label="运行模式">
              <el-option label="正常运行" value="active" /><el-option label="暂停新业务" value="pause_new" /><el-option label="处理存量" value="draining" />
            </el-select>
          </el-form-item>
          <el-collapse>
            <el-collapse-item title="高级设置：退货物流映射" name="return-carriers">
              <el-alert title="按供应商提供的物流名称与公司编码配置，不自动推测编码。未配置的公司不能向上游提交退货物流。" type="info" :closable="false" />
              <p class="carrier-hint">JSON 的键为商城填写的物流公司名称，值只包含供应商的 name 和 code。无需配置时保留 {}。</p>
              <el-input v-model="carrierJson" type="textarea" :rows="7" spellcheck="false" aria-label="退货物流映射 JSON" data-testid="return-carriers" />
            </el-collapse-item>
          </el-collapse>
        </el-form>
        <template #footer><el-button :disabled="saving" @click="dialog = false">取消</el-button><el-button type="primary" :loading="saving" data-testid="save-connection" @click="save">保存连接</el-button></template>
      </el-dialog>
    </template>
  </section>
</template>

<style scoped>
.toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.toolbar > div:first-child { flex: 1; }
h3 { margin: 0 0 6px; }
p { margin: 0; color: var(--el-text-color-secondary); }
.el-alert { margin-bottom: 16px; }
.el-select { width: 100%; }
.carrier-hint { margin-bottom: 12px; line-height: 1.7; }
</style>
