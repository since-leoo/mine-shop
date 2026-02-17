import type { MaProTableColumns } from '@mineadmin/pro-table'

import { ElTag } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { formatYuan } from '@/utils/price'

export default function getOrderColumns(): MaProTableColumns[] {
  const { t } = useI18n()

  const orderStatusMap: Record<string, { text: string, type: string }> = {
    pending: { text: t('mall.seckillOrder.orderPending'), type: 'warning' },
    paid: { text: t('mall.seckillOrder.orderPaid'), type: 'success' },
    cancelled: { text: t('mall.seckillOrder.orderCancelled'), type: 'info' },
  }

  return [
    { label: () => t('mall.seckillOrder.memberLabel'), prop: 'member_nickname', width: '100px' },
    { label: () => t('mall.seckillOrder.phoneLabel'), prop: 'member_phone', width: '120px' },
    { label: () => t('mall.seckillOrder.sessionLabel'), prop: 'session_time', width: '110px' },
    { label: () => t('mall.seckillOrder.quantityLabel'), prop: 'quantity', width: '60px' },
    {
      label: () => t('mall.seckillOrder.seckillPrice'),
      prop: 'seckill_price',
      width: '90px',
      cellRender: (({ row }: any) => (
        <span class="text-red-500">¥{formatYuan(row.seckill_price)}</span>
      )) as any,
    },
    {
      label: () => t('mall.seckillOrder.paidAmount'),
      prop: 'total_amount',
      width: '100px',
      cellRender: (({ row }: any) => (
        <span class="font-semibold">¥{formatYuan(row.total_amount)}</span>
      )) as any,
    },
    {
      label: () => t('mall.common.status'),
      prop: 'status',
      width: '90px',
      cellRender: (({ row }: any) => {
        const s = orderStatusMap[row.status] || { text: row.status, type: 'info' }
        return <ElTag type={s.type as any} size="small">{s.text}</ElTag>
      }) as any,
    },
    { label: () => t('mall.seckillOrder.relatedOrder'), prop: 'order_no', width: '180px' },
    { label: () => t('mall.seckillOrder.seckillTime'), prop: 'seckill_time', width: '160px' },
  ]
}
