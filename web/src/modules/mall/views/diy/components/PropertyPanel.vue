<script setup lang="ts">
import type { DiyComponent } from '../schema/types'
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps<{
  component?: DiyComponent | null
}>()

const emit = defineEmits<{
  update: [component: DiyComponent]
}>()

const propsJson = ref('{}')
const dataJson = ref('{}')
const styleJson = ref('{}')

const title = computed(() => props.component ? `${props.component.name} / ${props.component.type}` : '未选择组件')

watch(() => props.component, (component) => {
  propsJson.value = JSON.stringify(component?.props || {}, null, 2)
  dataJson.value = JSON.stringify(component?.data || {}, null, 2)
  styleJson.value = JSON.stringify(component?.style || {}, null, 2)
}, { immediate: true, deep: true })

function patch(payload: Partial<DiyComponent>) {
  if (!props.component)
    return
  emit('update', { ...props.component, ...payload })
}

function applyJson() {
  if (!props.component)
    return

  try {
    patch({
      props: JSON.parse(propsJson.value || '{}'),
      data: JSON.parse(dataJson.value || '{}'),
      style: JSON.parse(styleJson.value || '{}'),
    })
    ElMessage.success('属性已更新')
  }
  catch {
    ElMessage.error('JSON 格式不正确')
  }
}
</script>

<template>
  <aside class="property-panel">
    <div class="property-panel__head">{{ title }}</div>
    <el-empty v-if="!component" description="请选择组件" />
    <template v-else>
      <el-form label-position="top" class="property-panel__form">
        <el-form-item label="组件名称">
          <el-input :model-value="component.name" @update:model-value="patch({ name: String($event) })" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch :model-value="component.enabled !== false" @update:model-value="patch({ enabled: Boolean($event) })" />
        </el-form-item>
        <el-form-item label="Props JSON">
          <el-input v-model="propsJson" type="textarea" :rows="7" spellcheck="false" />
        </el-form-item>
        <el-form-item label="Data JSON">
          <el-input v-model="dataJson" type="textarea" :rows="10" spellcheck="false" />
        </el-form-item>
        <el-form-item label="Style JSON">
          <el-input v-model="styleJson" type="textarea" :rows="5" spellcheck="false" />
        </el-form-item>
        <el-button type="primary" @click="applyJson">应用属性</el-button>
      </el-form>
    </template>
  </aside>
</template>

<style scoped lang="scss">
.property-panel {
  width: 340px;
  border-left: 1px solid #e5e7eb;
  background: #fff;
  overflow: auto;
}

.property-panel__head {
  height: 48px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
  font-weight: 600;
}

.property-panel__form {
  padding: 16px;
}
</style>
