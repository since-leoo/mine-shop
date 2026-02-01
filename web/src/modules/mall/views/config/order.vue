<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="order"
      title="订单与售后"
      description="订单生命周期、风控及售后配置。"
    />
  </div>
</template>

<script setup lang="ts">
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'
</script>
