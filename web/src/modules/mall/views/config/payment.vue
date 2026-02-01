<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="payment"
      title="支付与结算"
      description="支付方式及风控策略，满足微信/支付宝支付退款。"
    />
  </div>
</template>

<script setup lang="ts">
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'
</script>
