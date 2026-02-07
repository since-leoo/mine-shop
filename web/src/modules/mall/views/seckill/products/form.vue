<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import type { MaFormExpose } from '@mineadmin/form'
import type { SeckillProductVo } from '~/mall/api/seckill'
import type { ProductVo } from '~/mall/api/product'
import { productCreate, productUpdate } from '~/mall/api/seckill'
import { page as mallProductPage, detail as mallProductDetail } from '~/mall/api/product'
import getFormItems from './data/getFormItems.tsx'
import useForm from '@/hooks/useForm.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { centsToYuan, yuanToCents } from '@/utils/price'

defineOptions({ name: 'mall:seckill:product:form' })

const { formType = 'add', data = null, sessionId = 0, activityId = 0 } = defineProps<{
  formType: 'add' | 'edit'
  data?: SeckillProductVo | null
  sessionId: number
  activityId: number
}>()

const formRef = ref<MaFormExpose>()
const model = ref<SeckillProductVo>({})
const productOptions = ref<ProductVo[]>([])
const skuOptions = ref<{ id: number; label: string; price: number }[]>([])

async function loadProducts() {
  try {
    const res = await mallProductPage({ page: 1, page_size: 500 })
    productOptions.value = res.data.list || []
  }
  catch (e) {
    console.error('Failed to load products', e)
  }
}

async function loadSkus(productId?: number) {
  if (!productId) {
    skuOptions.value = []
    return
  }
  try {
    const res = await mallProductDetail(productId)
    skuOptions.value = (res.data.skus || []).map((sku: any) => ({
      id: sku.id,
      label: sku.sku_name || `SKU-${sku.id}`,
      price: centsToYuan(sku.sale_price),
    }))
  }
  catch (e) {
    console.error('Failed to load skus', e)
    skuOptions.value = []
  }
}

function onProductChange(productId?: number) {
  model.value.product_id = productId
  model.value.product_sku_id = undefined
  model.value.original_price = undefined
  skuOptions.value = []
  if (productId) {
    loadSkus(productId)
  }
}

function onSkuChange(skuId?: number) {
  model.value.product_sku_id = skuId
  const sku = skuOptions.value.find(s => s.id === skuId)
  if (sku) {
    model.value.original_price = sku.price
  }
}

useForm('productForm').then(async (form: MaFormExpose) => {
  formRef.value = form
  if (formType === 'edit' && data) {
    Object.assign(model.value, data)
    // API返回分，表单显示元
    model.value.original_price = centsToYuan(data.original_price) as any
    model.value.seckill_price = centsToYuan(data.seckill_price) as any
  }
  model.value.session_id = sessionId
  model.value.activity_id = activityId
  if (formType === 'add') {
    await loadProducts()
  }
  form.setItems(getFormItems(formType, model.value, productOptions, skuOptions, onProductChange, onSkuChange))
  form.setOptions({ labelWidth: '90px' })
})

function buildPayload(): SeckillProductVo {
  const payload = { ...model.value }
  payload.original_price = yuanToCents(payload.original_price) as any
  payload.seckill_price = yuanToCents(payload.seckill_price) as any
  return payload
}

function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    productCreate(buildPayload()).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    productUpdate(model.value.id as number, buildPayload()).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch(reject)
  })
}

defineExpose({ add, edit, maForm: formRef })
</script>

<template>
  <ma-form ref="productForm" v-model="model" />
</template>

<style scoped lang="scss">

</style>
