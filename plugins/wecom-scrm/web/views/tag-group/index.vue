<script setup lang="ts">
import { onMounted, ref } from 'vue'
const rows = ref<any[]>([])
const loading = ref(false)
async function load() { loading.value = true; const result = await useHttp().get('/admin/wecom-scrm/tag-groups'); rows.value = result.data?.list ?? []; loading.value = false }
onMounted(load)
</script>
<template><div class="p-4"><ElCard shadow="never"><ElButton type="primary" @click="load">刷新</ElButton><ElTable v-loading="loading" :data="rows" stripe><ElTableColumn prop="name" label="标签组名称" /><ElTableColumn prop="group_id" label="企业微信标签组 ID" /><ElTableColumn prop="is_editable" label="可编辑" /><ElTableColumn prop="created_at" label="创建时间" /></ElTable></ElCard></div></template>
