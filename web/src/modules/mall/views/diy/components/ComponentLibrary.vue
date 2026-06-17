<script setup lang="ts">
import { computed, ref } from 'vue'
import { componentRegistry } from '../schema/componentRegistry'
import type { DiyComponentCategory, DiyComponentOrientation } from '../schema/types'

const emit = defineEmits<{
  add: [type: string]
}>()

const activeCategory = ref<DiyComponentCategory>('base')

const categories: Array<{ value: DiyComponentCategory, label: string, desc: string, icon: string }> = [
  { value: 'base', label: '基础', desc: '结构与文本', icon: 'ph:layout' },
  { value: 'user', label: '用户', desc: '店铺与会员', icon: 'ph:user-circle' },
  { value: 'ad', label: '广告', desc: '图片与活动', icon: 'ph:image-square' },
  { value: 'marketing', label: '营销', desc: '商品与促销', icon: 'ph:shopping-bag' },
]

const orientationText: Record<DiyComponentOrientation, string> = {
  horizontal: '横屏',
  vertical: '竖屏',
  both: '横竖屏',
}

const currentComponents = computed(() => componentRegistry.filter(item => item.category === activeCategory.value))
const currentCategory = computed(() => categories.find(item => item.value === activeCategory.value) || categories[0])
</script>

<template>
  <aside class="component-library">
    <div class="component-library__head">
      <div>
        <strong>组件库</strong>
        <span>{{ currentCategory.desc }} · {{ currentComponents.length }} 个组件</span>
      </div>
    </div>
    <div class="component-library__tabs">
      <button
        v-for="item in categories"
        :key="item.value"
        type="button"
        :class="{ 'is-active': activeCategory === item.value }"
        @click="activeCategory = item.value"
      >
        <ma-svg-icon :name="item.icon" size="16" />
        <span>
          <strong>{{ item.label }}</strong>
          <small>{{ item.desc }}</small>
        </span>
      </button>
    </div>
    <div class="component-library__list">
      <button
        v-for="item in currentComponents"
        :key="item.type"
        class="component-library__item"
        type="button"
        @click="emit('add', item.type)"
      >
        <span class="component-library__icon">
          <ma-svg-icon :name="item.icon" size="18" />
        </span>
        <span class="component-library__meta">
          <span class="component-library__line">
            <strong>{{ item.name }}</strong>
            <em>{{ orientationText[item.orientation] }}</em>
          </span>
          <small>{{ item.description }}</small>
        </span>
        <ma-svg-icon class="component-library__add-icon" name="ph:plus-circle" size="18" />
      </button>
    </div>
    <div v-if="currentComponents.length === 0" class="component-library__empty">
      该分类暂无组件
    </div>
  </aside>
</template>

<style scoped lang="scss">
.component-library {
  width: 268px;
  border-right: 1px solid #e5eaf2;
  background: #f8fafc;
  overflow: auto;
}

.component-library__head {
  min-height: 58px;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  background: #fff;
  border-bottom: 1px solid #eef2f7;

  div {
    display: flex;
    flex-direction: column;
    gap: 3px;
  }

  strong {
    font-size: 15px;
    line-height: 20px;
    color: #0f172a;
  }

  span {
    font-size: 12px;
    line-height: 16px;
    color: #64748b;
  }
}

.component-library__tabs {
  padding: 12px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;

  button {
    min-height: 54px;
    padding: 8px;
    border: 1px solid #d8dee8;
    border-radius: 8px;
    background: #fff;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 8px;
    text-align: left;
    cursor: pointer;
    transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;

    span {
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    strong {
      font-size: 12px;
      line-height: 16px;
      color: #0f172a;
    }

    small {
      overflow: hidden;
      font-size: 11px;
      line-height: 14px;
      color: #64748b;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    &.is-active {
      border-color: #409eff;
      background: #edf5ff;
      color: #1d4ed8;
      box-shadow: 0 6px 18px rgba(37, 99, 235, 0.10);
    }
  }
}

.component-library__list {
  padding: 0 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.component-library__item {
  width: 100%;
  min-height: 66px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid #e5eaf2;
  border-radius: 8px;
  background: #fff;
  color: #1f2937;
  text-align: left;
  cursor: pointer;
  transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;

  &:hover {
    border-color: #93c5fd;
    background: #f7fbff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);

    .component-library__add-icon {
      opacity: 1;
      color: #2563eb;
    }
  }
}

.component-library__icon {
  width: 34px;
  height: 34px;
  flex: none;
  border-radius: 8px;
  background: #eef5ff;
  color: #2563eb;
  display: inline-flex;
  align-items: center;
  justify-content: center;
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
  flex: 1;
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

.component-library__add-icon {
  flex: none;
  color: #94a3b8;
  opacity: 0;
  transition: opacity 0.18s ease, color 0.18s ease;
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
