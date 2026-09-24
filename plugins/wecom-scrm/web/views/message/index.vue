<script setup lang="ts">
import { ref } from 'vue'
const touser = ref('')
const content = ref('')
const type = ref<'text' | 'markdown'>('text')
const sending = ref(false)
async function send() { sending.value = true; const users = touser.value.split('|').map(item => item.trim()).filter(Boolean); await useHttp().post(`/admin/wecom-scrm/messages/${type.value}`, { touser: users, content: content.value }); sending.value = false; content.value = '' }
</script>
<template><div class="p-4"><ElCard shadow="never"><ElForm label-width="100px"><ElFormItem label="消息类型"><ElRadioGroup v-model="type"><ElRadio value="text">文本</ElRadio><ElRadio value="markdown">Markdown</ElRadio></ElRadioGroup></ElFormItem><ElFormItem label="成员 userid"><ElInput v-model="touser" placeholder="多个成员使用 | 分隔" /></ElFormItem><ElFormItem label="消息内容"><ElInput v-model="content" type="textarea" :rows="8" /></ElFormItem><ElButton type="primary" :loading="sending" @click="send">发送消息</ElButton></ElForm></ElCard></div></template>
