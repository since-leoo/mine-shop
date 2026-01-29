/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { SeckillSessionVo } from '~/mall/api/seckill'

export default function getFormItems(
  formType: 'add' | 'edit',
  model: SeckillSessionVo,
): MaFormItem[] {
  if (formType === 'add') {
    model.is_enabled = true
    model.status = 'pending'
    model.max_quantity_per_user = 1
    model.sort_order = 0
  }

  return [
    {
      label: () => '开始时间',
      prop: 'start_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="请选择开始时间" style="width: 100%" />,
      itemProps: { rules: [{ required: true, message: '请选择开始时间' }] },
    },
    {
      label: () => '结束时间',
      prop: 'end_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="请选择结束时间" style="width: 100%" />,
      itemProps: { rules: [{ required: true, message: '请选择结束时间' }] },
    },
    {
      label: () => '场次状态',
      prop: 'status',
      render: 'select',
      renderProps: { placeholder: '请选择状态' },
      renderSlots: {
        default: () => [
          <el-option label="待开始" value="pending" />,
          <el-option label="进行中" value="active" />,
          <el-option label="已结束" value="ended" />,
          <el-option label="已取消" value="cancelled" />,
        ],
      },
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
    {
      label: () => '备注',
      prop: 'remark',
      render: 'input',
      renderProps: { type: 'textarea', rows: 2, placeholder: '请输入备注', maxlength: 500, showWordLimit: true },
    },
  ]
}
