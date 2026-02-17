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
import type { BrandVo } from '~/mall/api/brand'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { ElImage, ElTag } from 'element-plus'
import { h } from 'vue'
import { useI18n } from 'vue-i18n'
import { remove } from '~/mall/api/brand'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const statusTypeMap: Record<string, string> = {
  active: 'success',
  inactive: 'info',
}

export default function getTableColumns(dialog: UseDialogExpose): MaProTableColumns[] {
  const msg = useMessage()
  const { t } = useI18n()

  return [
    { type: 'selection', showOverflowTooltip: false },
    { type: 'index', width: '60px' },
    { label: () => t('mall.brand.brandName'), prop: 'name', minWidth: '180px' },
    { label: () => t('mall.brand.brandLogo'), prop: 'logo', width: '120px',
      cellRender: ({ row }: { row: BrandVo }) => row.logo
        ? h(ElImage, { src: row.logo, style: 'width: 40px; height: 40px', fit: 'cover' })
        : '-',
    },
    { label: () => t('mall.brand.statusLabel'), prop: 'status', width: '100px',
      cellRender: ({ row }: { row: BrandVo }) => (
        <ElTag type={statusTypeMap[row.status || 'inactive'] as any}>
          {row.status === 'active' ? t('mall.common.enabled') : t('mall.common.disabled')}
        </ElTag>
      ),
    },
    { label: () => t('mall.brand.sortLabel'), prop: 'sort', width: '90px' },
    { label: () => t('mall.brand.updateTime'), prop: 'updated_at', minWidth: '160px' },
    {
      type: 'operation',
      label: () => t('mall.brand.operation'),
      width: '200px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('product:brand:update'),
            icon: 'material-symbols:edit',
            text: () => t('mall.common.edit'),
            onClick: ({ row }: { row: BrandVo }) => {
              dialog.setTitle(t('mall.brand.editBrand'))
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('product:brand:delete'),
            icon: 'mdi:delete',
            text: () => t('mall.common.delete'),
            onClick: async ({ row }: { row: BrandVo }, proxy: MaProTableExpose) => {
              msg.delConfirm(t('mall.brand.confirmDeleteSingle')).then(async () => {
                const response = await remove(row.id as number)
                if (response.code === ResultCode.SUCCESS) {
                  msg.success(t('mall.brand.deleteSuccess'))
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
