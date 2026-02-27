/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { Ref } from 'vue'
import type { CategoryVo } from '~/mall/api/category'
import type { ProductAttributeVo, ProductSkuVo, ProductVo } from '~/mall/api/product'
import MaUploadImage from '@/components/ma-upload-image/index.vue'
import MaRichEditor from '@/components/ma-rich-editor/index.vue'

type BrandOption = {
  value?: number
  label?: string
  id?: number
  name?: string
}

type SpecItem = {
  nameTags: string[]
  values: string[]
}

export default function getFormItems(
  formType: 'add' | 'edit',
  model: ProductVo,
  activeStep: Ref<number>,
  msg: ReturnType<typeof import('@/hooks/useMessage.ts').useMessage>,
  t: (key: string) => string,
): MaFormItem[] {
  const categoryOptions = ref<CategoryVo[]>([])
  const brandOptions = ref<BrandOption[]>([])
  const shippingTemplateOptions = ref<{ id: number; name: string }[]>([])
  const specItems = ref<SpecItem[]>((model as any).specs || [])

  if (formType === 'add') {
    model.status = model.status ?? 'draft'
    model.is_recommend = model.is_recommend ?? false
    model.is_hot = model.is_hot ?? false
    model.is_new = model.is_new ?? false
    model.sort = model.sort ?? 0
    model.virtual_sales = model.virtual_sales ?? 0
    model.real_sales = model.real_sales ?? 0
    model.freight_type = model.freight_type ?? 'default'
    model.flat_freight_amount = model.flat_freight_amount ?? 0
    model.min_price = model.min_price ?? 0
    model.max_price = model.max_price ?? 0
  }

  if (!model.skus) {
    model.skus = []
  }
  
  // 从 SKU 数据中反推规格配置（编辑模式）
  if (formType === 'edit' && model.skus.length > 0 && !Array.isArray((model as any).specs)) {
    const specMap = new Map<number, Set<string>>()
    
    model.skus.forEach((sku) => {
      if (Array.isArray(sku.spec_values) && sku.spec_values.length > 0) {
        sku.spec_values.forEach((value, index) => {
          if (!specMap.has(index)) {
            specMap.set(index, new Set())
          }
          specMap.get(index)!.add(value)
        })
      }
    })
    
    // 生成规格配置
    if (specMap.size > 0) {
      specItems.value = Array.from(specMap.entries()).map(([index, values]) => ({
        nameTags: [t('mall.productForm.defaultSpecName', { n: index + 1 })], // default spec name
        values: Array.from(values),
      }))
      ;(model as any).specs = specItems.value
    }
  }
  else if (!Array.isArray((model as any).specs)) {
    (model as any).specs = specItems.value
  }
  else {
    specItems.value = (model as any).specs.map((item: any) => ({
      nameTags: Array.isArray(item.nameTags) ? item.nameTags.slice(0, 1) : item.name ? [item.name] : [],
      values: Array.isArray(item.values) ? item.values : [],
    }))
    ;(model as any).specs = specItems.value
  }
  if (!model.attributes) {
    model.attributes = []
  }
  if (!model.gallery_images) {
    model.gallery_images = []
  }
  model.gallery_images = model.gallery_images.filter(Boolean)
  if (formType === 'add' && model.skus.length === 0) {
    model.skus = []
  }

  useHttp().get('/admin/product/category/tree', { params: { parent_id: 0 } }).then((res: any) => {
    categoryOptions.value = res.data || []
  })
  useHttp().get('/admin/product/brand/options').then((res: any) => {
    brandOptions.value = res.data || []
  })
  useHttp().get('/admin/shipping/templates/list', { params: { page: 1, page_size: 100 } }).then((res: any) => {
    const list = res.data?.list || res.data?.data || res.data || []
    shippingTemplateOptions.value = Array.isArray(list) ? list : []
  })

  const addSku = () => {
    model.skus?.push({
      sku_name: '',
      cost_price: 0,
      market_price: 0,
      sale_price: 0,
      stock: 0,
      status: 'active',
    } as ProductSkuVo)
  }

  const removeSku = (index: number) => {
    model.skus?.splice(index, 1)
  }

  const addSpec = () => {
    specItems.value.push({ nameTags: [], values: [] })
  }

  const removeSpec = (index: number) => {
    specItems.value.splice(index, 1)
  }

  const batchSet = (field: keyof ProductSkuVo, label: string, isInt = false) => {
    msg.prompt(t('mall.productForm.inputPrompt', { label }), '', label, (value) => {
      if (value === '' || value === null || value === undefined) {
        return t('mall.productForm.inputValidNumber')
      }
      return Number.isNaN(Number(value)) ? t('mall.productForm.inputNumber') : true
    }).then(({ value }) => {
      const next = isInt ? parseInt(value, 10) : Number(value)
      if (!Number.isNaN(next)) {
        model.skus?.forEach((sku) => {
          (sku as any)[field] = next
        })
      }
    }).catch(() => {})
  }

  const updateSkusFromSpecs = () => {
    const specs = specItems.value
      .map(item => ({
        name: item.nameTags?.[0]?.trim() ?? '',
        values: item.values.map(val => val.trim()).filter(Boolean),
      }))
      .filter(item => item.name && item.values.length > 0)

    if (!specs.length) {
      model.skus = model.skus?.length ? model.skus : []
      return
    }

    const cartesian = specs.reduce<string[][]>((acc, spec) => {
      if (acc.length === 0) {
        return spec.values.map(value => [value])
      }
      const next: string[][] = []
      acc.forEach((combo) => {
        spec.values.forEach((value) => {
          next.push([...combo, value])
        })
      })
      return next
    }, [])

    const base = model.skus?.[0] || {}
    
    // 编辑模式：尝试匹配现有 SKU，保留 ID
    if (formType === 'edit' && model.skus && model.skus.length > 0) {
      const existingSkuMap = new Map<string, ProductSkuVo>()
      model.skus.forEach((sku) => {
        const key = Array.isArray(sku.spec_values) 
          ? sku.spec_values.join('/')
          : String(sku.spec_values || '')
        existingSkuMap.set(key, sku)
      })
      
      model.skus = cartesian.map((values, index) => {
        const key = values.join('/')
        const existing = existingSkuMap.get(key)
        
        if (existing) {
          // 保留现有 SKU，只更新 spec_values 和 sku_name
          return {
            ...existing,
            sku_name: values.join('/'),
            spec_values: values,
          }
        }
        
        // 新增 SKU
        return {
          sku_code: model.product_code ? `${model.product_code}-${index + 1}` : '',
          sku_name: values.join('/'),
          spec_values: values,
          image: (base as ProductSkuVo).image,
          cost_price: base.cost_price ?? 0,
          market_price: base.market_price ?? 0,
          sale_price: base.sale_price ?? 0,
          stock: base.stock ?? 0,
          warning_stock: base.warning_stock ?? 0,
          weight: base.weight ?? 0,
          status: base.status ?? 'active',
        } as ProductSkuVo
      })
    }
    else {
      // 新增模式：直接生成新 SKU
      model.skus = cartesian.map((values, index) => ({
        sku_code: model.product_code ? `${model.product_code}-${index + 1}` : '',
        sku_name: values.join('/'),
        spec_values: values,
        image: (base as ProductSkuVo).image,
        cost_price: base.cost_price ?? 0,
        market_price: base.market_price ?? 0,
        sale_price: base.sale_price ?? 0,
        stock: base.stock ?? 0,
        warning_stock: base.warning_stock ?? 0,
        weight: base.weight ?? 0,
        status: base.status ?? 'active',
      } as ProductSkuVo))
    }
  }

  const addAttribute = () => {
    model.attributes?.push({
      attribute_name: '',
      value: '',
    } as ProductAttributeVo)
  }

  const removeAttribute = (index: number) => {
    model.attributes?.splice(index, 1)
  }

  const items: MaFormItem[] = [
    {
      label: t('mall.product.productCode'),
      prop: 'product_code',
      render: 'input',
      renderProps: { placeholder: t('mall.productForm.productCodePlaceholder'), disabled: formType !== 'add' },
      itemProps: { help: t('mall.productForm.productCodeHelp') },
      show: () => formType !== 'add',
      step: 1,
    },
    {
      label: t('mall.product.productName'),
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: t('mall.product.productName') },
      itemProps: { rules: [{ required: true, message: t('mall.productForm.productNameRequired') }] },
      step: 1,
    },
    {
      label: t('mall.product.subTitle'),
      prop: 'sub_title',
      render: 'input',
      renderProps: { placeholder: t('mall.product.subTitle') },
      itemProps: { help: t('mall.productForm.subTitleHelp') },
      step: 1,
    },
    {
      label: t('mall.product.category'),
      prop: 'category_id',
      render: () => (
        <el-tree-select
          data={categoryOptions.value}
          props={{ value: 'id', label: 'name' }}
          check-strictly={true}
          clearable={true}
          placeholder={t('mall.productForm.categoryPlaceholder')}
        />
      ),
      itemProps: { rules: [{ required: true, message: t('mall.productForm.categoryRequired') }] },
      step: 1,
    },
    {
      label: t('mall.product.brand'),
      prop: 'brand_id',
      render: () => (
        <el-select-v2
          clearable
          placeholder={t('mall.productForm.brandPlaceholder')}
          options={brandOptions.value.map(item => ({
            label: item.label ?? item.name ?? '',
            value: item.value ?? item.id,
          }))}
        />
      ),
      step: 1,
    },
    {
      label: t('mall.productForm.statusLabel'),
      prop: 'status',
      render: () => (
        <el-radio-group>
          <el-radio value="draft">{t('mall.product.status.draft')}</el-radio>
          <el-radio value="active">{t('mall.product.status.active')}</el-radio>
          <el-radio value="inactive">{t('mall.product.status.inactive')}</el-radio>
          <el-radio value="sold_out">{t('mall.product.status.soldOut')}</el-radio>
        </el-radio-group>
      ),
      step: 1,
    },
    {
      label: t('mall.product.isRecommendLabel'),
      prop: 'is_recommend',
      render: () => <el-switch active-value={true} inactive-value={false} />,
      cols: { md: 8, xs: 24 },
      step: 1,
    },
    {
      label: t('mall.product.isHotLabel'),
      prop: 'is_hot',
      render: () => <el-switch active-value={true} inactive-value={false} />,
      cols: { md: 8, xs: 24 },
      step: 1,
    },
    {
      label: t('mall.product.isNewLabel'),
      prop: 'is_new',
      render: () => <el-switch active-value={true} inactive-value={false} />,
      cols: { md: 8, xs: 24 },
      step: 1,
    },
    {
      label: t('mall.product.freightType'),
      prop: 'freight_type',
      render: () => (
        <el-select placeholder={t('mall.common.selectPlaceholder')} class="w-full">
          <el-option label={t('mall.product.freightDefault')} value="default" />
          <el-option label={t('mall.product.freightFree')} value="free" />
          <el-option label={t('mall.product.freightFlat')} value="flat" />
          <el-option label={t('mall.product.freightTemplate')} value="template" />
        </el-select>
      ),
      itemProps: {
        rules: [{ required: true, message: t('mall.productForm.freightTypeRequired') }],
        help: t('mall.productForm.freightHelp'),
      },
      cols: { md: 12, xs: 24 },
      step: 1,
    },
    {
      label: t('mall.productForm.freightAmount'),
      prop: 'flat_freight_amount',
      render: 'inputNumber',
      renderProps: { min: 0, max: 999.99, precision: 2, class: 'w-full' },
      itemProps: {
        rules: [{ required: true, message: t('mall.productForm.freightAmountRequired') }],
      },
      cols: { md: 12, xs: 24 },
      show: () => model.freight_type === 'flat',
      step: 1,
    },
    {
      label: t('mall.productForm.freightTemplate'),
      prop: 'shipping_template_id',
      render: () => (
        <el-select placeholder={t('mall.productForm.freightTemplatePlaceholder')} clearable class="w-full">
          {shippingTemplateOptions.value.map(t => (
            <el-option key={t.id} label={t.name} value={t.id} />
          ))}
        </el-select>
      ),
      itemProps: {
        rules: [{ required: true, message: t('mall.productForm.freightTemplateRequired') }],
      },
      cols: { md: 12, xs: 24 },
      show: () => model.freight_type === 'template',
      step: 1,
    },
    {
      label: t('mall.productForm.mainImage'),
      prop: 'main_image',
      render: () => MaUploadImage,
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: t('mall.productForm.gallery'),
      prop: 'gallery_images',
      render: () => MaUploadImage,
      renderProps: {
        multiple: true,
        limit: 8,
      },
      itemProps: { help: t('mall.productForm.galleryTip') },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: t('mall.productForm.description'),
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: t('mall.productForm.descPlaceholder') },
      step: 4,
    },
    {
      label: t('mall.productForm.detail'),
      prop: 'detail_content',
      render: () => (
        <MaRichEditor
          modelValue={model.detail_content || ''}
          placeholder={t('mall.productForm.detailPlaceholder')}
          height={360}
          onUpdate:modelValue={(val: string) => model.detail_content = val}
        />
      ),
      itemProps: { help: t('mall.productForm.skuHelp') },
      step: 4,
    },
    {
      label: t('mall.productForm.minPrice'),
      prop: 'min_price',
      render: 'inputNumber',
      renderProps: { min: 0, precision: 2, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: t('mall.productForm.maxPrice'),
      prop: 'max_price',
      render: 'inputNumber',
      renderProps: { min: 0, precision: 2, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: t('mall.productForm.virtualSales'),
      prop: 'virtual_sales',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: t('mall.productForm.realSales'),
      prop: 'real_sales',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: t('mall.productForm.sort'),
      prop: 'sort',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: t('mall.productForm.productAttributes'),
      prop: 'attributes',
      render: () => (
        <div class="w-full">
          <div class="mb-2 flex items-center justify-between">
            <div />
            <el-button type="primary" plain size="small" onClick={addAttribute}>
              {t('mall.productForm.addAttribute')}
            </el-button>
          </div>
          <el-table data={model.attributes || []} size="small" border>
            <el-table-column label={t('mall.productForm.attrName')} min-width="160">
              {{
                default: ({ row }: { row: ProductAttributeVo }) => (
                  <el-input
                    modelValue={row.attribute_name}
                    onUpdate:modelValue={(val: string) => row.attribute_name = val}
                    placeholder={t('mall.productForm.attrNamePlaceholder')}
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.attrValue')} min-width="220">
              {{
                default: ({ row }: { row: ProductAttributeVo }) => (
                  <el-input
                    modelValue={row.value}
                    onUpdate:modelValue={(val: string) => row.value = val}
                    placeholder={t('mall.productForm.attrValuePlaceholder')}
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.operation')} width="90">
              {{
                default: ({ $index }: { $index: number }) => (
                  <el-button type="danger" link onClick={() => removeAttribute($index)}>
                    {t('mall.common.delete')}
                  </el-button>
                ),
              }}
            </el-table-column>
          </el-table>
        </div>
      ),
      itemProps: { help: t('mall.productForm.attrHelp') },
      step: 3,
    },
    {
      label: t('mall.productForm.specConfig'),
      prop: 'specs',
      render: () => (
        <div class="w-full">
          <div class="mb-2 flex items-center justify-between">
            <div />
            <el-button type="primary" plain size="small" onClick={addSpec}>
              {t('mall.productForm.addSpec')}
            </el-button>
          </div>
          <el-table data={specItems.value} size="small" border>
            <el-table-column label={t('mall.productForm.specName')} min-width="160">
              {{
                default: ({ row }: { row: SpecItem }) => (
                  <div class="flex flex-col gap-2">
                    <el-select
                      modelValue={row.nameTags}
                      onUpdate:modelValue={(val: string[]) => {
                        row.nameTags = val.slice(0, 1)
                        updateSkusFromSpecs()
                      }}
                      multiple
                      multiple-limit={1}
                      filterable
                      allow-create
                      default-first-option
                      collapse-tags={false}
                      placeholder={t('mall.productForm.specNameTagPlaceholder')}
                    />
                  </div>
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.specValue')} min-width="320">
              {{
                default: ({ row }: { row: SpecItem }) => (
                  <div class="flex flex-col gap-2">
                    <el-select
                      modelValue={row.values}
                      onUpdate:modelValue={(val: string[]) => {
                        row.values = val
                        updateSkusFromSpecs()
                      }}
                      multiple
                      filterable
                      allow-create
                      default-first-option
                      collapse-tags={false}
                      placeholder={t('mall.productForm.specValueTagPlaceholder')}
                    />
                  </div>
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.operation')} width="90">
              {{
                default: ({ $index }: { $index: number }) => (
                  <el-button type="danger" link onClick={() => removeSpec($index)}>
                    {t('mall.common.delete')}
                  </el-button>
                ),
              }}
            </el-table-column>
          </el-table>
        </div>
      ),
      itemProps: { help: t('mall.productForm.specHelp') },
      step: 3,
    },
    {
      label: t('mall.productForm.skuList'),
      prop: 'skus',
      render: () => (
        <div class="w-full">
          <div class="mb-2 flex items-center justify-between">
            <div />
            <div class="flex flex-wrap items-center gap-2">
              <el-button type="primary" plain size="small" onClick={() => batchSet('cost_price', t('mall.productForm.costPrice'))}>
                {t('mall.productForm.batchSetCostPrice')}
              </el-button>
              <el-button type="primary" plain size="small" onClick={() => batchSet('market_price', t('mall.productForm.marketPrice'))}>
                {t('mall.productForm.batchSetMarketPrice')}
              </el-button>
              <el-button type="primary" plain size="small" onClick={() => batchSet('sale_price', t('mall.productForm.salePrice'))}>
                {t('mall.productForm.batchSetSalePrice')}
              </el-button>
              <el-button type="primary" plain size="small" onClick={() => batchSet('stock', t('mall.productForm.stock'), true)}>
                {t('mall.productForm.batchSetStock')}
              </el-button>
              <el-button type="primary" plain size="small" onClick={() => batchSet('warning_stock', t('mall.productForm.warningStock'), true)}>
                {t('mall.productForm.batchWarningStock')}
              </el-button>
            </div>
          </div>
          <el-table data={model.skus || []} size="small" border>
            {formType === 'edit' && (
              <el-table-column label={t('mall.productForm.skuCode')} min-width="140">
                {{
                  default: ({ row }: { row: ProductSkuVo }) => (
                    <el-input
                      modelValue={row.sku_code}
                      onUpdate:modelValue={(val: string) => row.sku_code = val}
                      placeholder={t('mall.productForm.skuCode')}
                      disabled
                    />
                  ),
                }}
              </el-table-column>
            )}
            <el-table-column label={t('mall.productForm.skuName')} min-width="160">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input
                    modelValue={row.sku_name}
                    onUpdate:modelValue={(val: string) => row.sku_name = val}
                    placeholder={t('mall.productForm.skuNamePlaceholder')}
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.specValues')} min-width="160">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input
                    modelValue={Array.isArray(row.spec_values) ? row.spec_values.join(',') : (row.spec_values as any)}
                    onUpdate:modelValue={(val: string) => {
                      row.spec_values = val.split(',').map(item => item.trim()).filter(Boolean)
                    }}
                    placeholder={t('mall.productForm.specValuesPlaceholder')}
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.image')} width="120">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <MaUploadImage
                    modelValue={row.image || ''}
                    onUpdate:modelValue={(val: string | string[]) => {
                      row.image = Array.isArray(val) ? val[0] : val
                    }}
                    size={60}
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.costPriceYuan')} width="110">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input-number
                    modelValue={row.cost_price ?? 0}
                    onUpdate:modelValue={(val: number) => row.cost_price = val}
                    min={0}
                    precision={2}
                    class="w-full"
                    controls-position="right"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.marketPriceYuan')} width="110">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input-number
                    modelValue={row.market_price ?? 0}
                    onUpdate:modelValue={(val: number) => row.market_price = val}
                    min={0}
                    precision={2}
                    class="w-full"
                    controls-position="right"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.salePriceYuan')} width="110">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input-number
                    modelValue={row.sale_price ?? 0}
                    onUpdate:modelValue={(val: number) => row.sale_price = val}
                    min={0}
                    precision={2}
                    class="w-full"
                    controls-position="right"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.stock')} width="100">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input-number
                    modelValue={row.stock ?? 0}
                    onUpdate:modelValue={(val: number) => row.stock = val}
                    min={0}
                    class="w-full"
                    controls-position="right"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.warningStock')} width="110">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input-number
                    modelValue={row.warning_stock ?? 0}
                    onUpdate:modelValue={(val: number) => row.warning_stock = val}
                    min={0}
                    class="w-full"
                    controls-position="right"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.weight')} width="100">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input-number
                    modelValue={row.weight ?? 0}
                    onUpdate:modelValue={(val: number) => row.weight = val}
                    min={0}
                    class="w-full"
                    controls-position="right"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.skuStatus')} width="90">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-switch
                    modelValue={row.status || 'active'}
                    onUpdate:modelValue={(val: string) => row.status = val}
                    active-value="active"
                    inactive-value="inactive"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label={t('mall.productForm.operation')} width="80">
              {{
                default: ({ $index }: { $index: number }) => (
                  <el-button type="danger" link onClick={() => removeSku($index)}>
                    {t('mall.common.delete')}
                  </el-button>
                ),
              }}
            </el-table-column>
          </el-table>
        </div>
      ),
      itemProps: { help: t('mall.productForm.skuHelp') },
      show: () => specItems.value.some(item => item.nameTags?.[0] && item.values.length > 0) || (model.skus?.length ?? 0) > 0,
      step: 3,
    },
  ]

  const showStep = (step: number) => () => activeStep.value === step
  const showWithStep = (step: number) => (item: MaFormItem) => ({ ...item, show: showStep(step) })

  const step1 = items.filter(item => (item as any).step === 1).map((item) => {
    const prop = (item as any).prop
    if (prop === 'flat_freight_amount') {
      return { ...item, show: () => activeStep.value === 1 && model.freight_type === 'flat' }
    }
    if (prop === 'shipping_template_id') {
      return { ...item, show: () => activeStep.value === 1 && model.freight_type === 'template' }
    }
    return showWithStep(1)(item)
  })
  const step2 = items.filter(item => (item as any).step === 2).map(showWithStep(2))
  const step3 = items
    .filter(item => (item as any).step === 3)
    .map((item) => {
      if ((item as any).prop === 'skus') {
        return {
          ...item,
          show: () =>
            activeStep.value === 3
            && (specItems.value.some(spec => spec.nameTags?.[0] && spec.values.length > 0)
            || (model.skus?.length ?? 0) > 0),
        }
      }
      return { ...item, show: showStep(3) }
    })
  const step4 = items.filter(item => (item as any).step === 4).map(showWithStep(4))

  return [...step1, ...step2, ...step3, ...step4]
}
