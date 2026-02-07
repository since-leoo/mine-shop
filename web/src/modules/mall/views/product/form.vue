<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import type { FormInstance } from 'element-plus'
import type { ProductAttributeVo, ProductSkuVo, ProductVo } from '~/mall/api/product'
import { create, detail, save } from '~/mall/api/product'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { centsToYuan, yuanToCents } from '@/utils/price'
import MaUploadImage from '@/components/ma-upload-image/index.vue'
import MaRichEditor from '@/components/ma-rich-editor/index.vue'

defineOptions({ name: 'mall:product:form' })

/** 从 spec_values 元素中提取纯字符串（后端存储格式为 {name, value} 对象） */
function specValStr(v: any): string {
  return typeof v === 'object' && v !== null ? (v.value ?? v.name ?? String(v)) : String(v)
}

type ProductFormModel = ProductVo & { skus?: ProductSkuVo[] }

const { formType = 'add', data = null } = defineProps<{
  formType: 'add' | 'edit'
  data?: ProductVo | null
}>()

const elFormRef = ref<FormInstance>()
const model = ref<ProductFormModel>({})
const activeStep = ref(1)
const msg = useMessage()

// Options
const categoryOptions = ref<any[]>([])
const brandOptions = ref<{ value?: number; label?: string; id?: number; name?: string }[]>([])
const shippingTemplateOptions = ref<{ id: number; name: string }[]>([])

// Specs
type SpecItem = { nameTags: string[]; values: string[] }
const specItems = ref<SpecItem[]>([])

// Init defaults
if (formType === 'add') {
  model.value = {
    status: 'draft',
    is_recommend: false,
    is_hot: false,
    is_new: false,
    sort: 0,
    virtual_sales: 0,
    real_sales: 0,
    freight_type: 'default',
    flat_freight_amount: 0,
    min_price: 0,
    max_price: 0,
    skus: [],
    attributes: [],
    gallery_images: [],
  }
}

// Load options
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

// Load detail for edit
async function loadDetail() {
  if (formType !== 'edit' || !data?.id) return
  const res = await detail(data.id)
  const d = res.data
  Object.assign(model.value, {
    ...d,
    min_price: centsToYuan(d.min_price),
    max_price: centsToYuan(d.max_price),
    virtual_sales: d.virtual_sales ? Number(d.virtual_sales) : 0,
    real_sales: d.real_sales ? Number(d.real_sales) : 0,
    sort: d.sort ? Number(d.sort) : 0,
    freight_type: d.freight_type ?? 'default',
    flat_freight_amount: centsToYuan(d.flat_freight_amount),
    shipping_template_id: d.shipping_template_id ?? undefined,
    skus: d.skus?.map((sku: any) => ({
      ...sku,
      cost_price: centsToYuan(sku.cost_price),
      market_price: centsToYuan(sku.market_price),
      sale_price: centsToYuan(sku.sale_price),
      stock: sku.stock ? Number(sku.stock) : 0,
      warning_stock: sku.warning_stock ? Number(sku.warning_stock) : 0,
      weight: sku.weight ? Number(sku.weight) : 0,
      spec_values: Array.isArray(sku.spec_values)
        ? sku.spec_values.map((v: any) => specValStr(v))
        : sku.spec_values,
    })) || [],
  })
  if (model.value.gallery_images) {
    model.value.gallery_images = model.value.gallery_images.filter(Boolean)
  }
  if (!model.value.gallery_images?.length && d.gallery?.length) {
    model.value.gallery_images = d.gallery.map((item: any) => item.image_url || '').filter(Boolean)
  }
  // Rebuild specs from SKUs
  if (model.value.skus && model.value.skus.length > 0) {
    const specMap = new Map<number, { name: string; values: Set<string> }>()
    model.value.skus.forEach((sku) => {
      if (Array.isArray(sku.spec_values)) {
        sku.spec_values.forEach((raw: any, index: number) => {
          if (!specMap.has(index)) {
            const specName = typeof raw === 'object' && raw !== null ? (raw.name ?? `规格${index + 1}`) : `规格${index + 1}`
            specMap.set(index, { name: specName, values: new Set() })
          }
          specMap.get(index)!.values.add(specValStr(raw))
        })
      }
    })
    if (specMap.size > 0) {
      specItems.value = Array.from(specMap.entries()).map(([_, spec]) => ({
        nameTags: [spec.name],
        values: Array.from(spec.values),
      }))
    }
  }
}

if (formType === 'edit' && data) {
  Object.assign(model.value, data)
  loadDetail()
}

// SKU helpers
function addSpec() { specItems.value.push({ nameTags: [], values: [] }) }
function removeSpec(index: number) { specItems.value.splice(index, 1); updateSkusFromSpecs() }
function addAttribute() { model.value.attributes?.push({ attribute_name: '', value: '' } as ProductAttributeVo) }
function removeAttribute(index: number) { model.value.attributes?.splice(index, 1) }

function batchSet(field: keyof ProductSkuVo, label: string, isInt = false) {
  msg.prompt(`请输入${label}`, '', label, (value) => {
    if (value === '' || value === null || value === undefined) return '请输入有效数值'
    return Number.isNaN(Number(value)) ? '请输入数字' : true
  }).then(({ value }) => {
    const next = isInt ? parseInt(value, 10) : Number(value)
    if (!Number.isNaN(next)) model.value.skus?.forEach((sku) => { (sku as any)[field] = next })
  }).catch(() => {})
}

function updateSkusFromSpecs() {
  const specs = specItems.value
    .map(item => ({ name: item.nameTags?.[0]?.trim() ?? '', values: item.values.map(v => v.trim()).filter(Boolean) }))
    .filter(item => item.name && item.values.length > 0)
  if (!specs.length) { model.value.skus = model.value.skus?.length ? model.value.skus : []; return }
  const cartesian = specs.reduce<string[][]>((acc, spec) => {
    if (acc.length === 0) return spec.values.map(v => [v])
    const next: string[][] = []
    acc.forEach(combo => spec.values.forEach(v => next.push([...combo, v])))
    return next
  }, [])
  const base = model.value.skus?.[0] || {}
  if (formType === 'edit' && model.value.skus && model.value.skus.length > 0) {
    const existingMap = new Map<string, ProductSkuVo>()
    model.value.skus.forEach(sku => {
      const key = Array.isArray(sku.spec_values) ? sku.spec_values.map((v: any) => specValStr(v)).join('/') : String(sku.spec_values || '')
      existingMap.set(key, sku)
    })
    model.value.skus = cartesian.map((values, i) => {
      const existing = existingMap.get(values.join('/'))
      if (existing) return { ...existing, sku_name: values.join('/'), spec_values: values }
      return { sku_code: model.value.product_code ? `${model.value.product_code}-${i + 1}` : '', sku_name: values.join('/'), spec_values: values, image: (base as ProductSkuVo).image, cost_price: base.cost_price ?? 0, market_price: base.market_price ?? 0, sale_price: base.sale_price ?? 0, stock: base.stock ?? 0, warning_stock: base.warning_stock ?? 0, weight: base.weight ?? 0, status: base.status ?? 'active' } as ProductSkuVo
    })
  } else {
    model.value.skus = cartesian.map((values, i) => ({
      sku_code: model.value.product_code ? `${model.value.product_code}-${i + 1}` : '', sku_name: values.join('/'), spec_values: values, image: (base as ProductSkuVo).image, cost_price: base.cost_price ?? 0, market_price: base.market_price ?? 0, sale_price: base.sale_price ?? 0, stock: base.stock ?? 0, warning_stock: base.warning_stock ?? 0, weight: base.weight ?? 0, status: base.status ?? 'active',
    } as ProductSkuVo))
  }
}

function normalizeSkus(skus: ProductSkuVo[] = []): ProductSkuVo[] {
  return skus.filter(item => item.sku_name).map(item => {
    const next: ProductSkuVo = { ...item }
    if (typeof (next as any).spec_values === 'string') {
      next.spec_values = (next as any).spec_values.split(',').map((v: string) => v.trim()).filter(Boolean)
    }
    return next
  })
}

function buildPayload(): ProductVo {
  const payload: ProductVo = { ...model.value }
  payload.min_price = yuanToCents(payload.min_price) as any
  payload.max_price = yuanToCents(payload.max_price) as any
  payload.flat_freight_amount = yuanToCents(payload.flat_freight_amount) as any
  payload.skus = normalizeSkus(payload.skus || []).map(sku => ({
    ...sku, cost_price: yuanToCents(sku.cost_price), market_price: yuanToCents(sku.market_price), sale_price: yuanToCents(sku.sale_price),
  }))
  if (payload.gallery_images) {
    payload.gallery = payload.gallery_images.map((url, index) => ({ image_url: url, sort_order: index, is_primary: index === 0 }))
  }
  delete (payload as any).specs
  return payload
}

function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    create(buildPayload()).then((res: any) => { res.code === ResultCode.SUCCESS ? resolve(res) : reject(res) }).catch(reject)
  })
}
function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    save(model.value.id as number, buildPayload()).then((res: any) => { res.code === ResultCode.SUCCESS ? resolve(res) : reject(res) }).catch(reject)
  })
}

const hasSpecs = computed(() => specItems.value.some(s => s.nameTags?.[0] && s.values.length > 0) || (model.value.skus?.length ?? 0) > 0)

const steps = [
  { title: '基础信息' },
  { title: '价格与展示' },
  { title: '属性与规格' },
  { title: '简介与详情' },
]

// Expose for parent drawer
defineExpose({
  add,
  edit,
  maForm: { getElFormRef: () => elFormRef.value },
})
</script>

<template>
  <div class="product-form-page">
    <!-- Steps -->
    <div class="steps-nav">
      <div
        v-for="(step, i) in steps"
        :key="i"
        class="step-tab"
        :class="{ active: activeStep === i + 1, done: activeStep > i + 1 }"
        @click="activeStep = i + 1"
      >
        <span class="step-dot">{{ activeStep > i + 1 ? '✓' : i + 1 }}</span>
        <span>{{ step.title }}</span>
      </div>
    </div>

    <el-form ref="elFormRef" :model="model" label-width="100px" class="form-body" scroll-to-error>
      <!-- ========== Step 1: 基础信息 ========== -->
      <div v-show="activeStep === 1" class="step-panel">
        <el-card shadow="never" class="section-card">
          <template #header><span class="section-title">基本信息</span></template>
          <el-row :gutter="20">
            <el-col v-if="formType === 'edit'" :span="24">
              <el-form-item label="商品编码">
                <el-input v-model="model.product_code" disabled placeholder="自动生成" />
              </el-form-item>
            </el-col>
            <el-col :span="24">
              <el-form-item label="商品名称" prop="name" :rules="[{ required: true, message: '请输入商品名称' }]">
                <el-input v-model="model.name" placeholder="请输入商品名称" />
              </el-form-item>
            </el-col>
            <el-col :span="24">
              <el-form-item label="副标题">
                <el-input v-model="model.sub_title" placeholder="请输入副标题（可选）" />
              </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
              <el-form-item label="分类" prop="category_id" :rules="[{ required: true, message: '请选择分类' }]">
                <el-tree-select
                  v-model="model.category_id"
                  :data="categoryOptions"
                  :props="{ value: 'id', label: 'name' }"
                  check-strictly
                  clearable
                  placeholder="请选择分类"
                  class="w-full"
                />
              </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
              <el-form-item label="品牌">
                <el-select-v2
                  v-model="model.brand_id"
                  clearable
                  placeholder="请选择品牌"
                  class="w-full"
                  :options="brandOptions.map(b => ({ label: b.label ?? b.name ?? '', value: b.value ?? b.id }))"
                />
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>

        <el-card shadow="never" class="section-card">
          <template #header><span class="section-title">状态与标签</span></template>
          <el-row :gutter="20">
            <el-col :span="24">
              <el-form-item label="状态">
                <el-radio-group v-model="model.status">
                  <el-radio value="draft">草稿</el-radio>
                  <el-radio value="active">上架</el-radio>
                  <el-radio value="inactive">下架</el-radio>
                  <el-radio value="sold_out">售罄</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
            <el-col :md="8" :xs="24">
              <el-form-item label="推荐">
                <el-switch v-model="model.is_recommend" />
              </el-form-item>
            </el-col>
            <el-col :md="8" :xs="24">
              <el-form-item label="热销">
                <el-switch v-model="model.is_hot" />
              </el-form-item>
            </el-col>
            <el-col :md="8" :xs="24">
              <el-form-item label="新品">
                <el-switch v-model="model.is_new" />
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>

        <el-card shadow="never" class="section-card">
          <template #header><span class="section-title">运费配置</span></template>
          <el-row :gutter="20">
            <el-col :md="12" :xs="24">
              <el-form-item label="运费类型" prop="freight_type" :rules="[{ required: true, message: '请选择运费类型' }]">
                <el-select v-model="model.freight_type" placeholder="请选择" class="w-full">
                  <el-option label="系统默认" value="default" />
                  <el-option label="免运费" value="free" />
                  <el-option label="统一运费" value="flat" />
                  <el-option label="运费模板" value="template" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col v-if="model.freight_type === 'flat'" :md="12" :xs="24">
              <el-form-item label="运费金额（元）" prop="flat_freight_amount" :rules="[{ required: true, message: '请输入运费金额' }]">
                <el-input-number v-model="model.flat_freight_amount" :min="0" :max="999.99" :precision="2" class="w-full" />
              </el-form-item>
            </el-col>
            <el-col v-if="model.freight_type === 'template'" :md="12" :xs="24">
              <el-form-item label="运费模板" prop="shipping_template_id" :rules="[{ required: true, message: '请选择运费模板' }]">
                <el-select v-model="model.shipping_template_id" placeholder="请选择运费模板" clearable class="w-full">
                  <el-option v-for="t in shippingTemplateOptions" :key="t.id" :label="t.name" :value="t.id" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="24">
              <div class="text-xs text-gray-400">系统默认：使用商城全局运费配置；免运费：不收运费；统一运费：所有地区相同金额；运费模板：按模板规则计算。</div>
            </el-col>
          </el-row>
        </el-card>
      </div>

      <!-- ========== Step 2: 价格与展示 ========== -->
      <div v-show="activeStep === 2" class="step-panel">
        <el-card shadow="never" class="section-card">
          <template #header><span class="section-title">商品图片</span></template>
          <el-row :gutter="20">
            <el-col :md="12" :xs="24">
              <el-form-item label="主图">
                <MaUploadImage v-model="model.main_image" />
              </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
              <el-form-item label="图集">
                <MaUploadImage v-model="model.gallery_images" :multiple="true" :limit="8" />
                <div class="text-xs text-gray-400 mt-1">最多 8 张，第一张将作为默认主图。</div>
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>

        <el-card shadow="never" class="section-card">
          <template #header><span class="section-title">价格与销量</span></template>
          <el-row :gutter="20">
            <el-col :md="12" :xs="24">
              <el-form-item label="最低价（元）">
                <el-input-number v-model="model.min_price" :min="0" :precision="2" class="w-full" />
              </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
              <el-form-item label="最高价（元）">
                <el-input-number v-model="model.max_price" :min="0" :precision="2" class="w-full" />
              </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
              <el-form-item label="虚拟销量">
                <el-input-number v-model="model.virtual_sales" :min="0" class="w-full" />
              </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
              <el-form-item label="真实销量">
                <el-input-number v-model="model.real_sales" :min="0" class="w-full" />
              </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
              <el-form-item label="排序">
                <el-input-number v-model="model.sort" :min="0" class="w-full" />
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>
      </div>

      <!-- ========== Step 3: 属性与规格 ========== -->
      <div v-show="activeStep === 3" class="step-panel">
        <el-card shadow="never" class="section-card">
          <template #header>
            <div class="flex items-center justify-between">
              <span class="section-title">商品属性</span>
              <el-button type="primary" plain size="small" @click="addAttribute">新增属性</el-button>
            </div>
          </template>
          <el-table :data="model.attributes || []" size="small" border>
            <el-table-column label="属性名" min-width="160">
              <template #default="{ row }">
                <el-input v-model="row.attribute_name" placeholder="属性名" />
              </template>
            </el-table-column>
            <el-table-column label="属性值" min-width="220">
              <template #default="{ row }">
                <el-input v-model="row.value" placeholder="属性值" />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="80" align="center">
              <template #default="{ $index }">
                <el-button type="danger" link @click="removeAttribute($index)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div v-if="!model.attributes?.length" class="text-center text-xs text-gray-400 py-4">暂无属性，点击上方按钮添加。</div>
        </el-card>

        <el-card shadow="never" class="section-card">
          <template #header>
            <div class="flex items-center justify-between">
              <span class="section-title">规格配置</span>
              <el-button type="primary" plain size="small" @click="addSpec">新增规格</el-button>
            </div>
          </template>
          <el-table :data="specItems" size="small" border>
            <el-table-column label="规格名" min-width="160">
              <template #default="{ row }">
                <el-select
                  v-model="row.nameTags"
                  multiple
                  :multiple-limit="1"
                  filterable
                  allow-create
                  default-first-option
                  :collapse-tags="false"
                  placeholder="输入后回车"
                  @change="updateSkusFromSpecs"
                />
              </template>
            </el-table-column>
            <el-table-column label="规格值" min-width="320">
              <template #default="{ row }">
                <el-select
                  v-model="row.values"
                  multiple
                  filterable
                  allow-create
                  default-first-option
                  :collapse-tags="false"
                  placeholder="输入后回车"
                  @change="updateSkusFromSpecs"
                />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="80" align="center">
              <template #default="{ $index }">
                <el-button type="danger" link @click="removeSpec($index)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div v-if="!specItems.length" class="text-center text-xs text-gray-400 py-4">暂无规格，点击上方按钮添加。</div>
        </el-card>

        <el-card v-if="hasSpecs" shadow="never" class="section-card">
          <template #header>
            <div class="flex items-center justify-between">
              <span class="section-title">SKU 列表</span>
              <div class="flex flex-wrap gap-1">
                <el-button plain size="small" @click="batchSet('cost_price', '成本价')">批量成本价</el-button>
                <el-button plain size="small" @click="batchSet('market_price', '市场价')">批量市场价</el-button>
                <el-button plain size="small" @click="batchSet('sale_price', '销售价')">批量销售价</el-button>
                <el-button plain size="small" @click="batchSet('stock', '库存', true)">批量库存</el-button>
              </div>
            </div>
          </template>
          <el-table :data="model.skus || []" size="small" border max-height="400">
            <el-table-column v-if="formType === 'edit'" label="SKU编码" min-width="120">
              <template #default="{ row }"><el-input v-model="row.sku_code" disabled /></template>
            </el-table-column>
            <el-table-column label="名称" min-width="140">
              <template #default="{ row }"><el-input v-model="row.sku_name" placeholder="SKU名称" /></template>
            </el-table-column>
            <el-table-column label="规格值" min-width="140">
              <template #default="{ row }">
                <el-input
                  :model-value="Array.isArray(row.spec_values) ? row.spec_values.map((v: any) => specValStr(v)).join(',') : row.spec_values"
                  placeholder="红色,XL"
                  @update:model-value="(v: string) => row.spec_values = v.split(',').map((s: string) => s.trim()).filter(Boolean)"
                />
              </template>
            </el-table-column>
            <el-table-column label="图片" width="90" align="center">
              <template #default="{ row }">
                <MaUploadImage v-model="row.image" :size="50" />
              </template>
            </el-table-column>
            <el-table-column label="成本价" width="130">
              <template #default="{ row }"><el-input-number v-model="row.cost_price" :min="0" :precision="2" class="w-full" controls-position="right" /></template>
            </el-table-column>
            <el-table-column label="市场价" width="130">
              <template #default="{ row }"><el-input-number v-model="row.market_price" :min="0" :precision="2" class="w-full" controls-position="right" /></template>
            </el-table-column>
            <el-table-column label="销售价" width="130">
              <template #default="{ row }"><el-input-number v-model="row.sale_price" :min="0" :precision="2" class="w-full" controls-position="right" /></template>
            </el-table-column>
            <el-table-column label="库存" width="110">
              <template #default="{ row }"><el-input-number v-model="row.stock" :min="0" class="w-full" controls-position="right" /></template>
            </el-table-column>
            <el-table-column label="重量" width="110">
              <template #default="{ row }"><el-input-number v-model="row.weight" :min="0" class="w-full" controls-position="right" /></template>
            </el-table-column>
            <el-table-column label="状态" width="70" align="center">
              <template #default="{ row }"><el-switch v-model="row.status" active-value="active" inactive-value="inactive" /></template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>

      <!-- ========== Step 4: 简介与详情 ========== -->
      <div v-show="activeStep === 4" class="step-panel">
        <el-card shadow="never" class="section-card">
          <template #header><span class="section-title">商品描述</span></template>
          <el-form-item label="简介">
            <el-input v-model="model.description" type="textarea" :rows="3" placeholder="请输入简介" />
          </el-form-item>
          <el-form-item label="详情">
            <MaRichEditor v-model="model.detail_content" placeholder="请输入详情内容" :height="360" />
          </el-form-item>
        </el-card>
      </div>
    </el-form>

    <!-- Footer -->
    <div class="step-footer">
      <el-button v-if="activeStep > 1" @click="activeStep -= 1">上一步</el-button>
      <el-button v-if="activeStep < 4" type="primary" @click="activeStep += 1">下一步</el-button>
    </div>
  </div>
</template>

<style scoped lang="scss">
.product-form-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 110px);
  padding: 0 16px;
}

.steps-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
  margin-bottom: 16px;
  flex-shrink: 0;
}

.step-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: var(--el-text-color-secondary);
  transition: all 0.2s;
  user-select: none;

  &:hover {
    background: var(--el-fill-color-light);
  }

  &.active {
    color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
    font-weight: 600;
  }

  &.done {
    color: var(--el-color-success);
  }
}

.step-dot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
  background: var(--el-fill-color);
  color: var(--el-text-color-secondary);
  flex-shrink: 0;

  .active & {
    background: var(--el-color-primary);
    color: #fff;
  }

  .done & {
    background: var(--el-color-success);
    color: #fff;
  }
}

.form-body {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 16px;
}

.step-panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-card {
  :deep(.el-card__header) {
    padding: 12px 20px;
    background: var(--el-fill-color-lighter);
    border-bottom: 1px solid var(--el-border-color-lighter);
  }

  :deep(.el-card__body) {
    padding: 20px;
  }
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.step-footer {
  display: flex;
  justify-content: center;
  gap: 12px;
  padding: 12px 0;
  border-top: 1px solid var(--el-border-color-lighter);
  flex-shrink: 0;
}
</style>
