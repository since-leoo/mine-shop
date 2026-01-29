/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { SeckillProductVo } from '~/mall/api/seckill'
import type { ProductVo } from '~/mall/api/product'

export default function getFormItems(
  formType: 'add' | 'edit',
  model: SeckillProductVo,
  productOptions: Ref<ProductVo[]>,
  skuOptions: Ref<{ id: number; label: string; price: number }[]>,
  onProductChange: (productId?: number) => void,
  onSkuChange: (skuId?: number) => void,
): MaFormItem[] {
  if (formType === 'add') {
    model.is_enabled = true
    model.max_quantity_per_user = 1
    model.sort_order = 0
  }

  const items: MaFormItem[] = []

  if (formType === 'add') {
    items.push(
      {
        label: () => '选择商品',
        prop: 'product_id',
        render: () => (
          <el-select
            filterable
            clearable
            placeholder="请选择商品"
            style="width: 100%"
            onChange={(val: number) => onProductChange(val)}
          >
            {productOptions.value.map(item => <el-option key={item.id} label={item.name} value={item.id} />)}
          </el-select>
        ),
        itemProps: { rules: [{ required: true, message: '请选择商品' }] },
      },
      {
        label: () => '选择SKU',
        prop: 'product_sku_id',
        render: () => (
          <el-select
            filterable
            clearable
            placeholder="请先选择商品"
            style="width: 100%"
            onChange={(val: number) => onSkuChange(val)}
          >
            {skuOptions.value.map(item => <el-option key={item.id} label={item.label} value={item.id} />)}
          </el-select>
        ),
        itemProps: { rules: [{ required: true, message: '请选择SKU' }] },
      },
    )
  }

  items.push(
    {
      label: () => '原价',
      prop: 'original_price',
      render: 'inputNumber',
      renderProps: { min: 0.01, precision: 2, class: 'w-full', placeholder: '请输入原价' },
      itemProps: { rules: [{ required: true, message: '请输入原价' }] },
    },
    {
      label: () => '秒杀价',
      prop: 'seckill_price',
      render: 'inputNumber',
      renderProps: { min: 0.01, precision: 2, class: 'w-full', placeholder: '请输入秒杀价（需小于原价）' },
      itemProps: { rules: [{ required: true, message: '请输入秒杀价' }] },
    },
    {
      label: () => '库存数量',
      prop: 'quantity',
      render: 'inputNumber',
      renderProps: { min: 1, class: 'w-full', placeholder: '请输入库存数量' },
      itemProps: { rules: [{ required: true, message: '请输入库存数量' }] },
    },
    {
      label: () => '每人限购',
      prop: 'max_quantity_per_user',
      render: 'inputNumber',
      renderProps: { min: 1, class: 'w-full', placeholder: '请输入每人限购数量' },
    },
    {
      label: () => '排序',
      prop: 'sort_order',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full', placeholder: '数值越小越靠前' },
    },
    {
      label: () => '启用状态',
      prop: 'is_enabled',
      render: () => <el-switch active-value={true} inactive-value={false} />,
    },
  )

  return items
}
