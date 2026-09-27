<script setup lang="ts">
import { computed, ref } from 'vue'
import Workbench from './workbench/index.vue'
import Faq from './faq/index.vue'

defineOptions({ name: 'customer-service:plugin-center' })

const active = ref('workbench')
const tabs = computed(() => [
  { key: 'workbench', label: '客服工作台', component: Workbench },
  { key: 'faq', label: '常见问题', component: Faq },
])
</script>

<template>
  <div class="customer-service-plugin h-full min-h-0 flex flex-col rounded-lg bg-[var(--el-bg-color-page)] p-4">
    <el-tabs v-model="active" class="service-tabs shrink-0 px-1" stretch>
      <el-tab-pane v-for="tab in tabs" :key="tab.key" :name="tab.key" :label="tab.label" />
    </el-tabs>
    <div class="plugin-content min-h-0 flex-1 overflow-auto rounded-lg bg-[var(--el-bg-color)] p-4">
      <keep-alive>
        <component :is="tabs.find(tab => tab.key === active)?.component" />
      </keep-alive>
    </div>
  </div>
</template>

<style scoped>
.customer-service-plugin {
  --el-bg-color-page: #f6f8fc;
  --el-bg-color: #fff;
  --el-fill-color-blank: #fff;
  background: var(--el-bg-color-page, #f6f8fc);
}
.service-tabs { background: var(--el-bg-color, #fff); border-bottom: 1px solid var(--el-border-color-lighter); }
.service-tabs :deep(.el-tabs__header) { margin: 0; }
.service-tabs :deep(.el-tabs__nav-wrap::after) { display: none; }
:global(html.dark) .customer-service-plugin {
  --el-bg-color-page: #0d1420;
  --el-bg-color: #161e2d;
  --el-fill-color-blank: #161e2d;
  --el-fill-color-light: #202a3a;
  --el-border-color: #2a3448;
  --el-border-color-light: #303b50;
  --el-border-color-lighter: #2a3448;
  color-scheme: dark;
}
:global(html.dark) .service-tabs { color: #edf1fa; }
:global(html.dark) .service-tabs :deep(.el-tabs__item) { color: #aab5c8; }
:global(html.dark) .service-tabs :deep(.el-tabs__item.is-active) { color: #8f84ff; }
</style>

<style lang="scss">
html.dark .customer-service-plugin {
  --el-bg-color-page: #0d1420;
  --el-bg-color: #161e2d;
  --el-fill-color-blank: #161e2d;
  --el-fill-color-light: #202a3a;
  --el-border-color: #2a3448;
  --el-border-color-light: #303b50;
  --el-border-color-lighter: #2a3448;
  color-scheme: dark;
}

html.dark .customer-service-plugin .service-tabs {
  color: #edf1fa;
}

html.dark .customer-service-plugin .service-tabs .el-tabs__item {
  color: #aab5c8;
}

html.dark .customer-service-plugin .service-tabs .el-tabs__item.is-active {
  color: #8f84ff;
}
</style>
