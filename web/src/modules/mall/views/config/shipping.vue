<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="shipping"
      title="配送与物流"
      description="快递、自提及第三方物流相关配置。"
    />
  </div>
</template>

<script setup lang="ts">
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'
</script>
