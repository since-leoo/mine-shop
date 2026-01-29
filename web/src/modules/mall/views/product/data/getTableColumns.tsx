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
import { remove } from '~/mall/api/product'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'

const statusTextMap: Record<string, string> = {
  draft: '草稿',
  active: '上架',
  inactive: '下架',
  sold_out: '售罄',
}

const statusTypeMap: Record<string, string> = {
  draft: 'info',
  active: 'success',
  inactive: 'warning',
  sold_out: 'danger',
}

export default function getTableColumns(dialog: UseDialogExpose): MaProTableColumns[] {
  const msg = useMessage()

  return [
    { type: 'selection', showOverflowTooltip: false },
    { type: 'index', width: '60px' },
    { label: () => '商品编码', prop: 'product_code', minWidth: '140px' },
    { label: () => '商品图片', prop: 'main_image', width: '110px',
      cellRender: ({ row }: { row: ProductVo }) => row.main_image
        ? <ElImage src={row.main_image} style="width: 48px; height: 48px" fit="cover" />
        : '-',
    },
    { label: () => '商品名称', prop: 'name', minWidth: '200px' },
    { label: () => '分类', prop: 'category_id', width: '120px',
      cellRender: ({ row }: { row: ProductVo }) => row.category?.name ?? '-',
    },
    { label: () => '品牌', prop: 'brand_id', width: '120px',
      cellRender: ({ row }: { row: ProductVo }) => row.brand?.name ?? '-',
    },
    { label: () => '价格区间', prop: 'min_price', width: '140px',
      cellRender: ({ row }: { row: ProductVo }) => `${row.min_price ?? '-'} ~ ${row.max_price ?? '-'}`,
    },
    { label: () => '状态', prop: 'status', width: '100px',
      cellRender: ({ row }: { row: ProductVo }) => (
        <ElTag type={statusTypeMap[row.status || 'draft'] as any}>
          {statusTextMap[row.status || 'draft']}
        </ElTag>
      ),
    },
    { label: () => '是否热销', prop: 'is_hot', width: '100px',
      cellRender: ({ row }: { row: ProductVo }) => (
        <ElTag type={row.is_hot ? 'danger' : 'info'}>
          {row.is_hot ? '热销' : '否'}
        </ElTag>
      ),
    },
    { label: () => '更新时间', prop: 'updated_at', minWidth: '160px' },
    {
      type: 'operation',
      label: () => '操作',
      width: '220px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('product:product:update'),
            icon: 'material-symbols:edit',
            text: () => '编辑',
            onClick: ({ row }: { row: ProductVo }) => {
              dialog.setTitle('编辑商品')
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('product:product:delete'),
            icon: 'mdi:delete',
            text: () => '删除',
            onClick: async ({ row }: { row: ProductVo }, proxy: MaProTableExpose) => {
              msg.delConfirm('确定删除该商品吗？').then(async () => {
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
