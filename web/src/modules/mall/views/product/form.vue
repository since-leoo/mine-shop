<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import type { MaFormExpose } from '@mineadmin/form'
import type { ProductSkuVo, ProductVo } from '~/mall/api/product'
import { create, detail, save } from '~/mall/api/product'
import getFormItems from './data/getFormItems.tsx'
import useForm from '@/hooks/useForm.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'mall:product:form' })

type ProductFormModel = ProductVo & {
  skus?: ProductSkuVo[]
}

const { formType = 'add', data = null } = defineProps<{
  formType: 'add' | 'edit'
  data?: ProductVo | null
}>()

const formRef = ref<MaFormExpose>()
const model = ref<ProductFormModel>({})
const activeStep = ref(1)
const msg = useMessage()

async function loadDetail() {
  if (formType !== 'edit' || !data?.id) {
    return
  }
  const res = await detail(data.id)
  
  // 转换数字类型字段
  const normalizedData = {
    ...res.data,
    min_price: res.data.min_price ? Number(res.data.min_price) : 0,
    max_price: res.data.max_price ? Number(res.data.max_price) : 0,
    virtual_sales: res.data.virtual_sales ? Number(res.data.virtual_sales) : 0,
    real_sales: res.data.real_sales ? Number(res.data.real_sales) : 0,
    sort: res.data.sort ? Number(res.data.sort) : 0,
    skus: res.data.skus?.map((sku: any) => ({
      ...sku,
      cost_price: sku.cost_price ? Number(sku.cost_price) : 0,
      market_price: sku.market_price ? Number(sku.market_price) : 0,
      sale_price: sku.sale_price ? Number(sku.sale_price) : 0,
      stock: sku.stock ? Number(sku.stock) : 0,
      warning_stock: sku.warning_stock ? Number(sku.warning_stock) : 0,
      weight: sku.weight ? Number(sku.weight) : 0,
    })) || [],
  }
  
  Object.assign(model.value, normalizedData)
  
  if (model.value.gallery_images) {
    model.value.gallery_images = model.value.gallery_images.filter(Boolean)
  }
  if (!model.value.gallery_images?.length && res.data?.gallery?.length) {
    model.value.gallery_images = res.data.gallery.map(item => item.image_url || '').filter(Boolean)
  }
}

useForm('productForm').then(async (form: MaFormExpose) => {
  formRef.value = form
  if (formType === 'edit' && data) {
    Object.assign(model.value, data)
    await loadDetail()
  }
  form.setItems(getFormItems(formType, model.value, activeStep, msg))
  form.setOptions({ labelWidth: '110px' })
})


function normalizeSkus(skus: ProductSkuVo[] = []): ProductSkuVo[] {
  return skus
    .filter(item => item.sku_name)
    .map((item) => {
      const next: ProductSkuVo = { ...item }
      if (typeof (next as any).spec_values === 'string') {
        next.spec_values = (next as any).spec_values.split(',').map((val: string) => val.trim()).filter(Boolean)
      }
      return next
    })
}

function buildPayload(): ProductVo {
  const payload: ProductVo = { ...model.value }
  payload.skus = normalizeSkus(payload.skus || [])
  if (payload.gallery_images) {
    payload.gallery = payload.gallery_images.map((url, index) => ({
      image_url: url,
      sort_order: index,
      is_primary: index === 0,
    }))
  }
  delete (payload as any).specs
  return payload
}

function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    create(buildPayload()).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    save(model.value.id as number, buildPayload()).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

defineExpose({ add, edit, maForm: formRef })
</script>

<template>
  <div class="px-2">
    <el-steps :active="activeStep" align-center class="mb-4">
      <el-step title="基础信息" description="名称、分类、状态" />
      <el-step title="价格与展示" description="图片、价格、销量" />
      <el-step title="属性与规格" description="属性、SKU、库存" />
      <el-step title="简介与详情" description="简述、富文本详情" />
    </el-steps>
    <ma-form ref="productForm" v-model="model" />
    <div class="mt-4 flex items-center justify-end gap-2">
      <el-button v-if="activeStep > 1" @click="activeStep -= 1">
        上一步
      </el-button>
      <el-button v-if="activeStep < 4" type="primary" @click="activeStep += 1">
        下一步
      </el-button>
    </div>
  </div>
</template>

<style scoped lang="scss">

</style>
