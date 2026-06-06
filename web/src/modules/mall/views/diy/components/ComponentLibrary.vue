<script setup lang="ts">
import { componentRegistry } from '../schema/componentRegistry'

const emit = defineEmits<{
  add: [type: string]
}>()
</script>

<template>
  <aside class="component-library">
    <div class="component-library__head">组件库</div>
    <button
      v-for="item in componentRegistry"
      :key="item.type"
      class="component-library__item"
      type="button"
      @click="emit('add', item.type)"
    >
      <ma-svg-icon :name="item.icon" size="18" />
      <span class="component-library__meta">
        <strong>{{ item.name }}</strong>
        <small>{{ item.description }}</small>
      </span>
    </button>
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
</style>
