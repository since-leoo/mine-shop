/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */

import type { MaFormItem } from '@mineadmin/form'
import type { CouponVo } from '~/mall/api/coupon'

export default function getFormItems(model: CouponVo): MaFormItem[] {
  if (!model.status) {
    model.status = 'active'
  }

  return [
    {
      label: () => '优惠券名称',
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: '请输入优惠券名称', maxlength: 120, showWordLimit: true },
      itemProps: { rules: [{ required: true, message: '请输入优惠券名称' }] },
    },
    {
      label: () => '优惠类型',
      prop: 'type',
      render: 'select',
      renderProps: { placeholder: '请选择类型' },
      renderSlots: {
        default: () => [
          <el-option label="满减" value="fixed" />,
          <el-option label="折扣" value="percent" />,
        ],
      },
      itemProps: { rules: [{ required: true, message: '请选择优惠类型' }] },
    },
    {
      label: () => '优惠值',
      prop: 'value',
      render: 'inputNumber',
      renderProps: { min: 0.01, precision: 2, placeholder: '请输入优惠金额/折扣值', class: 'w-full' },
      itemProps: { rules: [{ required: true, message: '请输入优惠值' }] },
    },
    {
      label: () => '最低使用金额',
      prop: 'min_amount',
      render: 'inputNumber',
      renderProps: { min: 0, precision: 2, placeholder: '请输入最低使用金额', class: 'w-full' },
    },
    {
      label: () => '发放总数',
      prop: 'total_quantity',
      render: 'inputNumber',
      renderProps: { min: 1, precision: 0, placeholder: '请输入发放数量', class: 'w-full' },
      itemProps: { rules: [{ required: true, message: '请输入发放总数' }] },
    },
    {
      label: () => '每人限领',
      prop: 'per_user_limit',
      render: 'inputNumber',
      renderProps: { min: 1, precision: 0, placeholder: '请输入限领数量', class: 'w-full' },
    },
    {
      label: () => '有效期',
      prop: 'dateRange',
      render: () => (
        <el-date-picker
          v-model={model.dateRange}
          type="datetimerange"
          range-separator="至"
          start-placeholder="开始时间"
          end-placeholder="结束时间"
          value-format="YYYY-MM-DD HH:mm:ss"
          class="w-full"
        />
      ),
      itemProps: { rules: [{ required: true, message: '请选择有效期' }] },
    },
    {
      label: () => '状态',
      prop: 'status',
      render: () => <el-switch active-value="active" inactive-value="inactive" active-text="启用" inactive-text="停用" />,
    },
    {
      label: () => '描述',
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, maxlength: 500, showWordLimit: true, placeholder: '请输入描述' },
    },
  ]
}
