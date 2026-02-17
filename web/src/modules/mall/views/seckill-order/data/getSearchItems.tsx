import type { MaSearchItem } from '@mineadmin/search'
import { useI18n } from 'vue-i18n'

export default function getSearchItems(): MaSearchItem[] {
  const { t } = useI18n()
  return [
    {
      label: () => t('mall.seckillOrder.activityName'),
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: t('mall.groupBuy.searchActivity') },
    },
    {
      label: () => t('mall.common.status'),
      prop: 'status',
      render: () => (
        <el-select clearable placeholder={t('mall.allStatus')}>
          <el-option label={t('mall.activityStatus.pending')} value="pending" />
          <el-option label={t('mall.activityStatus.active')} value="active" />
          <el-option label={t('mall.activityStatus.ended')} value="ended" />
          <el-option label={t('mall.activityStatus.cancelled')} value="cancelled" />
        </el-select>
      ),
    },
  ]
}
