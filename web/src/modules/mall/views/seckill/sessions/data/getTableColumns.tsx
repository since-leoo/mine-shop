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
import { useI18n } from 'vue-i18n'
import { sessionRemove, sessionToggleStatus } from '~/mall/api/seckill'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
  sold_out: 'warning',
}

export default function getTableColumns(dialog: UseDialogExpose, router: any, tableRef: { value: MaProTableExpose | undefined }): MaProTableColumns[] {
  const msg = useMessage()
  const { t } = useI18n()

  const statusTextMap: Record<string, string> = {
    pending: t('mall.activityStatus.pending'),
    active: t('mall.activityStatus.active'),
    ended: t('mall.activityStatus.ended'),
    cancelled: t('mall.activityStatus.cancelled'),
    sold_out: t('mall.activityStatus.soldOut'),
  }

  return [
    { type: 'selection', showOverflowTooltip: false },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => t('mall.seckill.startTime'), prop: 'start_time', minWidth: '170px' },
    { label: () => t('mall.seckill.endTime'), prop: 'end_time', minWidth: '170px' },
    { label: () => t('mall.seckill.productsCount'), prop: 'products_count', minWidth: '100px',
      cellRender: ({ row }: { row: SeckillSessionVo }) => (
        <el-button
          type="primary"
          link
          onClick={() => router.push({
            path: '/mall/seckill/products',
            query: { session_id: row.id, activity_id: row.activity_id },
          })}
        >
          {row.products_count ?? 0} {t('mall.seckill.productsUnit')}
        </el-button>
      ),
    },
    { label: () => t('mall.seckill.stockSold'), prop: 'quantity', minWidth: '180px',
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
    { label: () => t('mall.seckill.purchaseLimit'), prop: 'max_quantity_per_user', width: '80px' },
    { label: () => t('mall.common.status'), prop: 'status', width: '100px',
      cellRender: ({ row }: { row: SeckillSessionVo }) => (
        <ElTag type={statusTypeMap[row.status || 'pending'] as any}>
          {statusTextMap[row.status || 'pending']}
        </ElTag>
      ),
    },
    { label: () => t('mall.common.enabled'), prop: 'is_enabled', width: '80px',
      cellRender: ({ row }: { row: SeckillSessionVo }) => (
        <ElSwitch
          modelValue={row.is_enabled}
          disabled={!hasAuth('seckill:session:update')}
          onChange={async () => {
            const res = await sessionToggleStatus(row.id as number)
            if (res.code === ResultCode.SUCCESS) {
              msg.success(t('mall.seckill.statusToggleSuccess'))
              await tableRef.value?.refresh()
            }
          }}
        />
      ),
    },
    { label: () => t('mall.seckill.sortOrder'), prop: 'sort_order', width: '80px' },
    {
      type: 'operation',
      label: () => t('mall.seckill.operation'),
      width: '220px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'products',
            show: () => hasAuth('seckill:product:list'),
            icon: 'material-symbols:inventory-2',
            text: () => t('mall.seckill.products'),
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
            text: () => t('mall.common.edit'),
            onClick: ({ row }: { row: SeckillSessionVo }) => {
              dialog.setTitle(t('mall.seckill.editSession'))
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('seckill:session:delete'),
            icon: 'mdi:delete',
            text: () => t('mall.common.delete'),
            onClick: async ({ row }: { row: SeckillSessionVo }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('mall.seckill.confirmDeleteSessionSingle')).then(async () => {
                const response = await sessionRemove(row.id as number)
                if (response.code === ResultCode.SUCCESS) {
                  msg.success(t('mall.seckill.deleteSuccess'))
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
