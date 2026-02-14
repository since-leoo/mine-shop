/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaSearchItem } from '@mineadmin/search'
import type { CategoryVo } from '~/mall/api/category'
import { useI18n } from 'vue-i18n'
type BrandOption = {
  value?: number
  label?: string
  id?: number
  name?: string
}

export default function getSearchItems(): MaSearchItem[] {
  const { t } = useI18n()
  const categoryOptions = ref<CategoryVo[]>([])
  const brandOptions = ref<BrandOption[]>([])

  useHttp().get('/admin/product/category/tree', { params: { parent_id: 0 } }).then((res: any) => {
    categoryOptions.value = res.data || []
  })
  useHttp().get('/admin/product/brand/options').then((res: any) => {
    brandOptions.value = res.data || []
  })

  return [
    {
      label: () => t('mall.product.keyword'),
      prop: 'keyword',
      render: 'input',
      renderProps: { placeholder: t('mall.product.keywordPlaceholder') },
    },
    {
      label: () => t('mall.product.productCode'),
      prop: 'product_code',
      render: 'input',
      renderProps: { placeholder: t('mall.product.productCode') },
    },
    {
      label: () => t('mall.product.category'),
      prop: 'category_id',
      render: () => (
        <el-tree-select
          data={categoryOptions.value}
          props={{ value: 'id', label: 'name' }}
          check-strictly={true}
          clearable={true}
          placeholder={t('mall.product.allCategory')}
        />
      ),
    },
    {
      label: () => t('mall.product.brand'),
      prop: 'brand_id',
      render: () => (
        <el-select-v2
          clearable
          placeholder={t('mall.product.allBrand')}
          options={brandOptions.value.map(item => ({
            label: item.label ?? item.name ?? '',
            value: item.value ?? item.id,
          }))}
        />
      ),
    },
    {
      label: () => t('mall.product.statusText'),
      prop: 'status',
      render: () => (
        <el-select-v2
          clearable
          placeholder={t('mall.product.allStatus')}
          options={[
            { label: t('mall.product.status.draft'), value: 'draft' },
            { label: t('mall.product.status.active'), value: 'active' },
            { label: t('mall.product.status.inactive'), value: 'inactive' },
            { label: t('mall.product.status.soldOut'), value: 'sold_out' },
          ]}
        />
      ),
    },
    {
      label: () => t('mall.product.isRecommendLabel'),
      prop: 'is_recommend',
      render: () => (
        <el-select-v2
          clearable
          placeholder={t('mall.product.all')}
          options={[
            { label: t('mall.product.isRecommend'), value: true },
            { label: t('mall.product.notRecommend'), value: false },
          ]}
        />
      ),
    },
    {
      label: () => t('mall.product.isHotLabel'),
      prop: 'is_hot',
      render: () => (
        <el-select-v2
          clearable
          placeholder={t('mall.product.all')}
          options={[
            { label: t('mall.product.isHot'), value: true },
            { label: t('mall.product.notHot'), value: false },
          ]}
        />
      ),
    },
    {
      label: () => t('mall.product.isNewLabel'),
      prop: 'is_new',
      render: () => (
        <el-select-v2
          clearable
          placeholder={t('mall.product.all')}
          options={[
            { label: t('mall.product.isNew'), value: true },
            { label: t('mall.product.notNew'), value: false },
          ]}
        />
      ),
    },
    {
      label: () => t('mall.product.minPrice'),
      prop: 'min_price',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => t('mall.product.maxPrice'),
      prop: 'max_price',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => t('mall.product.salesMin'),
      prop: 'sales_min',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => t('mall.product.salesMax'),
      prop: 'sales_max',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => t('mall.product.productName'),
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: t('mall.product.productName') },
    },
  ]
}
