/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { SeckillActivityVo } from '~/mall/api/seckill'

export default function getFormItems(
  formType: 'add' | 'edit',
  model: SeckillActivityVo,
): MaFormItem[] {
  if (formType === 'add') {
    model.is_enabled = true
    model.status = 'pending'
  }

  return [
    {
      label: () => '活动标题',
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: '请输入活动标题', maxlength: 100, showWordLimit: true },
      itemProps: { rules: [{ required: true, message: '请输入活动标题' }] },
    },
    {
      label: () => '活动描述',
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: '请输入活动描述', maxlength: 500, showWordLimit: true },
    },
    {
      label: () => '活动状态',
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
