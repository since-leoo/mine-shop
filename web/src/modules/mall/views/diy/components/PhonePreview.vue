<script setup lang="ts">
import type { DiyComponent, DiySchema } from '../schema/types'

defineProps<{
  schema: DiySchema
  selectedId?: string
}>()

const emit = defineEmits<{
  select: [id: string]
  moveUp: [id: string]
  moveDown: [id: string]
  copy: [id: string]
  remove: [id: string]
  toggle: [id: string]
}>()

function imageOf(item: any) {
  return item?.image || item?.img || item?.url || ''
}

function products(component: DiyComponent) {
  return component.data?.products || component.data?.items || []
}
</script>

<template>
  <section class="phone-preview">
    <div class="phone-preview__device">
      <div class="phone-preview__status" />
      <div class="phone-preview__title">{{ schema.page.title || schema.page.key }}</div>
      <div class="phone-preview__body">
        <div
          v-for="component in schema.components"
          :key="component.id"
          class="preview-block"
          :class="{ 'preview-block--active': component.id === selectedId, 'preview-block--disabled': component.enabled === false }"
          @click="emit('select', component.id)"
        >
          <div class="preview-block__tools">
            <button type="button" @click.stop="emit('moveUp', component.id)"><ma-svg-icon name="ph:arrow-up" size="13" /></button>
            <button type="button" @click.stop="emit('moveDown', component.id)"><ma-svg-icon name="ph:arrow-down" size="13" /></button>
            <button type="button" @click.stop="emit('copy', component.id)"><ma-svg-icon name="ph:copy" size="13" /></button>
            <button type="button" @click.stop="emit('toggle', component.id)"><ma-svg-icon :name="component.enabled === false ? 'ph:eye-slash' : 'ph:eye'" size="13" /></button>
            <button type="button" @click.stop="emit('remove', component.id)"><ma-svg-icon name="ph:trash" size="13" /></button>
          </div>

          <template v-if="component.type === 'banner'">
            <div class="preview-banner">
              <img v-if="imageOf(component.data?.items?.[0])" :src="imageOf(component.data?.items?.[0])">
              <span v-else>轮播图</span>
            </div>
          </template>

          <template v-else-if="component.type === 'quick-nav'">
            <div class="preview-nav">
              <div v-for="(item, index) in (component.data?.items || []).slice(0, 5)" :key="index" class="preview-nav__item">
                <span class="preview-nav__icon" />
                <em>{{ item.title || item.name || '入口' }}</em>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'image-ad'">
            <div class="preview-image-ad">
              <div v-for="(item, index) in (component.data?.items || []).slice(0, 2)" :key="index">
                <img v-if="imageOf(item)" :src="imageOf(item)">
                <span v-else>广告图</span>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'product-group'">
            <div class="preview-products">
              <div v-for="(item, index) in products(component).slice(0, 4)" :key="index" class="preview-product">
                <span class="preview-product__img" />
                <strong>{{ item.title || item.name || '商品' }}</strong>
                <em>¥{{ item.price || 0 }}</em>
              </div>
              <div v-if="products(component).length === 0" class="preview-empty">商品组</div>
            </div>
          </template>

          <template v-else-if="component.type === 'title-bar'">
            <div class="preview-title">
              <strong>{{ component.props?.title || component.data?.title || component.name }}</strong>
              <span>{{ component.props?.subtitle || component.data?.subtitle }}</span>
            </div>
          </template>

          <template v-else-if="component.type === 'gap'">
            <div class="preview-gap" :style="{ height: `${component.props?.height || 16}px` }" />
          </template>

          <template v-else-if="component.type === 'divider'">
            <div class="preview-divider" />
          </template>

          <template v-else>
            <div class="preview-empty">{{ component.name }}</div>
          </template>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped lang="scss">
.phone-preview {
  flex: 1;
  min-width: 440px;
  padding: 24px 0;
  display: flex;
  justify-content: center;
  overflow: auto;
  background: #f3f4f6;
}

.phone-preview__device {
  width: 390px;
  height: 760px;
  border: 1px solid #d1d5db;
  border-radius: 28px;
  background: #f6f7f8;
  overflow: hidden;
  box-shadow: 0 16px 42px rgba(15, 23, 42, 0.12);
}

.phone-preview__status {
  height: 28px;
  background: #111827;
}

.phone-preview__title {
  height: 46px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  font-size: 15px;
  font-weight: 600;
}

.phone-preview__body {
  height: 686px;
  overflow: auto;
}

.preview-block {
  position: relative;
  min-height: 28px;
  border: 1px solid transparent;
  cursor: pointer;

  &:hover,
  &--active {
    border-color: #409eff;
  }
}

.preview-block--disabled {
  opacity: 0.45;
}

.preview-block__tools {
  position: absolute;
  z-index: 2;
  top: 4px;
  right: 4px;
  display: none;
  gap: 3px;

  button {
    width: 22px;
    height: 22px;
    border: 0;
    border-radius: 4px;
    background: rgba(17, 24, 39, 0.72);
    color: #fff;
    cursor: pointer;
  }
}

.preview-block:hover .preview-block__tools,
.preview-block--active .preview-block__tools {
  display: flex;
}

.preview-banner {
  margin: 12px;
  height: 150px;
  border-radius: 10px;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.preview-nav {
  margin: 10px 12px;
  padding: 14px 4px;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  background: #fff;
  border-radius: 10px;
}

.preview-nav__item {
  display: flex;
  align-items: center;
  flex-direction: column;
  gap: 6px;

  em {
    max-width: 56px;
    font-size: 11px;
    font-style: normal;
    color: #374151;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
}

.preview-nav__icon,
.preview-product__img {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: #e5e7eb;
}

.preview-image-ad {
  margin: 10px 12px;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;

  div {
    height: 80px;
    border-radius: 8px;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.preview-products {
  margin: 10px 12px;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.preview-product {
  padding: 8px;
  background: #fff;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;

  strong {
    font-size: 12px;
    line-height: 16px;
    color: #1f2937;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  em {
    font-size: 12px;
    font-style: normal;
    color: #ef4444;
  }
}

.preview-product__img {
  width: 100%;
  height: 82px;
  border-radius: 6px;
}

.preview-title {
  padding: 14px 12px 6px;
  display: flex;
  align-items: baseline;
  gap: 8px;

  strong {
    font-size: 18px;
  }

  span {
    font-size: 12px;
    color: #6b7280;
  }
}

.preview-gap {
  background: transparent;
}

.preview-divider {
  margin: 12px;
  height: 1px;
  background: #e5e7eb;
}

.preview-empty {
  margin: 12px;
  min-height: 48px;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: #6b7280;
}
</style>
