/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaProTableColumns, MaProTableExpose } from '@mineadmin/pro-table'
import type { SeckillProductVo } from '~/mall/api/seckill'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElTag, ElSwitch, ElImage } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { productRemove, productToggleStatus } from '~/mall/api/seckill'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'
import { formatYuan } from '@/utils/price'

export default function getTableColumns(dialog: UseDialogExpose, tableRef: { value: MaProTableExpose | undefined }): MaProTableColumns[] {
  const msg = useMessage()
  const { t } = useI18n()

  return [
    { type: 'selection', showOverflowTooltip: false },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => t('mall.seckill.productImage'), prop: 'product_image', width: '100px',
      cellRender: ({ row }: { row: SeckillProductVo }) => (
        row.product_image
          ? <ElImage src={row.product_image} style="width: 60px; height: 60px" fit="cover" preview-src-list={[row.product_image]} />
          : <span class="text-gray-400">{t('mall.seckill.noImage')}</span>
      ),
    },
    { label: () => t('mall.seckill.productName'), prop: 'product_name', minWidth: '180px', showOverflowTooltip: true },
    { label: () => 'SKU', prop: 'sku_name', width: '120px', showOverflowTooltip: true },
    { label: () => t('mall.seckill.originalPrice'), prop: 'original_price', width: '100px',
      cellRender: ({ row }: { row: SeckillProductVo }) => (
        <span class="text-gray-400 line-through">¥{formatYuan(row.original_price)}</span>
      ),
    },
    { label: () => t('mall.seckill.seckillPrice'), prop: 'seckill_price', width: '100px',
      cellRender: ({ row }: { row: SeckillProductVo }) => (
        <span class="text-red-500 font-semibold">¥{formatYuan(row.seckill_price)}</span>
      ),
    },
    { label: () => t('mall.seckill.stock'), prop: 'quantity', width: '80px' },
    { label: () => t('mall.seckill.sold'), prop: 'sold_quantity', width: '80px' },
    { label: () => t('mall.seckill.purchaseLimit'), prop: 'max_quantity_per_user', width: '80px' },
    { label: () => t('mall.common.enabled'), prop: 'is_enabled', width: '80px',
      cellRender: ({ row }: { row: SeckillProductVo }) => (
        <ElSwitch
          modelValue={row.is_enabled}
          disabled={!hasAuth('seckill:product:update')}
          onChange={async () => {
            const res = await productToggleStatus(row.id as number)
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
      width: '150px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('seckill:product:update'),
            icon: 'material-symbols:edit',
            text: () => t('mall.common.edit'),
            onClick: ({ row }: { row: SeckillProductVo }) => {
              dialog.setTitle(t('mall.seckill.editProductConfig'))
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('seckill:product:delete'),
            icon: 'mdi:delete',
            text: () => t('mall.common.delete'),
            onClick: async ({ row }: { row: SeckillProductVo }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('mall.seckill.confirmRemoveSingle')).then(async () => {
                const response = await productRemove(row.id as number)
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
