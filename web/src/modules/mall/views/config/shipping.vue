<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="shipping"
      :title="t('mall.config.shippingTitle')"
      :description="t('mall.config.shippingDesc')"
    />
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'

const { t } = useI18n()
</script>
