<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="integration"
      title="系统集成"
      description="常用第三方服务接入配置。"
    />
  </div>
</template>

<script setup lang="ts">
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'
</script>
