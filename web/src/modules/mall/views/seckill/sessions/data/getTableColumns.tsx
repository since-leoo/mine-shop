/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { SeckillSessionVo } from '~/mall/api/seckill'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElTag, ElSwitch, ElProgress } from 'element-plus'
import { sessionRemove, sessionToggleStatus } from '~/mall/api/seckill'
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

export default function getTableColumns(dialog: UseDialogExpose, router: any, tableRef: { value: MaProTableExpose | undefined }): MaProTableColumns[] {
  const msg = useMessage()

  return [
    { type: 'selection', showOverflowTooltip: false },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => '开始时间', prop: 'start_time', minWidth: '170px' },
    { label: () => '结束时间', prop: 'end_time', minWidth: '170px' },
    { label: () => '商品数', prop: 'products_count', minWidth: '100px',
      cellRender: ({ row }: { row: SeckillSessionVo }) => (
        <el-button
          type="primary"
          link
          onClick={() => router.push({
            path: '/mall/seckill/products',
            query: { session_id: row.id, activity_id: row.activity_id },
          })}
        >
          {row.products_count ?? 0} 个商品
        </el-button>
      ),
    },
    { label: () => '库存/售出', prop: 'quantity', minWidth: '180px',
      cellRender: ({ row }: { row: SeckillSessionVo }) => {
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
    { label: () => '限购', prop: 'max_quantity_per_user', width: '80px' },
    { label: () => '状态', prop: 'status', width: '100px',
      cellRender: ({ row }: { row: SeckillSessionVo }) => (
        <ElTag type={statusTypeMap[row.status || 'pending'] as any}>
          {statusTextMap[row.status || 'pending']}
        </ElTag>
      ),
    },
    { label: () => '启用', prop: 'is_enabled', width: '80px',
      cellRender: ({ row }: { row: SeckillSessionVo }) => (
        <ElSwitch
          modelValue={row.is_enabled}
          disabled={!hasAuth('seckill:session:update')}
          onChange={async () => {
            const res = await sessionToggleStatus(row.id as number)
            if (res.code === ResultCode.SUCCESS) {
              msg.success('状态切换成功')
              await tableRef.value?.refresh()
            }
          }}
        />
      ),
    },
    { label: () => '排序', prop: 'sort_order', width: '80px' },
    {
      type: 'operation',
      label: () => '操作',
      width: '220px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'products',
            show: () => hasAuth('seckill:product:list'),
            icon: 'material-symbols:inventory-2',
            text: () => '商品',
            onClick: ({ row }: { row: SeckillSessionVo }) => {
              router.push({
                path: '/mall/seckill/products',
                query: { session_id: row.id, activity_id: row.activity_id },
              })
            },
          },
          {
            name: 'edit',
            show: () => hasAuth('seckill:session:update'),
            icon: 'material-symbols:edit',
            text: () => '编辑',
            onClick: ({ row }: { row: SeckillSessionVo }) => {
              dialog.setTitle('编辑场次')
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('seckill:session:delete'),
            icon: 'mdi:delete',
            text: () => '删除',
            onClick: async ({ row }: { row: SeckillSessionVo }, proxy: MaProTableExpose) => {
              msg.delConfirm('确定删除该场次吗？场次下的商品配置也会被删除。').then(async () => {
                const response = await sessionRemove(row.id as number)
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
