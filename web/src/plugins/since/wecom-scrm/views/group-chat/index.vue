<script setup lang="ts">
import { onMounted, ref } from 'vue'
const rows = ref<any[]>([])
const loading = ref(false)
const chatId = ref('')
const content = ref('')
async function load() { loading.value = true; const result = await useHttp().get('/admin/wecom-scrm/group-chats'); rows.value = result.data?.chat_id_list ? result.data.chat_id_list.map((id: string) => ({ chat_id: id })) : (result.data ?? []); loading.value = false }
async function send() { await useHttp().post('/admin/wecom-scrm/group-chats/send', { chat_id: chatId.value, content: content.value }); content.value = '' }
onMounted(load)
</script>
<template><div class="p-4"><ElCard shadow="never"><div class="toolbar"><ElButton type="primary" @click="load">刷新群列表</ElButton><ElInput v-model="chatId" placeholder="群聊 ID" /><ElInput v-model="content" placeholder="群消息内容" /><ElButton type="success" @click="send">发送消息</ElButton></div><ElTable v-loading="loading" :data="rows" stripe><ElTableColumn prop="chat_id" label="群聊 ID" /><ElTableColumn prop="name" label="群名称" /><ElTableColumn prop="owner" label="群主" /><ElTableColumn prop="status" label="状态" /></ElTable></ElCard></div></template>
