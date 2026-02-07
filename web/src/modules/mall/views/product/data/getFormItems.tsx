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
): MaFormItem[] {
  const categoryOptions = ref<CategoryVo[]>([])
  const brandOptions = ref<BrandOption[]>([])
  const specItems = ref<SpecItem[]>((model as any).specs || [])

  if (formType === 'add') {
    model.status = model.status ?? 'draft'
    model.is_recommend = model.is_recommend ?? false
    model.is_hot = model.is_hot ?? false
    model.is_new = model.is_new ?? false
    model.sort = model.sort ?? 0
    model.virtual_sales = model.virtual_sales ?? 0
    model.real_sales = model.real_sales ?? 0
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
        nameTags: [`规格${index + 1}`], // 默认规格名
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
    msg.prompt(`请输入${label}`, '', label, (value) => {
      if (value === '' || value === null || value === undefined) {
        return '请输入有效数值'
      }
      return Number.isNaN(Number(value)) ? '请输入数字' : true
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
      label: () => '商品编码',
      prop: 'product_code',
      render: 'input',
      renderProps: { placeholder: '可留空自动生成', disabled: formType !== 'add' },
      itemProps: { help: '留空则系统自动生成唯一编码。' },
      show: () => formType !== 'add',
      step: 1,
    },
    {
      label: () => '商品名称',
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: '请输入商品名称' },
      itemProps: { rules: [{ required: true, message: '请输入商品名称' }] },
      step: 1,
    },
    {
      label: () => '副标题',
      prop: 'sub_title',
      render: 'input',
      renderProps: { placeholder: '请输入副标题' },
      itemProps: { help: '用于列表展示的简短卖点，可选。' },
      step: 1,
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
          placeholder="请选择分类"
        />
      ),
      itemProps: { rules: [{ required: true, message: '请选择分类' }] },
      step: 1,
    },
    {
      label: () => '品牌',
      prop: 'brand_id',
      render: () => (
        <el-select-v2
          clearable
          placeholder="请选择品牌"
          options={brandOptions.value.map(item => ({
            label: item.label ?? item.name ?? '',
            value: item.value ?? item.id,
          }))}
        />
      ),
      step: 1,
    },
    {
      label: () => '状态',
      prop: 'status',
      render: () => (
        <el-radio-group>
          <el-radio value="draft">草稿</el-radio>
          <el-radio value="active">上架</el-radio>
          <el-radio value="inactive">下架</el-radio>
          <el-radio value="sold_out">售罄</el-radio>
        </el-radio-group>
      ),
      step: 1,
    },
    {
      label: () => '是否推荐',
      prop: 'is_recommend',
      render: () => <el-switch active-value={true} inactive-value={false} />,
      step: 1,
    },
    {
      label: () => '是否热销',
      prop: 'is_hot',
      render: () => <el-switch active-value={true} inactive-value={false} />,
      step: 1,
    },
    {
      label: () => '是否新品',
      prop: 'is_new',
      render: () => <el-switch active-value={true} inactive-value={false} />,
      step: 1,
    },
    {
      label: () => '主图',
      prop: 'main_image',
      render: () => MaUploadImage,
      step: 2,
    },
    {
      label: () => '图集',
      prop: 'gallery_images',
      render: () => MaUploadImage,
      renderProps: {
        multiple: true,
        limit: 8,
      },
      itemProps: { help: '最多 8 张，第一张将作为默认主图。' },
      step: 2,
    },
    {
      label: () => '简介',
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: '请输入简介' },
      step: 4,
    },
    {
      label: () => '详情',
      prop: 'detail_content',
      render: () => (
        <MaRichEditor
          modelValue={model.detail_content || ''}
          placeholder="请输入详情内容"
          height={360}
          onUpdate:modelValue={(val: string) => model.detail_content = val}
        />
      ),
      itemProps: { help: '可粘贴简单图文描述，后续可按需升级富文本。' },
      step: 4,
    },
    {
      label: () => '最低价（元）',
      prop: 'min_price',
      render: 'inputNumber',
      renderProps: { min: 0, precision: 2, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: () => '最高价（元）',
      prop: 'max_price',
      render: 'inputNumber',
      renderProps: { min: 0, precision: 2, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: () => '虚拟销量',
      prop: 'virtual_sales',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: () => '真实销量',
      prop: 'real_sales',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: () => '排序',
      prop: 'sort',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
      cols: { md: 12, xs: 24 },
      step: 2,
    },
    {
      label: () => '商品属性',
      prop: 'attributes',
      render: () => (
        <div class="w-full">
          <div class="mb-2 flex items-center justify-between">
            <div />
            <el-button type="primary" plain size="small" onClick={addAttribute}>
              新增属性
            </el-button>
          </div>
          <el-table data={model.attributes || []} size="small" border>
            <el-table-column label="属性名" min-width="160">
              {{
                default: ({ row }: { row: ProductAttributeVo }) => (
                  <el-input
                    modelValue={row.attribute_name}
                    onUpdate:modelValue={(val: string) => row.attribute_name = val}
                    placeholder="属性名"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label="属性值" min-width="220">
              {{
                default: ({ row }: { row: ProductAttributeVo }) => (
                  <el-input
                    modelValue={row.value}
                    onUpdate:modelValue={(val: string) => row.value = val}
                    placeholder="属性值"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label="操作" width="90">
              {{
                default: ({ $index }: { $index: number }) => (
                  <el-button type="danger" link onClick={() => removeAttribute($index)}>
                    删除
                  </el-button>
                ),
              }}
            </el-table-column>
          </el-table>
        </div>
      ),
      itemProps: { help: '用于商品参数展示，例如材质、产地等。' },
      step: 3,
    },
    {
      label: () => '规格配置',
      prop: 'specs',
      render: () => (
        <div class="w-full">
          <div class="mb-2 flex items-center justify-between">
            <div />
            <el-button type="primary" plain size="small" onClick={addSpec}>
              新增规格
            </el-button>
          </div>
          <el-table data={specItems.value} size="small" border>
            <el-table-column label="规格名" min-width="160">
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
                      placeholder="输入后回车生成标签"
                    />
                  </div>
                ),
              }}
            </el-table-column>
            <el-table-column label="规格值" min-width="320">
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
                      placeholder="输入后回车生成标签"
                    />
                  </div>
                ),
              }}
            </el-table-column>
            <el-table-column label="操作" width="90">
              {{
                default: ({ $index }: { $index: number }) => (
                  <el-button type="danger" link onClick={() => removeSpec($index)}>
                    删除
                  </el-button>
                ),
              }}
            </el-table-column>
          </el-table>
        </div>
      ),
      itemProps: { help: '规格值以标签形式输入，自动生成 SKU。' },
      step: 3,
    },
    {
      label: () => '规格与库存',
      prop: 'skus',
      render: () => (
        <div class="w-full">
          <div class="mb-2 flex items-center justify-between">
            <div />
            <div class="flex flex-wrap items-center gap-2">
              <el-button type="primary" plain size="small" onClick={() => batchSet('cost_price', '成本价')}>
                批量设置成本价
              </el-button>
              <el-button type="primary" plain size="small" onClick={() => batchSet('market_price', '市场价')}>
                批量设置市场价
              </el-button>
              <el-button type="primary" plain size="small" onClick={() => batchSet('sale_price', '销售价')}>
                批量设置销售价
              </el-button>
              <el-button type="primary" plain size="small" onClick={() => batchSet('stock', '库存', true)}>
                批量设置库存
              </el-button>
              <el-button type="primary" plain size="small" onClick={() => batchSet('warning_stock', '预警库存', true)}>
                批量设置预警库存
              </el-button>
            </div>
          </div>
          <el-table data={model.skus || []} size="small" border>
            {formType === 'edit' && (
              <el-table-column label="SKU编码" min-width="140">
                {{
                  default: ({ row }: { row: ProductSkuVo }) => (
                    <el-input
                      modelValue={row.sku_code}
                      onUpdate:modelValue={(val: string) => row.sku_code = val}
                      placeholder="SKU编码"
                      disabled
                    />
                  ),
                }}
              </el-table-column>
            )}
            <el-table-column label="SKU名称" min-width="160">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input
                    modelValue={row.sku_name}
                    onUpdate:modelValue={(val: string) => row.sku_name = val}
                    placeholder="SKU名称"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label="规格值" min-width="160">
              {{
                default: ({ row }: { row: ProductSkuVo }) => (
                  <el-input
                    modelValue={Array.isArray(row.spec_values) ? row.spec_values.join(',') : (row.spec_values as any)}
                    onUpdate:modelValue={(val: string) => {
                      row.spec_values = val.split(',').map(item => item.trim()).filter(Boolean)
                    }}
                    placeholder="例：红色,XL"
                  />
                ),
              }}
            </el-table-column>
            <el-table-column label="图片" width="120">
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
            <el-table-column label="成本价（元）" width="110">
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
            <el-table-column label="市场价（元）" width="110">
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
            <el-table-column label="销售价（元）" width="110">
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
            <el-table-column label="库存" width="100">
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
            <el-table-column label="预警库存" width="110">
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
            <el-table-column label="重量" width="100">
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
            <el-table-column label="状态" width="90">
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
            <el-table-column label="操作" width="80">
              {{
                default: ({ $index }: { $index: number }) => (
                  <el-button type="danger" link onClick={() => removeSku($index)}>
                    删除
                  </el-button>
                ),
              }}
            </el-table-column>
          </el-table>
        </div>
      ),
      itemProps: { help: '生成后可微调价格与库存。' },
      show: () => specItems.value.some(item => item.nameTags?.[0] && item.values.length > 0) || (model.skus?.length ?? 0) > 0,
      step: 3,
    },
  ]

  const showStep = (step: number) => () => activeStep.value === step
  const showWithStep = (step: number) => (item: MaFormItem) => ({ ...item, show: showStep(step) })

  const step1 = items.filter(item => (item as any).step === 1).map(showWithStep(1))
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
