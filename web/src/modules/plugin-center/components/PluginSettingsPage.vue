<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

defineOptions({ name: 'PluginSettingsPage' })
type Setting = { key: string; label: string; description?: string; type?: string; is_sensitive?: boolean; value?: any; meta?: Record<string, any> }
const route = useRoute()
const props = defineProps<{ pluginName?: string }>()
const settings = ref<Setting[]>([])
const loading = ref(false)
const saving = ref<string | null>(null)
const pluginName = computed(() => props.pluginName || String(route.params.pluginName))
function fields(item: Setting): any[] { return Array.isArray(item.meta?.fields) ? item.meta.fields : [] }
function isJson(item: Setting): boolean { return item.type === 'json' && fields(item).length > 0 }
function componentType(field: any): string { return field.component ?? (field.input_type === 'password' ? 'password' : 'input') }

async function load() {
  loading.value = true
  try {
    const res = await useHttp().get('/admin/plugin-center/plugins/config', { params: { name: pluginName.value } })
    settings.value = (res.data || []).map((item: Setting) => ({ ...item, value: item.value ?? (item.type === 'json' ? {} : '') }))
  }
  finally { loading.value = false }
}
async function save(item: Setting) {
  saving.value = item.key
  try { await useHttp().put(`/admin/plugin-center/plugins/config/${encodeURIComponent(item.key)}`, { name: pluginName.value, value: item.value }); ElMessage.success('配置已保存') }
  finally { saving.value = null }
}
onMounted(load)
</script>

<template>
  <el-card v-loading="loading" shadow="never" class="plugin-settings-card">
    <template #header><div class="settings-heading"><strong>插件配置</strong><span>配置由插件独立管理，保存后立即生效</span></div></template>
    <el-empty v-if="!settings.length && !loading" description="该插件暂无可配置项" />
    <div v-for="item in settings" :key="item.key" class="setting-row">
      <div class="setting-copy"><strong>{{ item.label }}</strong><p>{{ item.description }}</p></div>
      <div v-if="isJson(item)" class="json-fields setting-control">
        <div v-for="field in fields(item)" :key="field.key" class="json-field">
          <label>{{ field.label }}</label>
          <el-switch v-if="componentType(field) === 'switch'" v-model="item.value[field.key]" />
          <el-select v-else-if="componentType(field) === 'select'" v-model="item.value[field.key]" class="w-full">
            <el-option v-for="option in field.options || []" :key="option.value" :label="option.label" :value="option.value" />
          </el-select>
          <el-input-number v-else-if="componentType(field) === 'number'" v-model="item.value[field.key]" class="w-full" />
          <el-input v-else v-model="item.value[field.key]" :type="componentType(field) === 'password' ? 'password' : 'text'" :show-password="componentType(field) === 'password'" :placeholder="field.placeholder" />
        </div>
      </div>
      <el-input v-else-if="item.type === 'string' || item.is_sensitive" v-model="item.value" show-password class="setting-control" />
      <el-input-number v-else-if="item.type === 'integer' || item.type === 'number'" v-model="item.value" class="setting-control" />
      <el-switch v-else-if="item.type === 'boolean'" v-model="item.value" class="setting-control" />
      <el-input v-else v-model="item.value" type="textarea" class="setting-control" />
      <el-button type="primary" :loading="saving === item.key" @click="save(item)">保存</el-button>
    </div>
  </el-card>
</template>

<style scoped lang="scss">
.plugin-settings-card { max-width: 920px; border-radius: 12px; }
.settings-heading { display: flex; align-items: baseline; gap: 12px; }
.settings-heading span, .setting-copy p { color: var(--el-text-color-secondary); font-size: 12px; }
.setting-row { display: flex; align-items: center; gap: 18px; padding: 18px 0; border-bottom: 1px solid var(--el-border-color-lighter); }
.setting-copy { flex: 1; min-width: 220px; }.setting-copy p { margin: 6px 0 0; }.setting-control { width: 320px; }
.json-fields { display: flex; flex-direction: column; gap: 10px; }.json-field { display: flex; align-items: center; gap: 10px; }.json-field label { width: 100px; color: var(--el-text-color-secondary); font-size: 12px; }.json-field .el-input, .json-field .el-select, .json-field .el-input-number { flex: 1; }
@media (max-width: 700px) { .setting-row { align-items: stretch; flex-direction: column; gap: 10px; }.setting-control { width: 100%; } }
</style>
