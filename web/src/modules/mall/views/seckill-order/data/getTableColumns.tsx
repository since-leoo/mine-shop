import type { MaProTableColumns } from '@mineadmin/pro-table'
import type { SeckillOrderSummaryVo } from '~/mall/api/seckill-order'

import { ElTag, ElButton } from 'element-plus'
import { formatYuan } from '@/utils/price'

const statusTextMap: Record<string, string> = {
  pending: '待开始',
  active: '进行中',
  ended: '已结束',
  cancelled: '已取消',
}

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
}

export default function getTableColumns(onViewOrders: (row: SeckillOrderSummaryVo) => void): MaProTableColumns[] {
  return [
    { label: () => 'ID', prop: 'id', width: '70px' },
    { label: () => '活动名称', prop: 'title', minWidth: '180px' },
    {
      label: () => '状态',
      prop: 'status',
      width: '90px',
      cellRender: (({ row }: any) => (
        <ElTag type={statusTypeMap[row.status] as any} size="small">
          {statusTextMap[row.status] || row.status}
        </ElTag>
      )) as any,
    },
    {
      label: () => '启用',
      prop: 'is_enabled',
      width: '70px',
      cellRender: (({ row }: any) => (
        <ElTag type={row.is_enabled ? 'success' : 'info'} size="small">
          {row.is_enabled ? '是' : '否'}
        </ElTag>
      )) as any,
    },
    { label: () => '场次数', prop: 'sessions_count', width: '80px' },
    {
      label: () => '秒杀人数',
      prop: 'buyer_count',
      width: '90px',
      cellRender: (({ row }: any) => (
        <span class="font-semibold">{row.buyer_count}</span>
      )) as any,
    },
    {
      label: () => '秒杀总金额',
      prop: 'total_amount',
      width: '120px',
      cellRender: (({ row }: any) => (
        <span class="text-red-500 font-semibold">¥{formatYuan(row.total_amount)}</span>
      )) as any,
    },
    { label: () => '创建时间', prop: 'created_at', width: '160px' },
    {
      label: () => '操作',
      prop: 'action',
      width: '120px',
      fixed: 'right',
      cellRender: (({ row }: any) => (
        <ElButton type="primary" link size="small" onClick={() => onViewOrders(row)}>
          查看秒杀订单
        </ElButton>
      )) as any,
    },
  ]
}
