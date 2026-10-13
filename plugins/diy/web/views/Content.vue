<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { useRoute } from 'vue-router'
import hasAuth from '@/utils/permission/hasAuth.ts'
import Pages from "./page/index.vue"
import Templates from "./template/index.vue"
const route = useRoute()
const editing = computed(() => route.query.view === 'editor')
const canEdit = computed(() => hasAuth('mall:diy:page:update'))
const Editor = defineAsyncComponent(() => import('./editor/index.vue'))
</script>
<template>
  <template v-if="editing">
    <!-- 切换页面 ID 时重新加载编辑器，避免把上一页的草稿保存到新页面。 -->
    <Editor v-if="canEdit" :key="String(route.query.id ?? '')" />
    <el-empty v-else description="暂无页面装修权限" />
  </template>
  <el-tabs v-else>
    <el-tab-pane label="装修页面"><Pages /></el-tab-pane>
    <el-tab-pane label="模板" lazy><Templates /></el-tab-pane>
  </el-tabs>
</template>
