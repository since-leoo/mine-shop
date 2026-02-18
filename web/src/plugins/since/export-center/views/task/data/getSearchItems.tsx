import type { MaSearchItem } from '@mineadmin/search'
import { useI18n } from 'vue-i18n'

export default function getSearchItems(): MaSearchItem[] {
  const { t } = useI18n()
  return [
    {
      label: () => t('export.status'),
      prop: 'status',
      render: () => (
        <el-select clearable placeholder={t('export.allStatus')}>
          <el-option label={t('export.statusPending')} value="pending" />
          <el-option label={t('export.statusProcessing')} value="processing" />
          <el-option label={t('export.statusSuccess')} value="success" />
          <el-option label={t('export.statusFailed')} value="failed" />
          <el-option label={t('export.statusExpired')} value="expired" />
        </el-select>
      ),
    },
    {
      label: () => t('export.timeRange'),
      prop: 'created_at',
      render: () => (
        <el-date-picker
          type="daterange"
          value-format="YYYY-MM-DD"
          start-placeholder={t('export.startDate')}
          end-placeholder={t('export.endDate')}
          style="width: 100%"
        />
      ),
    },
  ]
}
