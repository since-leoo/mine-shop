<template>
  <div class="mine-layout config-setting-page">
    <SettingGroupForm
      group-key="content"
      :title="t('mall.config.contentTitle')"
      :description="t('mall.config.contentDesc')"
    />
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import SettingGroupForm from '@/modules/system/components/SettingGroupForm.vue'

const { t } = useI18n()
</script>
