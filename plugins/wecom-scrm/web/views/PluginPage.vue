<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue'

defineOptions({ name: 'WecomScrmPluginPage' })

const active = ref('customer')
const sections = [
  { key: 'customer', label: '客户管理', icon: 'ant-design:user-outlined', component: defineAsyncComponent(() => import('./customer/index.vue')) },
  { key: 'tag', label: '标签管理', icon: 'ant-design:tags-outlined', component: defineAsyncComponent(() => import('./tag/index.vue')) },
  { key: 'tag-group', label: '标签组', icon: 'ant-design:appstore-outlined', component: defineAsyncComponent(() => import('./tag-group/index.vue')) },
  { key: 'group-chat', label: '客户群', icon: 'ant-design:team-outlined', component: defineAsyncComponent(() => import('./group-chat/index.vue')) },
  { key: 'group-sop', label: '群 SOP', icon: 'ant-design:ordered-list-outlined', component: defineAsyncComponent(() => import('./group-sop/index.vue')) },
  { key: 'binding', label: '用户绑定', icon: 'ant-design:link-outlined', component: defineAsyncComponent(() => import('./binding/index.vue')) },
  { key: 'message', label: '应用消息', icon: 'ant-design:message-outlined', component: defineAsyncComponent(() => import('./message/index.vue')) },
]
const current = computed(() => sections.find(item => item.key === active.value) ?? sections[0])
</script>

<template>
  <div class="wecom-page">
    <div class="page-intro">
      <div class="intro-icon"><ma-svg-icon name="ant-design:team-outlined" :size="28" /></div>
      <div><h2>企业微信 SCRM</h2><p>连接企业微信，统一维护客户关系、标签和群运营数据</p></div>
    </div>
    <el-card shadow="never" class="workspace-card">
      <el-tabs v-model="active" class="workspace-tabs">
        <el-tab-pane v-for="section in sections" :key="section.key" :name="section.key">
          <template #label><span class="tab-label"><ma-svg-icon :name="section.icon" :size="16" />{{ section.label }}</span></template>
        </el-tab-pane>
      </el-tabs>
      <div class="section-content"><component :is="current.component" /></div>
    </el-card>
  </div>
</template>

<style scoped lang="scss">
.wecom-page { min-height: 100%; padding: 4px 0 24px; }
.page-intro { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
.intro-icon { display: inline-flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 14px; background: var(--el-color-primary-light-9); color: var(--el-color-primary); }
h2 { margin: 0; color: var(--el-text-color-primary); font-size: 22px; }
.page-intro p { margin: 5px 0 0; color: var(--el-text-color-secondary); font-size: 13px; }
.workspace-card { border-radius: 12px; }
.workspace-tabs :deep(.el-tabs__header) { margin-bottom: 20px; }
.tab-label { display: inline-flex; align-items: center; gap: 6px; }
.section-content { min-height: 360px; }
.section-content :deep(> div) { padding: 0 !important; }
</style>
