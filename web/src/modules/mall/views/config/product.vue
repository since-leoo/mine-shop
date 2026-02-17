<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="product"
      :title="t('mall.config.productTitle')"
      :description="t('mall.config.productDesc')"
    />
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'

const { t } = useI18n()
</script>
