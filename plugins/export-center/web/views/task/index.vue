<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { page } from '../../api/export'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'

defineOptions({ name: 'export:task' })

const { t } = useI18n()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 140,
  header: {
    mainTitle: () => t('export.title'),
    subTitle: () => t('export.subtitle'),
  },
  searchOptions: {
    fold: true,
  },
  searchFormOptions: { labelWidth: '100px' },
  requestOptions: { api: page },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(proTableRef),
})
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="proTableRef" :options="options" :schema="schema" />
  </div>
</template>
