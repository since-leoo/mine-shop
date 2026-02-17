<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="payment"
      :title="t('mall.config.paymentTitle')"
      :description="t('mall.config.paymentDesc')"
    />
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'

const { t } = useI18n()
</script>
