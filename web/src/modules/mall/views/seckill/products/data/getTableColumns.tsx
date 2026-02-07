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
import { productRemove, productToggleStatus } from '~/mall/api/seckill'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import hasAuth from '@/utils/permission/hasAuth.ts'
import { formatYuan } from '@/utils/price'

export default function getTableColumns(dialog: UseDialogExpose, tableRef: { value: MaProTableExpose | undefined }): MaProTableColumns[] {
  const msg = useMessage()

  return [
    { type: 'selection', showOverflowTooltip: false },
    { label: () => 'ID', prop: 'id', width: '80px' },
    { label: () => '商品图片', prop: 'product_image', width: '100px',
      cellRender: ({ row }: { row: SeckillProductVo }) => (
        row.product_image
          ? <ElImage src={row.product_image} style="width: 60px; height: 60px" fit="cover" preview-src-list={[row.product_image]} />
          : <span class="text-gray-400">暂无图片</span>
      ),
    },
    { label: () => '商品名称', prop: 'product_name', minWidth: '180px', showOverflowTooltip: true },
    { label: () => 'SKU', prop: 'sku_name', width: '120px', showOverflowTooltip: true },
    { label: () => '原价', prop: 'original_price', width: '100px',
      cellRender: ({ row }: { row: SeckillProductVo }) => (
        <span class="text-gray-400 line-through">¥{formatYuan(row.original_price)}</span>
      ),
    },
    { label: () => '秒杀价', prop: 'seckill_price', width: '100px',
      cellRender: ({ row }: { row: SeckillProductVo }) => (
        <span class="text-red-500 font-semibold">¥{formatYuan(row.seckill_price)}</span>
      ),
    },
    { label: () => '库存', prop: 'quantity', width: '80px' },
    { label: () => '已售', prop: 'sold_quantity', width: '80px' },
    { label: () => '限购', prop: 'max_quantity_per_user', width: '80px' },
    { label: () => '启用', prop: 'is_enabled', width: '80px',
      cellRender: ({ row }: { row: SeckillProductVo }) => (
        <ElSwitch
          modelValue={row.is_enabled}
          disabled={!hasAuth('seckill:product:update')}
          onChange={async () => {
            const res = await productToggleStatus(row.id as number)
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
      width: '150px',
      operationConfigure: {
        type: 'tile',
        actions: [
          {
            name: 'edit',
            show: () => hasAuth('seckill:product:update'),
            icon: 'material-symbols:edit',
            text: () => '编辑',
            onClick: ({ row }: { row: SeckillProductVo }) => {
              dialog.setTitle('编辑商品配置')
              dialog.open({ formType: 'edit', data: row })
            },
          },
          {
            name: 'del',
            show: () => hasAuth('seckill:product:delete'),
            icon: 'mdi:delete',
            text: () => '删除',
            onClick: async ({ row }: { row: SeckillProductVo }, proxy: MaProTableExpose) => {
              msg.delConfirm('确定移除该商品吗？').then(async () => {
                const response = await productRemove(row.id as number)
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
