<script setup lang="ts">
import { ref } from 'vue'
const userid = ref('')
const rows = ref<any[]>([])
const loading = ref(false)
async function search() { loading.value = true; const result = await useHttp().get('/admin/wecom-scrm/customers', { params: { userid: userid.value } }); rows.value = result.data?.external_userid ? [result.data] : (result.data ?? []); loading.value = false }
</script>
<template><div class="p-4"><ElCard shadow="never"><div class="toolbar"><ElInput v-model="userid" placeholder="员工 userid" clearable /><ElButton type="primary" @click="search">查询客户</ElButton></div><ElTable v-loading="loading" :data="rows" stripe><ElTableColumn prop="external_userid" label="外部联系人 ID" /><ElTableColumn prop="name" label="客户名称" /><ElTableColumn prop="type" label="客户类型" /><ElTableColumn prop="gender" label="性别" /><ElTableColumn prop="follow_user" label="跟进员工" /></ElTable></ElCard></div></template>
