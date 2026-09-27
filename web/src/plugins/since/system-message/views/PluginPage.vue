<script setup lang="ts">
import { computed, inject, provide, ref } from 'vue'
import { useRoute } from 'vue-router'
import AdminDashboard from './admin/AdminDashboard.vue'
import AdminMessageList from './admin/AdminMessageList.vue'
import AdminTemplateList from './admin/AdminTemplateList.vue'
import NotificationSettings from './NotificationSettings.vue'
import AdminTemplateForm from './admin/AdminTemplateForm.vue'

defineOptions({ name: 'system-message:plugin-center' })

const route = useRoute()
const active = ref(String(route.query.tab ?? 'dashboard'))
const templateRequest = ref<{ template: string; id?: string; duplicate?: string } | null>(route.query.template ? { template: String(route.query.template), id: route.query.id ? String(route.query.id) : undefined, duplicate: route.query.duplicate ? String(route.query.duplicate) : undefined } : null)
const editingTemplate = computed(() => templateRequest.value !== null)
const templateFormKey = computed(() => templateRequest.value ? `${templateRequest.value.template}:${templateRequest.value.id ?? ''}:${templateRequest.value.duplicate ?? ''}` : '')
provide('system-message-open-template', (request: { template: string; id?: string; duplicate?: string }) => { templateRequest.value = request; active.value = 'templates' })
function closeTemplateForm() { templateRequest.value = null; active.value = 'templates' }
const tabs = computed(() => [
  { key: 'dashboard', label: '消息概览', component: AdminDashboard },
  { key: 'messages', label: '消息管理', component: AdminMessageList },
  { key: 'templates', label: '模板管理', component: AdminTemplateList },
  { key: 'settings', label: '通知偏好', component: NotificationSettings },
])
</script>

<template>
  <div class="system-message-plugin h-full min-h-0 flex flex-col rounded-lg bg-[var(--el-bg-color-page)] p-4">
    <el-tabs v-if="!editingTemplate" v-model="active" class="message-tabs shrink-0 px-1" stretch>
      <el-tab-pane v-for="tab in tabs" :key="tab.key" :name="tab.key" :label="tab.label" />
    </el-tabs>
    <div class="plugin-content min-h-0 flex-1 overflow-auto rounded-lg bg-[var(--el-bg-color)] p-4">
      <AdminTemplateForm v-if="editingTemplate" :key="templateFormKey" @close="closeTemplateForm" />
      <component v-if="!editingTemplate" :is="tabs.find(tab => tab.key === active)?.component" />
    </div>
  </div>
</template>

<style scoped>
.system-message-plugin {
  --el-bg-color-page: #f6f8fc;
  --el-bg-color: #fff;
  --el-fill-color-blank: #fff;
  background: var(--el-bg-color-page);
}
.message-tabs { background: var(--el-bg-color); border-bottom: 1px solid var(--el-border-color-lighter); }
.message-tabs :deep(.el-tabs__header) { margin: 0; }
.message-tabs :deep(.el-tabs__nav-wrap::after) { display: none; }
</style>

<style lang="scss">
html.dark .system-message-plugin {
  --el-bg-color-page: #0d1420;
  --el-bg-color: #161e2d;
  --el-fill-color-blank: #161e2d;
  --el-fill-color-light: #202a3a;
  --el-border-color-lighter: #2a3448;
  color-scheme: dark;
}
html.dark .system-message-plugin .message-tabs .el-tabs__item { color: #aab5c8; }
html.dark .system-message-plugin .message-tabs .el-tabs__item.is-active { color: #8f84ff; }
</style>
