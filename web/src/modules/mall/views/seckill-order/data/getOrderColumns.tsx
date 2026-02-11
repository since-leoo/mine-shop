import type { MaProTableColumns } from '@mineadmin/pro-table'

import { ElTag } from 'element-plus'
import { formatYuan } from '@/utils/price'

const orderStatusMap: Record<string, { text: string, type: string }> = {
  pending: { text: '待付款', type: 'warning' },
  paid: { text: '已付款', type: 'success' },
  cancelled: { text: '已取消', type: 'info' },
}

export default function getOrderColumns(): MaProTableColumns[] {
  return [
    { label: () => '会员', prop: 'member_nickname', width: '100px' },
    { label: () => '手机号', prop: 'member_phone', width: '120px' },
    { label: () => '场次', prop: 'session_time', width: '110px' },
    { label: () => '数量', prop: 'quantity', width: '60px' },
    {
      label: () => '秒杀价',
      prop: 'seckill_price',
      width: '90px',
      cellRender: (({ row }: any) => (
        <span class="text-red-500">¥{formatYuan(row.seckill_price)}</span>
      )) as any,
    },
    {
      label: () => '实付金额',
      prop: 'total_amount',
      width: '100px',
      cellRender: (({ row }: any) => (
        <span class="font-semibold">¥{formatYuan(row.total_amount)}</span>
      )) as any,
    },
    {
      label: () => '状态',
      prop: 'status',
      width: '90px',
      cellRender: (({ row }: any) => {
        const s = orderStatusMap[row.status] || { text: row.status, type: 'info' }
        return <ElTag type={s.type as any} size="small">{s.text}</ElTag>
      }) as any,
    },
    { label: () => '关联订单', prop: 'order_no', width: '180px' },
    { label: () => '秒杀时间', prop: 'seckill_time', width: '160px' },
  ]
}
