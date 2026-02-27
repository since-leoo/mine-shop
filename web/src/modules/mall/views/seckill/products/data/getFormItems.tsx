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
  t: (key: string) => string,
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
        label: t('mall.seckill.formSelectProduct'),
        prop: 'product_id',
        render: () => (
          <el-select
            filterable
            clearable
            placeholder={t('mall.seckill.formSelectProductPlaceholder')}
            style="width: 100%"
            onChange={(val: number) => onProductChange(val)}
          >
            {productOptions.value.map(item => <el-option key={item.id} label={item.name} value={item.id} />)}
          </el-select>
        ),
        itemProps: { rules: [{ required: true, message: t('mall.seckill.formSelectProductRequired') }] },
      },
      {
        label: t('mall.seckill.formSelectSku'),
        prop: 'product_sku_id',
        render: () => (
          <el-select
            filterable
            clearable
            placeholder={t('mall.seckill.formSelectSkuPlaceholder')}
            style="width: 100%"
            onChange={(val: number) => onSkuChange(val)}
          >
            {skuOptions.value.map(item => <el-option key={item.id} label={item.label} value={item.id} />)}
          </el-select>
        ),
        itemProps: { rules: [{ required: true, message: t('mall.seckill.formSelectSkuRequired') }] },
      },
    )
  }

  items.push(
    {
      label: t('mall.seckill.formOriginalPrice'),
      prop: 'original_price',
      render: 'inputNumber',
      renderProps: { min: 0.01, precision: 2, class: 'w-full', placeholder: t('mall.seckill.formOriginalPricePlaceholder') },
      itemProps: { rules: [{ required: true, message: t('mall.seckill.formOriginalPriceRequired') }] },
    },
    {
      label: t('mall.seckill.formSeckillPrice'),
      prop: 'seckill_price',
      render: 'inputNumber',
      renderProps: { min: 0.01, precision: 2, class: 'w-full', placeholder: t('mall.seckill.formSeckillPricePlaceholder') },
      itemProps: { rules: [{ required: true, message: t('mall.seckill.formSeckillPriceRequired') }] },
    },
    {
      label: t('mall.seckill.formStock'),
      prop: 'quantity',
      render: 'inputNumber',
      renderProps: { min: 1, class: 'w-full', placeholder: t('mall.seckill.formStockPlaceholder') },
      itemProps: { rules: [{ required: true, message: t('mall.seckill.formStockRequired') }] },
    },
    {
      label: t('mall.seckill.formPerUserLimit'),
      prop: 'max_quantity_per_user',
      render: 'inputNumber',
      renderProps: { min: 1, class: 'w-full', placeholder: t('mall.seckill.formPerUserLimitPlaceholder') },
    },
    {
      label: t('mall.seckill.formSortOrder'),
      prop: 'sort_order',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full', placeholder: t('mall.seckill.formSortPlaceholder') },
    },
    {
      label: t('mall.seckill.formEnabledStatus'),
      prop: 'is_enabled',
      render: () => <el-switch active-value={true} inactive-value={false} />,
    },
  )

  return items
}
