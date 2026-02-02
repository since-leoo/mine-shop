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
): MaFormItem[] {
  const numberRule = (
    label: string,
    { min, max, allowZero = false, integer = false }: { min?: number; max?: number; allowZero?: boolean; integer?: boolean } = {},
  ) => ({
    validator: (_: any, value: any, callback: (error?: Error) => void) => {
      if (value === undefined || value === null || value === '') {
        callback(new Error(`请输入${label}`))
        return
      }
      if (typeof value !== 'number' || Number.isNaN(value)) {
        callback(new Error(`${label}必须为数字`))
        return
      }
      if (!allowZero && value <= 0) {
        callback(new Error(`${label}必须大于0`))
        return
      }
      if (allowZero && value < 0) {
        callback(new Error(`${label}不能小于0`))
        return
      }
      if (min !== undefined && value < min) {
        callback(new Error(`${label}不能小于${min}`))
        return
      }
      if (max !== undefined && value > max) {
        callback(new Error(`${label}不能大于${max}`))
        return
      }
      if (integer && !Number.isInteger(value)) {
        callback(new Error(`${label}必须为整数`))
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
      label: () => '活动标题',
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: '请输入活动标题' },
      itemProps: { rules: [{ required: true, message: '请输入活动标题', trigger: ['blur', 'change'] }] },
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
      itemProps: { rules: [{ required: true, message: '请选择商品', trigger: ['change'] }] },
    },
    {
      label: () => 'SKU',
      prop: 'sku_id',
      render: () => (
        <el-select filterable clearable placeholder="请选择SKU">
          {skuOptions.value.map(item => <el-option key={item.id} label={item.label} value={item.id} />)}
        </el-select>
      ),
      itemProps: { rules: [{ required: true, message: '请选择SKU', trigger: ['change'] }] },
    },
    {
      label: () => '原价',
      prop: 'original_price',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule('原价', { allowZero: true, min: 0 }),
        ],
      },
    },
    {
      label: () => '团购价',
      prop: 'group_price',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule('团购价', { min: 0.01 }),
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (typeof value !== 'number' || Number.isNaN(value)) {
                callback(new Error('请输入团购价'))
                return
              }
              if (typeof model.original_price === 'number' && value > model.original_price) {
                callback(new Error('团购价不能高于原价'))
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
      label: () => '成团人数',
      prop: 'min_people',
      render: 'inputNumber',
      renderProps: { min: 2, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule('成团人数', { min: 2, integer: true }),
        ],
      },
    },
    {
      label: () => '最大人数',
      prop: 'max_people',
      render: 'inputNumber',
      renderProps: { min: 2, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule('最大人数', { min: 2, integer: true }),
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (typeof value !== 'number' || Number.isNaN(value)) {
                callback(new Error('请输入最大人数'))
                return
              }
              if (typeof model.min_people === 'number' && value < model.min_people) {
                callback(new Error('最大人数需大于或等于成团人数'))
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
      label: () => '成团时限(小时)',
      prop: 'group_time_limit',
      render: 'inputNumber',
      renderProps: { min: 1, max: 168, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule('成团时限', { min: 1, max: 168, integer: true }),
        ],
      },
    },
    {
      label: () => '库存',
      prop: 'total_quantity',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      itemProps: {
        rules: [
          numberRule('库存', { min: 1, integer: true }),
        ],
      },
    },
    {
      label: () => '开始时间',
      prop: 'start_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />,
      renderProps: { class: 'w-full' },
      itemProps: {
        rules: [
          { required: true, message: '请选择开始时间', trigger: ['change'] },
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (value && model.end_time && dayjs(value).isAfter(dayjs(model.end_time))) {
                callback(new Error('开始时间不能晚于结束时间'))
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
      label: () => '结束时间',
      prop: 'end_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" />,
      renderProps: { class: 'w-full' },
      itemProps: {
        rules: [
          { required: true, message: '请选择结束时间', trigger: ['change'] },
          {
            validator: (_: any, value: any, callback: (error?: Error) => void) => {
              if (value && model.start_time && dayjs(value).isBefore(dayjs(model.start_time))) {
                callback(new Error('结束时间需晚于开始时间'))
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
