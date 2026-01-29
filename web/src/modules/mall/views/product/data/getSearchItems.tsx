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
type BrandOption = {
  value?: number
  label?: string
  id?: number
  name?: string
}

export default function getSearchItems(): MaSearchItem[] {
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
      label: () => '关键词',
      prop: 'keyword',
      render: 'input',
      renderProps: { placeholder: '商品名称/编码' },
    },
    {
      label: () => '商品编码',
      prop: 'product_code',
      render: 'input',
      renderProps: { placeholder: '商品编码' },
    },
    {
      label: () => '分类',
      prop: 'category_id',
      render: () => (
        <el-tree-select
          data={categoryOptions.value}
          props={{ value: 'id', label: 'name' }}
          check-strictly={true}
          clearable={true}
          placeholder="全部分类"
        />
      ),
    },
    {
      label: () => '品牌',
      prop: 'brand_id',
      render: () => (
        <el-select-v2
          clearable
          placeholder="全部品牌"
          options={brandOptions.value.map(item => ({
            label: item.label ?? item.name ?? '',
            value: item.value ?? item.id,
          }))}
        />
      ),
    },
    {
      label: () => '状态',
      prop: 'status',
      render: () => (
        <el-select-v2
          clearable
          placeholder="全部状态"
          options={[
            { label: '草稿', value: 'draft' },
            { label: '上架', value: 'active' },
            { label: '下架', value: 'inactive' },
            { label: '售罄', value: 'sold_out' },
          ]}
        />
      ),
    },
    {
      label: () => '是否推荐',
      prop: 'is_recommend',
      render: () => (
        <el-select-v2
          clearable
          placeholder="全部"
          options={[
            { label: '推荐', value: true },
            { label: '不推荐', value: false },
          ]}
        />
      ),
    },
    {
      label: () => '是否热销',
      prop: 'is_hot',
      render: () => (
        <el-select-v2
          clearable
          placeholder="全部"
          options={[
            { label: '热销', value: true },
            { label: '非热销', value: false },
          ]}
        />
      ),
    },
    {
      label: () => '是否新品',
      prop: 'is_new',
      render: () => (
        <el-select-v2
          clearable
          placeholder="全部"
          options={[
            { label: '新品', value: true },
            { label: '非新品', value: false },
          ]}
        />
      ),
    },
    {
      label: () => '最低价',
      prop: 'min_price',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => '最高价',
      prop: 'max_price',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => '销量下限',
      prop: 'sales_min',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => '销量上限',
      prop: 'sales_max',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => '商品名称',
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: '商品名称' },
    },
  ]
}
