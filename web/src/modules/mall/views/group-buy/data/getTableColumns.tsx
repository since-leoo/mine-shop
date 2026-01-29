/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { GroupBuyVo } from '~/mall/api/group-buy'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElTag, ElSwitch, ElImage, ElProgress } from 'element-plus'
import { remove, toggleStatus } from '~/mall/api/group-buy'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const statusTextMap: Record<string, string> = {
  pending: '待开始',
  active: '进行中',
  ended: '已结束',
  cancelled: '已取消',
  sold_out: '已售罄',
}

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
  sold_out: 'warning',
}

export default function getTableColumns(dialog: UseDialogExpose, tableRef: { value: MaProTableExpose | undefined }): MaProTableColumns[] {
  const msg = useMessage()

  return [
    { type: 'selection', showOverflowTooltip: false },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => '商品', prop: 'product', minWidth: '220px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <div class="flex items-center gap-2">
          {row.product?.main_image && <ElImage src={row.product.main_image} style="width: 40px; height: 40px" fit="cover" />}
          <div class="flex-1 min-w-0">
            <div class="font-medium truncate">{row.title}</div>
            <div class="text-xs text-gray-400 truncate">{row.product?.name || '-'}</div>
          </div>
        </div>
      ),
    },
    { label: () => '价格', prop: 'price', width: '140px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <div>
          <div class="text-red-500 font-semibold">¥{row.group_price}</div>
          <div class="text-xs text-gray-400 line-through">¥{row.original_price}</div>
        </div>
      ),
    },
    { label: () => '成团人数', prop: 'min_people', width: '100px',
      cellRender: ({ row }: { row: GroupBuyVo }) => `${row.min_people ?? 2}-${row.max_people ?? 10}人`,
    },
    { label: () => '库存/销量', prop: 'quantity', width: '160px',
      cellRender: ({ row }: { row: GroupBuyVo }) => {
        const total = row.total_quantity || 0
        const sold = row.sold_quantity || 0
        const percent = total > 0 ? Math.round((sold / total) * 100) : 0
        return (
          <div class="w-full">
            <div class="text-xs text-gray-500 mb-1">{sold} / {total}</div>
            <ElProgress percentage={percent} strokeWidth={6} showText={false} />
          </div>
        )
      },
    },
    { label: () => '状态', prop: 'status', width: '100px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <ElTag type={statusTypeMap[row.status || 'pending'] as any}>
          {statusTextMap[row.status || 'pending']}
        </ElTag>
      ),
    },
    { label: () => '启用', prop: 'is_enabled', width: '80px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <ElSwitch
          modelValue={row.is_enabled}
          disabled={!hasAuth('promotion:group_buy:update')}
          onChange={async () => {
            const res = await toggleStatus(row.id as number)
            if (res.code === ResultCode.SUCCESS) {
              msg.success('状态切换成功')
              await tableRef.value?.refresh()
            }
          }}
        />
      ),
    },
    { label: () => '时间', prop: 'time', width: '180px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <div class="text-xs">
          <div>开始: {row.start_time}</div>
          <div>结束: {row.end_time}</div>
        </div>
      ),
    },
    {
      type: 'operation',
      label: () => '操作',
      width: '150px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('promotion:group_buy:update'),
            icon: 'material-symbols:edit',
            text: () => '编辑',
            onClick: ({ row }: { row: GroupBuyVo }) => {
              dialog.setTitle('编辑团购')
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('promotion:group_buy:delete'),
            icon: 'mdi:delete',
            text: () => '删除',
            onClick: async ({ row }: { row: GroupBuyVo }, proxy: MaProTableExpose) => {
              msg.delConfirm('确定删除该团购活动吗？').then(async () => {
                const response = await remove(row.id as number)
                if (response.code === ResultCode.SUCCESS) {
                  msg.success('删除成功')
                  await proxy.refresh()
                }
              })
            },
          },
        ],
      },
    },
  ]
}
