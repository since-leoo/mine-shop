<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="content"
      title="内容与合规"
      description="协议、公告以及内容审核策略。"
    />
  </div>
</template>

<script setup lang="ts">
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'
</script>
