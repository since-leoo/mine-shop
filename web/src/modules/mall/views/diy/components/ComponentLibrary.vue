<script setup lang="ts">
import { computed, ref } from 'vue'
import { componentRegistry } from '../schema/componentRegistry'
import type { DiyComponentCategory, DiyComponentOrientation } from '../schema/types'

const emit = defineEmits<{
  add: [type: string]
}>()

const activeCategory = ref<DiyComponentCategory>('base')

const categories: Array<{ value: DiyComponentCategory, label: string }> = [
  { value: 'base', label: '基础信息' },
  { value: 'user', label: '用户信息' },
  { value: 'ad', label: '广告组件' },
  { value: 'marketing', label: '营销组件' },
]

const orientationText: Record<DiyComponentOrientation, string> = {
  horizontal: '横屏',
  vertical: '竖屏',
  both: '横竖屏',
}

const currentComponents = computed(() => componentRegistry.filter(item => item.category === activeCategory.value))
</script>

<template>
  <aside class="component-library">
    <div class="component-library__head">组件库</div>
    <div class="component-library__tabs">
      <button
        v-for="item in categories"
        :key="item.value"
        type="button"
        :class="{ 'is-active': activeCategory === item.value }"
        @click="activeCategory = item.value"
      >
        {{ item.label }}
      </button>
    </div>
    <button
      v-for="item in currentComponents"
      :key="item.type"
      class="component-library__item"
      type="button"
      @click="emit('add', item.type)"
    >
      <ma-svg-icon :name="item.icon" size="18" />
      <span class="component-library__meta">
        <span class="component-library__line">
          <strong>{{ item.name }}</strong>
          <em>{{ orientationText[item.orientation] }}</em>
        </span>
        <small>{{ item.description }}</small>
      </span>
    </button>
    <div v-if="currentComponents.length === 0" class="component-library__empty">该分类暂无组件</div>
  </aside>
</template>

<style scoped lang="scss">
.component-library {
  width: 240px;
  border-right: 1px solid #e5e7eb;
  background: #fff;
}

.component-library__head {
  height: 48px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  font-size: 14px;
  font-weight: 600;
  border-bottom: 1px solid #e5e7eb;
}

.component-library__tabs {
  padding: 10px 12px 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;

  button {
    height: 30px;
    border: 1px solid #d7dce3;
    border-radius: 6px;
    background: #fff;
    color: #4b5563;
    font-size: 12px;
    cursor: pointer;

    &.is-active {
      border-color: #409eff;
      background: #edf5ff;
      color: #1d4ed8;
      font-weight: 600;
    }
  }
}

.component-library__item {
  width: calc(100% - 24px);
  margin: 12px;
  padding: 12px;
  display: flex;
  gap: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #1f2937;
  text-align: left;
  cursor: pointer;

  &:hover {
    border-color: #409eff;
    background: #f5f9ff;
  }
}

.component-library__line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;

  em {
    flex: none;
    padding: 1px 6px;
    border-radius: 4px;
    background: #f3f4f6;
    color: #4b5563;
    font-size: 11px;
    font-style: normal;
    font-weight: 400;
  }
}

.component-library__meta {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;

  strong {
    font-size: 13px;
    line-height: 18px;
  }

  small {
    font-size: 12px;
    line-height: 16px;
    color: #6b7280;
  }
}

.component-library__empty {
  margin: 16px 12px;
  padding: 18px 10px;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  color: #6b7280;
  font-size: 12px;
  text-align: center;
}
</style>
