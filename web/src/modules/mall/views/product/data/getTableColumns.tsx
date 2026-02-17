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
import type { ProductVo } from '~/mall/api/product'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElImage, ElTag } from 'element-plus'
import { useI18n } from 'vue-i18n'
import { remove } from '~/mall/api/product'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'
import { formatYuan } from '@/utils/price'

const statusTypeMap: Record<string, string> = {
  draft: 'info',
  active: 'success',
  inactive: 'warning',
  sold_out: 'danger',
}

export default function getTableColumns(dialog: UseDialogExpose): MaProTableColumns[] {
  const msg = useMessage()
  const { t } = useI18n()

  const statusTextMap: Record<string, string> = {
    draft: t('mall.product.status.draft'),
    active: t('mall.product.status.active'),
    inactive: t('mall.product.status.inactive'),
    sold_out: t('mall.product.status.soldOut'),
  }

  return [
    { type: 'selection', showOverflowTooltip: false },
    { type: 'index', width: '60px' },
    { label: () => t('mall.product.productCode'), prop: 'product_code', minWidth: '140px' },
    { label: () => t('mall.productForm.mainImage'), prop: 'main_image', width: '110px',
      cellRender: ({ row }: { row: ProductVo }) => row.main_image
        ? <ElImage src={row.main_image} style="width: 48px; height: 48px" fit="cover" />
        : '-',
    },
    { label: () => t('mall.product.productName'), prop: 'name', minWidth: '200px' },
    { label: () => t('mall.product.category'), prop: 'category_id', width: '120px',
      cellRender: ({ row }: { row: ProductVo }) => row.category?.name ?? '-',
    },
    { label: () => t('mall.product.brand'), prop: 'brand_id', width: '120px',
      cellRender: ({ row }: { row: ProductVo }) => row.brand?.name ?? '-',
    },
    { label: () => t('mall.product.priceRange'), prop: 'min_price', width: '140px',
      cellRender: ({ row }: { row: ProductVo }) => `¥${formatYuan(row.min_price)} ~ ¥${formatYuan(row.max_price)}`,
    },
    { label: () => t('mall.product.statusText'), prop: 'status', width: '100px',
      cellRender: ({ row }: { row: ProductVo }) => (
        <ElTag type={statusTypeMap[row.status || 'draft'] as any}>
          {statusTextMap[row.status || 'draft']}
        </ElTag>
      ),
    },
    { label: () => t('mall.product.isHotColumn'), prop: 'is_hot', width: '100px',
      cellRender: ({ row }: { row: ProductVo }) => (
        <ElTag type={row.is_hot ? 'danger' : 'info'}>
          {row.is_hot ? t('mall.product.isHot') : t('mall.product.notHot')}
        </ElTag>
      ),
    },
    { label: () => t('crud.updateTime'), prop: 'updated_at', minWidth: '160px' },
    {
      type: 'operation',
      label: () => t('crud.operation'),
      width: '220px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('product:product:update'),
            icon: 'material-symbols:edit',
            text: () => t('mall.common.edit'),
            onClick: ({ row }: { row: ProductVo }) => {
              dialog.setTitle(t('mall.product.editProduct'))
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('product:product:delete'),
            icon: 'mdi:delete',
            text: () => t('mall.common.delete'),
            onClick: async ({ row }: { row: ProductVo }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('mall.product.confirmDeleteSingle')).then(async () => {
                const response = await remove(row.id as number)
                if (response.code === ResultCode.SUCCESS) {
                  msg.success(t('mall.product.deleteSuccess'))
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
