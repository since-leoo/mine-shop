<script setup lang="ts">
import { ref } from 'vue'
import ProductForm from '../../../src/modules/mall/views/product/form.vue'

const form = ref<InstanceType<typeof ProductForm>>()
const saved = ref(false)
async function save() {
  await form.value?.edit()
  saved.value = true
}
</script>
<template>
  <ProductForm ref="form" form-type="edit" :data="{ id: 8 }" />
  <el-button type="primary" @click="save">保存商品</el-button>
  <output v-if="saved">保存完成</output>
</template>
