/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { ProductVo } from '~/mall/api/product'
import type { GroupBuyVo } from '~/mall/api/group-buy'

export default function getFormItems(
  formType: 'add' | 'edit',
  model: GroupBuyVo,
  productOptions: Ref<ProductVo[]>,
  skuOptions: Ref<{ id: number; label: string }[]>,
  onProductChange: (productId?: number) => void,
): MaFormItem[] {
  if (formType === 'add') {
    model.is_enabled = true
    model.min_people = 2
    model.max_people = 5
    model.group_time_limit = 24
  }

  return [
    {
      label: () => '活动标题',
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: '请输入活动标题' },
      itemProps: { rules: [{ required: true, message: '请输入活动标题' }] },
    },
    {
      label: () => '商品',
      prop: 'product_id',
      render: () => (
        <el-select
          filterable
          clearable
          placeholder="请选择商品"
          onChange={(val: number) => onProductChange(val)}
        >
          {productOptions.value.map(item => <el-option key={item.id} label={item.name} value={item.id} />)}
        </el-select>
      ),
      itemProps: { rules: [{ required: true, message: '请选择商品' }] },
    },
    {
      label: () => 'SKU',
      prop: 'sku_id',
      render: () => (
        <el-select filterable clearable placeholder="请选择SKU">
          {skuOptions.value.map(item => <el-option key={item.id} label={item.label} value={item.id} />)}
        </el-select>
      ),
      itemProps: { rules: [{ required: true, message: '请选择SKU' }] },
    },
    {
      label: () => '原价',
      prop: 'original_price',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      itemProps: { rules: [{ required: true, message: '请输入原价' }] },
    },
    {
      label: () => '团购价',
      prop: 'group_price',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      itemProps: { rules: [{ required: true, message: '请输入团购价' }] },
    },
    {
      label: () => '成团人数',
      prop: 'min_people',
      render: 'inputNumber',
      renderProps: { min: 2, class: 'w-full' },
    },
    {
      label: () => '最大人数',
      prop: 'max_people',
      render: 'inputNumber',
      renderProps: { min: 2, class: 'w-full' },
    },
    {
      label: () => '成团时限(小时)',
      prop: 'group_time_limit',
      render: 'inputNumber',
      renderProps: { min: 1, max: 168, class: 'w-full' },
    },
    {
      label: () => '库存',
      prop: 'total_quantity',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => '开始时间',
      prop: 'start_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />,
      renderProps: { class: 'w-full' },
      itemProps: { rules: [{ required: true, message: '请选择开始时间' }] },
    },
    {
      label: () => '结束时间',
      prop: 'end_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />,
      renderProps: { class: 'w-full' },
      itemProps: { rules: [{ required: true, message: '请选择结束时间' }] },
    },
    {
      label: () => '启用',
      prop: 'is_enabled',
      render: () => (
        <el-switch active-value={true} inactive-value={false} />
      ),
    },
    {
      label: () => '备注',
      prop: 'remark',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: '请输入备注' },
    },
  ]
}
