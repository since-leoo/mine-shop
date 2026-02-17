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
import { useI18n } from 'vue-i18n'
import { remove, toggleStatus } from '~/mall/api/group-buy'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'
import { formatYuan } from '@/utils/price'

const statusTypeMap: Record<string, string> = {
  pending: 'info',
  active: 'success',
  ended: 'danger',
  cancelled: 'warning',
  sold_out: 'warning',
}

export default function getTableColumns(dialog: UseDialogExpose, tableRef: { value: MaProTableExpose | undefined }): MaProTableColumns[] {
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
    { label: () => t('mall.groupBuy.product'), prop: 'product', minWidth: '220px',
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
    { label: () => t('mall.groupBuy.price'), prop: 'price', width: '140px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <div>
          <div class="text-red-500 font-semibold">¥{formatYuan(row.group_price)}</div>
          <div class="text-xs text-gray-400 line-through">¥{formatYuan(row.original_price)}</div>
        </div>
      ),
    },
    { label: () => t('mall.groupBuy.groupSize'), prop: 'min_people', width: '100px',
      cellRender: ({ row }: { row: GroupBuyVo }) => `${row.min_people ?? 2}-${row.max_people ?? 10}${t('mall.groupBuy.people')}`,
    },
    { label: () => t('mall.groupBuy.stockSales'), prop: 'quantity', width: '160px',
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
    { label: () => t('mall.common.status'), prop: 'status', width: '100px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <ElTag type={statusTypeMap[row.status || 'pending'] as any}>
          {statusTextMap[row.status || 'pending']}
        </ElTag>
      ),
    },
    { label: () => t('mall.common.enabled'), prop: 'is_enabled', width: '80px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <ElSwitch
          modelValue={row.is_enabled}
          disabled={!hasAuth('promotion:group_buy:update')}
          onChange={async () => {
            const res = await toggleStatus(row.id as number)
            if (res.code === ResultCode.SUCCESS) {
              msg.success(t('mall.groupBuy.statusToggleSuccess'))
              await tableRef.value?.refresh()
            }
          }}
        />
      ),
    },
    { label: () => t('mall.groupBuy.time'), prop: 'time', width: '180px',
      cellRender: ({ row }: { row: GroupBuyVo }) => (
        <div class="text-xs">
          <div>{t('mall.groupBuy.startLabel')}: {row.start_time}</div>
          <div>{t('mall.groupBuy.endLabel')}: {row.end_time}</div>
        </div>
      ),
    },
    {
      type: 'operation',
      label: () => t('mall.groupBuy.operation'),
      width: '150px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('promotion:group_buy:update'),
            icon: 'material-symbols:edit',
            text: () => t('mall.common.edit'),
            onClick: ({ row }: { row: GroupBuyVo }) => {
              dialog.setTitle(t('mall.groupBuy.editGroupBuy'))
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('promotion:group_buy:delete'),
            icon: 'mdi:delete',
            text: () => t('mall.common.delete'),
            onClick: async ({ row }: { row: GroupBuyVo }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('mall.groupBuy.confirmDeleteSingle')).then(async () => {
                const response = await remove(row.id as number)
                if (response.code === ResultCode.SUCCESS) {
                  msg.success(t('mall.groupBuy.deleteSuccess'))
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
