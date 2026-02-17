import type { MaProTableColumns } from '@mineadmin/pro-table'
import type { SeckillOrderSummaryVo } from '~/mall/api/seckill-order'

import { ElTag, ElButton } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { formatYuan } from '@/utils/price'

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
}

export default function getTableColumns(onViewOrders: (row: SeckillOrderSummaryVo) => void): MaProTableColumns[] {
  const { t } = useI18n()

  const statusTextMap: Record<string, string> = {
    pending: t('mall.activityStatus.pending'),
    active: t('mall.activityStatus.active'),
    ended: t('mall.activityStatus.ended'),
    cancelled: t('mall.activityStatus.cancelled'),
  }

  return [
    { label: () => 'ID', prop: 'id', width: '70px' },
    { label: () => t('mall.seckillOrder.activityName'), prop: 'title', minWidth: '180px' },
    {
      label: () => t('mall.common.status'),
      prop: 'status',
      width: '90px',
      cellRender: (({ row }: any) => (
        <ElTag type={statusTypeMap[row.status] as any} size="small">
          {statusTextMap[row.status] || row.status}
        </ElTag>
      )) as any,
    },
    {
      label: () => t('mall.seckillOrder.enabledLabel'),
      prop: 'is_enabled',
      width: '70px',
      cellRender: (({ row }: any) => (
        <ElTag type={row.is_enabled ? 'success' : 'info'} size="small">
          {row.is_enabled ? t('mall.seckillOrder.yes') : t('mall.seckillOrder.no')}
        </ElTag>
      )) as any,
    },
    { label: () => t('mall.seckillOrder.sessionsCount'), prop: 'sessions_count', width: '80px' },
    {
      label: () => t('mall.seckillOrder.buyerCount'),
      prop: 'buyer_count',
      width: '90px',
      cellRender: (({ row }: any) => (
        <span class="font-semibold">{row.buyer_count}</span>
      )) as any,
    },
    {
      label: () => t('mall.seckillOrder.totalAmount'),
      prop: 'total_amount',
      width: '120px',
      cellRender: (({ row }: any) => (
        <span class="text-red-500 font-semibold">¥{formatYuan(row.total_amount)}</span>
      )) as any,
    },
    { label: () => t('mall.seckillOrder.createdAt'), prop: 'created_at', width: '160px' },
    {
      label: () => t('mall.seckillOrder.operation'),
      prop: 'action',
      width: '120px',
      cellRender: (({ row }: any) => (
        <ElButton type="primary" link size="small" onClick={() => onViewOrders(row)}>
          {t('mall.seckillOrder.viewOrders')}
        </ElButton>
      )) as any,
    },
  ]
}
