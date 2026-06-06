<script setup lang="ts">
import type { DiySchema } from '../schema/types'
import { defaultPageTheme } from '../schema/componentRegistry'

const props = defineProps<{
  schema: DiySchema
}>()

const emit = defineEmits<{
  update: [schema: DiySchema]
}>()

const theme = computed(() => ({
  ...defaultPageTheme,
  ...(props.schema.page.theme || {}),
}))

function patchPage(payload: Record<string, any>) {
  emit('update', {
    ...props.schema,
    page: {
      ...props.schema.page,
      ...payload,
    },
  })
}

function patchTheme(payload: Record<string, any>) {
  patchPage({
    theme: {
      ...theme.value,
      ...payload,
    },
  })
}
</script>

<template>
  <aside class="page-setting-panel">
    <div class="page-setting-panel__head">页面设置</div>
    <el-form label-position="top" class="page-setting-panel__form">
      <el-form-item label="页面标题">
        <el-input :model-value="schema.page.title || ''" @update:model-value="patchPage({ title: String($event) })" />
      </el-form-item>
      <el-divider>主题风格</el-divider>
      <div class="page-setting-panel__grid">
        <el-form-item label="主色">
          <el-color-picker :model-value="theme.primaryColor" @update:model-value="patchTheme({ primaryColor: $event || defaultPageTheme.primaryColor })" />
        </el-form-item>
        <el-form-item label="价格色">
          <el-color-picker :model-value="theme.priceColor" @update:model-value="patchTheme({ priceColor: $event || defaultPageTheme.priceColor })" />
        </el-form-item>
        <el-form-item label="背景色">
          <el-color-picker :model-value="theme.backgroundColor" @update:model-value="patchTheme({ backgroundColor: $event || defaultPageTheme.backgroundColor })" />
        </el-form-item>
        <el-form-item label="卡片圆角">
          <el-input-number :model-value="theme.cardRadius" :min="0" :max="64" @update:model-value="patchTheme({ cardRadius: $event ?? 8 })" />
        </el-form-item>
      </div>
      <el-form-item label="按钮样式">
        <el-segmented
          :model-value="theme.buttonShape"
          :options="[
            { label: '圆角', value: 'round' },
            { label: '方形', value: 'square' },
            { label: '线框', value: 'plain' },
          ]"
          @update:model-value="patchTheme({ buttonShape: $event })"
        />
      </el-form-item>
    </el-form>
  </aside>
</template>

<style scoped lang="scss">
.page-setting-panel {
  width: 320px;
  border-left: 1px solid #e5e7eb;
  background: #fff;
  overflow: auto;
}

.page-setting-panel__head {
  height: 48px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
  font-weight: 600;
}

.page-setting-panel__form {
  padding: 16px;
}

.page-setting-panel__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
</style>
