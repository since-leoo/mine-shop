<script setup lang="ts">
import { onMounted, ref } from 'vue'
const rows = ref<any[]>([])
const loading = ref(false)
async function load() { loading.value = true; const result = await useHttp().get('/admin/wecom-scrm/bindings'); rows.value = result.data?.list ?? []; loading.value = false }
onMounted(load)
</script>
<template><div class="p-4"><ElCard shadow="never"><div class="toolbar"><ElButton type="primary" @click="load">刷新</ElButton></div><ElTable v-loading="loading" :data="rows" stripe><ElTableColumn prop="user_id" label="系统用户 ID" /><ElTableColumn prop="wecom_userid" label="企业成员 ID" /><ElTableColumn prop="external_userid" label="外部联系人 ID" /><ElTableColumn prop="unionid" label="UnionID" /><ElTableColumn prop="source" label="绑定来源" /></ElTable></ElCard></div></template>
