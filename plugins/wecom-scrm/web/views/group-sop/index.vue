<script setup lang="ts">
import { onMounted, ref } from 'vue'
const rows = ref<any[]>([])
const loading = ref(false)
async function load() { loading.value = true; const result = await useHttp().get('/admin/wecom-scrm/group-sops'); rows.value = result.data?.list ?? []; loading.value = false }
onMounted(load)
</script>
<template><div class="p-4"><ElCard shadow="never"><div class="toolbar"><ElButton type="primary" @click="load">刷新</ElButton></div><ElTable v-loading="loading" :data="rows" stripe><ElTableColumn prop="name" label="SOP 名称" /><ElTableColumn prop="trigger" label="触发事件" /><ElTableColumn prop="enabled" label="状态"><template #default="{ row }"><ElTag :type="row.enabled ? 'success' : 'info'">{{ row.enabled ? '启用' : '停用' }}</ElTag></template></ElTableColumn><ElTableColumn prop="created_at" label="创建时间" /></ElTable></ElCard></div></template>
