/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */

import dayjs from 'dayjs'
import type { MaFormItem } from '@mineadmin/form'
import type { CouponVo } from '~/mall/api/coupon'

export default function getFormItems(model: CouponVo): MaFormItem[] {
  if (!model.status) {
    model.status = 'active'
  }

  const validateCouponValue = () => ({
    validator: (_: any, value: any, callback: (error?: Error) => void) => {
      if (value === undefined || value === null || value === '') {
        callback(new Error('请输入优惠值'))
        return
      }
      if (typeof value !== 'number' || Number.isNaN(value)) {
        callback(new Error('优惠值必须为数字'))
        return
      }
      if (model.type === 'percent') {
        if (value <= 0 || value > 100) {
          callback(new Error('折扣值需在 0-100 之间'))
          return
        }
      }
      else {
        if (value <= 0) {
          callback(new Error('优惠金额必须大于0'))
          return
        }
      }
      callback()
    },
    trigger: ['blur', 'change'],
  })

  const optionalNonNegative = (label: string) => ({
    validator: (_: any, value: any, callback: (error?: Error) => void) => {
      if (value === undefined || value === null || value === '') {
        callback()
        return
      }
      if (typeof value !== 'number' || Number.isNaN(value)) {
        callback(new Error(`${label}必须为数字`))
        return
      }
      if (value < 0) {
        callback(new Error(`${label}不能小于0`))
        return
      }
      callback()
    },
    trigger: ['blur', 'change'],
  })

  const positiveIntegerRule = (label: string, requiredMessage?: string) => ({
    validator: (_: any, value: any, callback: (error?: Error) => void) => {
      if (value === undefined || value === null || value === '') {
        callback(new Error(requiredMessage ?? `请输入${label}`))
        return
      }
      if (typeof value !== 'number' || Number.isNaN(value) || !Number.isInteger(value)) {
        callback(new Error(`${label}必须为正整数`))
        return
      }
      if (value <= 0) {
        callback(new Error(`${label}必须大于0`))
        return
      }
      callback()
    },
    trigger: ['blur', 'change'],
  })

  const optionalPositiveInteger = (label: string) => ({
    validator: (_: any, value: any, callback: (error?: Error) => void) => {
      if (value === undefined || value === null || value === '') {
        callback()
        return
      }
      if (typeof value !== 'number' || Number.isNaN(value) || !Number.isInteger(value) || value <= 0) {
        callback(new Error(`${label}必须为正整数`))
        return
      }
      callback()
    },
    trigger: ['blur', 'change'],
  })

  return [
    {
      label: () => '优惠券名称',
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: '请输入优惠券名称', maxlength: 120, showWordLimit: true },
      itemProps: { rules: [{ required: true, message: '请输入优惠券名称', trigger: ['blur', 'change'] }] },
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
      itemProps: { rules: [{ required: true, message: '请选择优惠类型', trigger: ['change'] }] },
    },
    {
      label: () => '优惠值',
      prop: 'value',
      render: () => (
        <el-input-number
          modelValue={typeof model.value === 'number' ? model.value : undefined}
          onUpdate:modelValue={(val: number | null) => model.value = typeof val === 'number' ? val : undefined}
          min={0.01}
          precision={2}
          placeholder="请输入优惠金额/折扣值"
          controls-position="right"
          class="w-full"
        />
      ),
      itemProps: {
        rules: [
          validateCouponValue(),
        ],
      },
    },
    {
      label: () => '最低使用金额',
      prop: 'min_amount',
      render: () => (
        <el-input-number
          modelValue={typeof model.min_amount === 'number' ? model.min_amount : undefined}
          onUpdate:modelValue={(val: number | null) => model.min_amount = typeof val === 'number' ? val : undefined}
          min={0}
          precision={2}
          placeholder="请输入最低使用金额"
          controls-position="right"
          class="w-full"
        />
      ),
      itemProps: { rules: [optionalNonNegative('最低使用金额')] },
    },
    {
      label: () => '发放总数',
      prop: 'total_quantity',
      render: () => (
        <el-input-number
          modelValue={typeof model.total_quantity === 'number' ? model.total_quantity : 1}
          onUpdate:modelValue={(val: number | null) => model.total_quantity = typeof val === 'number' ? val : model.total_quantity}
          min={1}
          step={1}
          controls-position="right"
          placeholder="请输入发放数量"
          class="w-full"
        />
      ),
      itemProps: {
        rules: [
          positiveIntegerRule('发放总数'),
        ],
      },
    },
    {
      label: () => '每人限领',
      prop: 'per_user_limit',
      render: () => (
        <el-input-number
          modelValue={typeof model.per_user_limit === 'number' ? model.per_user_limit : undefined}
          onUpdate:modelValue={(val: number | null) => {
            model.per_user_limit = typeof val === 'number' ? val : undefined
          }}
          min={1}
          step={1}
          controls-position="right"
          placeholder="请输入限领数量（留空则不限）"
          class="w-full"
        />
      ),
      itemProps: { rules: [optionalPositiveInteger('每人限领')] },
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
      itemProps: {
        rules: [
          { required: true, message: '请选择有效期', trigger: ['change'] },
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (Array.isArray(value) && value.length === 2) {
                const [start, end] = value
                if (start && end && dayjs(start).isAfter(dayjs(end))) {
                  callback(new Error('开始时间不能晚于结束时间'))
                  return
                }
              }
              callback()
            },
            trigger: ['change'],
          },
        ],
      },
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
