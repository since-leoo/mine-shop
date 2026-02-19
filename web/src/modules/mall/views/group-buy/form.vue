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
import type { GroupBuyVo } from '~/mall/api/group-buy'
import type { ProductVo } from '~/mall/api/product'
import { create, save } from '~/mall/api/group-buy'
import { detail as productDetail, page as productPage } from '~/mall/api/product'
import getFormItems from './data/getFormItems.tsx'
import useForm from '@/hooks/useForm.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { centsToYuan, yuanToCents } from '@/utils/price'
import { useI18n } from 'vue-i18n'

defineOptions({ name: 'mall:group-buy:form' })

const { t } = useI18n()

const { formType = 'add', data = null } = defineProps<{
  formType: 'add' | 'edit'
  data?: GroupBuyVo | null
}>()

const formRef = ref<MaFormExpose>()
const model = ref<GroupBuyVo>({})
const productOptions = ref<ProductVo[]>([])
const skuOptions = ref<{ id: number; label: string }[]>([])

async function loadProducts() {
  const res = await productPage({ page: 1, page_size: 200 })
  productOptions.value = res.data.list || []
}

async function loadSkus(productId?: number) {
  if (!productId) {
    skuOptions.value = []
    return
  }
  const res = await productDetail(productId)
  skuOptions.value = (res.data.skus || []).map((sku: any) => ({
    id: sku.id,
    label: sku.sku_name || `SKU-${sku.id}`,
  }))
}

useForm('groupBuyForm').then(async (form: MaFormExpose) => {
  formRef.value = form
  if (formType === 'edit' && data) {
    Object.assign(model.value, data)
    // API返回分，表单显示元
    model.value.original_price = centsToYuan(data.original_price) as any
    model.value.group_price = centsToYuan(data.group_price) as any
    await loadSkus(model.value.product_id as number)
  }
  await loadProducts()
  form.setItems(getFormItems(formType, model.value, productOptions, skuOptions, loadSkus, t))
  form.setOptions({ labelWidth: '110px' })
})

function buildPayload(): GroupBuyVo {
  const payload = { ...model.value }
  // 表单中金额为元，提交时转换为分
  payload.original_price = yuanToCents(payload.original_price) as any
  payload.group_price = yuanToCents(payload.group_price) as any
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
  <ma-form ref="groupBuyForm" v-model="model" />
</template>

<style scoped lang="scss">

</style>
