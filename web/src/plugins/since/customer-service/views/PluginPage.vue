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
.customer-service-plugin { background: var(--el-bg-color-page, #f6f8fc); }
.service-tabs { background: var(--el-bg-color, #fff); border-bottom: 1px solid var(--el-border-color-lighter); }
.service-tabs :deep(.el-tabs__header) { margin: 0; }
.service-tabs :deep(.el-tabs__nav-wrap::after) { display: none; }
</style>
