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

export default function getFormItems(model: CouponVo, t: (key: string) => string): MaFormItem[] {

  if (!model.status) {
    model.status = 'active'
  }

  const validateCouponValue = () => ({
    validator: (_: any, value: any, callback: (error?: Error) => void) => {
      if (value === undefined || value === null || value === '') {
        callback(new Error(t('mall.coupon.valueRequired')))
        return
      }
      if (typeof value !== 'number' || Number.isNaN(value)) {
        callback(new Error(t('mall.coupon.valueMustBeNumber')))
        return
      }
      if (model.type === 'percent') {
        // 表单输入折扣值如 8.5 表示8.5折，有效范围 0.1-9.9
        if (value <= 0 || value >= 10) {
          callback(new Error(t('mall.coupon.percentRange')))
          return
        }
      }
      else {
        if (value <= 0) {
          callback(new Error(t('mall.coupon.amountPositive')))
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
        callback(new Error(t('mall.coupon.minAmountMustBeNumber')))
        return
      }
      if (value < 0) {
        callback(new Error(t('mall.coupon.minAmountNonNegative')))
        return
      }
      callback()
    },
    trigger: ['blur', 'change'],
  })

  const positiveIntegerRule = (label: string, requiredMessage?: string) => ({
    validator: (_: any, value: any, callback: (error?: Error) => void) => {
      if (value === undefined || value === null || value === '') {
        callback(new Error(requiredMessage ?? t('mall.coupon.totalCountRequired')))
        return
      }
      if (typeof value !== 'number' || Number.isNaN(value) || !Number.isInteger(value)) {
        callback(new Error(t('mall.coupon.totalCountPositiveInt')))
        return
      }
      if (value <= 0) {
        callback(new Error(t('mall.coupon.totalCountPositive')))
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
        callback(new Error(t('mall.coupon.perLimitPositiveInt')))
        return
      }
      callback()
    },
    trigger: ['blur', 'change'],
  })

  return [
    {
      label: t('mall.coupon.name'),
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: t('mall.coupon.namePlaceholder'), maxlength: 120, showWordLimit: true },
      itemProps: { rules: [{ required: true, message: t('mall.coupon.nameRequired'), trigger: ['blur', 'change'] }] },
    },
    {
      label: t('mall.coupon.typeLabel'),
      prop: 'type',
      render: () => (
        <el-select placeholder={t('mall.coupon.typePlaceholder')}>
          <el-option label={t('mall.coupon.typeFixed')} value="fixed" />
          <el-option label={t('mall.coupon.typePercent')} value="percent" />
        </el-select>
      ),
      itemProps: { rules: [{ required: true, message: t('mall.coupon.typeRequired'), trigger: ['change'] }] },
    },
    {
      label: t('mall.coupon.valueLabel'),
      prop: 'value',
      render: () => (
        <el-input-number
          modelValue={typeof model.value === 'number' ? model.value : undefined}
          onUpdate:modelValue={(val: number | null) => model.value = typeof val === 'number' ? val : undefined}
          min={0.01}
          precision={2}
          placeholder={model.type === 'percent' ? t('mall.coupon.valuePlaceholderPercent') : t('mall.coupon.valuePlaceholderFixed')}
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
      label: t('mall.coupon.minAmountLabel'),
      prop: 'min_amount',
      render: () => (
        <el-input-number
          modelValue={typeof model.min_amount === 'number' ? model.min_amount : undefined}
          onUpdate:modelValue={(val: number | null) => model.min_amount = typeof val === 'number' ? val : undefined}
          min={0}
          precision={2}
          placeholder={t('mall.coupon.minAmountPlaceholder')}
          controls-position="right"
          class="w-full"
        />
      ),
      itemProps: { rules: [optionalNonNegative(t('mall.coupon.minAmountLabel'))] },
    },
    {
      label: t('mall.coupon.totalCountLabel'),
      prop: 'total_quantity',
      render: () => (
        <el-input-number
          modelValue={typeof model.total_quantity === 'number' ? model.total_quantity : 1}
          onUpdate:modelValue={(val: number | null) => model.total_quantity = typeof val === 'number' ? val : model.total_quantity}
          min={1}
          step={1}
          controls-position="right"
          placeholder={t('mall.coupon.totalCountPlaceholder')}
          class="w-full"
        />
      ),
      itemProps: {
        rules: [
          positiveIntegerRule(t('mall.coupon.totalCountLabel')),
        ],
      },
    },
    {
      label: t('mall.coupon.perLimitLabel'),
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
          placeholder={t('mall.coupon.perLimitPlaceholder')}
          class="w-full"
        />
      ),
      itemProps: { rules: [optionalPositiveInteger(t('mall.coupon.perLimitLabel'))] },
    },
    {
      label: t('mall.coupon.validityLabel'),
      prop: 'dateRange',
      render: () => (
        <el-date-picker
          v-model={model.dateRange}
          type="datetimerange"
          range-separator={t('dashboard.dateRange.to')}
          start-placeholder={t('mall.common.startTime')}
          end-placeholder={t('mall.common.endTime')}
          value-format="YYYY-MM-DD HH:mm:ss"
          class="w-full"
        />
      ),
      itemProps: {
        rules: [
          { required: true, message: t('mall.coupon.validityRequired'), trigger: ['change'] },
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (Array.isArray(value) && value.length === 2) {
                const [start, end] = value
                if (start && end && dayjs(start).isAfter(dayjs(end))) {
                  callback(new Error(t('mall.coupon.startBeforeEnd')))
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
      label: t('mall.coupon.statusLabel'),
      prop: 'status',
      render: () => <el-switch active-value="active" inactive-value="inactive" active-text={t('mall.coupon.statusActive')} inactive-text={t('mall.coupon.statusInactive')} />,
    },
    {
      label: t('mall.coupon.descriptionLabel'),
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, maxlength: 500, showWordLimit: true, placeholder: t('mall.coupon.descriptionPlaceholder') },
    },
  ]
}
