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
import type { ProductVo } from '~/mall/api/product'
import type { GroupBuyVo } from '~/mall/api/group-buy'

export default function getFormItems(
  formType: 'add' | 'edit',
  model: GroupBuyVo,
  productOptions: Ref<ProductVo[]>,
  skuOptions: Ref<{ id: number; label: string }[]>,
  onProductChange: (productId?: number) => void,
  t: (key: string) => string,
): MaFormItem[] {

  const numberRule = (
    label: string,
    { min, max, allowZero = false, integer = false }: { min?: number; max?: number; allowZero?: boolean; integer?: boolean } = {},
  ) => ({
    validator: (_: any, value: any, callback: (error?: Error) => void) => {
      if (value === undefined || value === null || value === '') {
        callback(new Error(t('mall.groupBuy.formInputRequired', { label })))
        return
      }
      if (typeof value !== 'number' || Number.isNaN(value)) {
        callback(new Error(t('mall.groupBuy.formMustBeNumber', { label })))
        return
      }
      if (!allowZero && value <= 0) {
        callback(new Error(t('mall.groupBuy.formMustBePositive', { label })))
        return
      }
      if (allowZero && value < 0) {
        callback(new Error(t('mall.groupBuy.formCannotBeNegative', { label })))
        return
      }
      if (min !== undefined && value < min) {
        callback(new Error(t('mall.groupBuy.formCannotBeLessThan', { label, min })))
        return
      }
      if (max !== undefined && value > max) {
        callback(new Error(t('mall.groupBuy.formCannotBeGreaterThan', { label, max })))
        return
      }
      if (integer && !Number.isInteger(value)) {
        callback(new Error(t('mall.groupBuy.formMustBeInteger', { label })))
        return
      }
      callback()
    },
    trigger: ['blur', 'change'],
  })


  if (formType === 'add') {
    model.is_enabled = true
    model.min_people = 2
    model.max_people = 5
    model.group_time_limit = 24
    if (typeof model.original_price !== 'number') {
      model.original_price = 0
    }
    if (typeof model.group_price !== 'number') {
      model.group_price = 0
    }
    if (!model.start_time) {
      model.start_time = dayjs().startOf('day').format('YYYY-MM-DD HH:mm:ss')
    }
    if (!model.end_time) {
      model.end_time = dayjs().endOf('day').format('YYYY-MM-DD HH:mm:ss')
    }
    if (typeof model.total_quantity !== 'number') {
      model.total_quantity = 0
    }
  }

  return [
    {
      label: t('mall.groupBuy.formTitle'),
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: t('mall.groupBuy.formTitlePlaceholder') },
      itemProps: { rules: [{ required: true, message: t('mall.groupBuy.formTitleRequired'), trigger: ['blur', 'change'] }] },
    },
    {
      label: t('mall.groupBuy.formProduct'),
      prop: 'product_id',
      render: () => (
        <el-select
          filterable
          clearable
          placeholder={t('mall.groupBuy.formProductPlaceholder')}
          onChange={(val: number) => onProductChange(val)}
        >
          {productOptions.value.map(item => <el-option key={item.id} label={item.name} value={item.id} />)}
        </el-select>
      ),
      itemProps: { rules: [{ required: true, message: t('mall.groupBuy.formProductRequired'), trigger: ['change'] }] },
    },
    {
      label: 'SKU',
      prop: 'sku_id',
      render: () => (
        <el-select filterable clearable placeholder={t('mall.groupBuy.formSkuPlaceholder')}>
          {skuOptions.value.map(item => <el-option key={item.id} label={item.label} value={item.id} />)}
        </el-select>
      ),
      itemProps: { rules: [{ required: true, message: t('mall.groupBuy.formSkuRequired'), trigger: ['change'] }] },
    },
    {
      label: t('mall.groupBuy.formOriginalPrice'),
      prop: 'original_price',
      render: 'inputNumber',
      renderProps: { min: 0, precision: 2, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule(t('mall.groupBuy.formOriginalPrice'), { allowZero: true, min: 0 }),
        ],
      },
    },
    {
      label: t('mall.groupBuy.formGroupPrice'),
      prop: 'group_price',
      render: 'inputNumber',
      renderProps: { min: 0, precision: 2, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule(t('mall.groupBuy.formGroupPrice'), { min: 0.01 }),
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (typeof value !== 'number' || Number.isNaN(value)) {
                callback(new Error(t('mall.groupBuy.formGroupPriceRequired')))
                return
              }
              if (typeof model.original_price === 'number' && value > model.original_price) {
                callback(new Error(t('mall.groupBuy.formGroupPriceExceed')))
                return
              }
              callback()
            },
            trigger: ['blur', 'change'],
          },
        ],
      },
    },
    {
      label: t('mall.groupBuy.formMinPeople'),
      prop: 'min_people',
      render: 'inputNumber',
      renderProps: { min: 2, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule(t('mall.groupBuy.formMinPeople'), { min: 2, integer: true }),
        ],
      },
    },
    {
      label: t('mall.groupBuy.formMaxPeople'),
      prop: 'max_people',
      render: 'inputNumber',
      renderProps: { min: 2, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule(t('mall.groupBuy.formMaxPeople'), { min: 2, integer: true }),
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (typeof value !== 'number' || Number.isNaN(value)) {
                callback(new Error(t('mall.groupBuy.formMaxPeopleRequired')))
                return
              }
              if (typeof model.min_people === 'number' && value < model.min_people) {
                callback(new Error(t('mall.groupBuy.formMaxPeopleGteMin')))
                return
              }
              callback()
            },
            trigger: ['blur', 'change'],
          },
        ],
      },
    },
    {
      label: t('mall.groupBuy.formTimeLimit'),
      prop: 'group_time_limit',
      render: 'inputNumber',
      renderProps: { min: 1, max: 168, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule(t('mall.groupBuy.formTimeLimit'), { min: 1, max: 168, integer: true }),
        ],
      },
    },
    {
      label: t('mall.groupBuy.formStock'),
      prop: 'total_quantity',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule(t('mall.groupBuy.formStock'), { min: 1, integer: true }),
        ],
      },
    },
    {
      label: t('mall.groupBuy.formStartTime'),
      prop: 'start_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />,
      renderProps: { class: 'w-full' },
      itemProps: {
        rules: [
          { required: true, message: t('mall.groupBuy.formStartTimeRequired'), trigger: ['change'] },
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (value && model.end_time && dayjs(value).isAfter(dayjs(model.end_time))) {
                callback(new Error(t('mall.groupBuy.formStartBeforeEnd')))
                return
              }
              callback()
            },
            trigger: ['change'],
          },
        ],
      },
    },
    {
      label: t('mall.groupBuy.formEndTime'),
      prop: 'end_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />,
      renderProps: { class: 'w-full' },
      itemProps: {
        rules: [
          { required: true, message: t('mall.groupBuy.formEndTimeRequired'), trigger: ['change'] },
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (value && model.start_time && dayjs(value).isBefore(dayjs(model.start_time))) {
                callback(new Error(t('mall.groupBuy.formEndAfterStart')))
                return
              }
              callback()
            },
            trigger: ['change'],
          },
        ],
      },
    },
    {
      label: t('mall.groupBuy.formEnabled'),
      prop: 'is_enabled',
      render: () => (
        <el-switch active-value={true} inactive-value={false} />
      ),
    },
    {
      label: t('mall.groupBuy.formRemark'),
      prop: 'remark',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: t('mall.groupBuy.formRemarkPlaceholder') },
    },
  ]
}
