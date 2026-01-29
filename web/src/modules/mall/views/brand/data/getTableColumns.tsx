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

  return [
    { type: 'selection', showOverflowTooltip: false },
    { type: 'index', width: '60px' },
    { label: () => '品牌名称', prop: 'name', minWidth: '180px' },
    { label: () => '品牌Logo', prop: 'logo', width: '120px',
      cellRender: ({ row }: { row: BrandVo }) => row.logo
        ? h(ElImage, { src: row.logo, style: 'width: 40px; height: 40px', fit: 'cover' })
        : '-',
    },
    { label: () => '状态', prop: 'status', width: '100px',
      cellRender: ({ row }: { row: BrandVo }) => (
        <ElTag type={statusTypeMap[row.status || 'inactive'] as any}>
          {row.status === 'active' ? '启用' : '停用'}
        </ElTag>
      ),
    },
    { label: () => '排序', prop: 'sort', width: '90px' },
    { label: () => '更新时间', prop: 'updated_at', minWidth: '160px' },
    {
      type: 'operation',
      label: () => '操作',
      width: '200px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('product:brand:update'),
            icon: 'material-symbols:edit',
            text: () => '编辑',
            onClick: ({ row }: { row: BrandVo }) => {
              dialog.setTitle('编辑品牌')
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('product:brand:delete'),
            icon: 'mdi:delete',
            text: () => '删除',
            onClick: async ({ row }: { row: BrandVo }, proxy: MaProTableExpose) => {
              msg.delConfirm('确定删除该品牌吗？').then(async () => {
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
