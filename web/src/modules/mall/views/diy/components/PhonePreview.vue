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

function coupons(component: DiyComponent) {
  return component.data?.coupons || []
}

function groupBuys(component: DiyComponent) {
  return component.data?.activities || []
}

function price(value: any) {
  const amount = Number(value || 0)
  return Number.isFinite(amount) ? (amount / 100).toFixed(2) : '0.00'
}

function productSourceText(component: DiyComponent) {
  const source = component.props?.source || component.data?.source || component.data?.mode || 'recommend'
  const map: Record<string, string> = {
    manual: '手动商品',
    recommend: '推荐商品',
    hot: '热卖商品',
    new: '新品商品',
    category: `分类 ${component.props?.categoryId || '未选择'}`,
    tag: '标签商品',
    activity: `活动 ${component.props?.activityId || '未选择'}`,
  }
  return map[source] || '推荐商品'
}

function imageWrapStyle(component: DiyComponent, fallbackHeight = 120) {
  const props = component.props || {}
  const widthMode = props.widthMode || 'full'
  const widthUnit = props.widthUnit || 'percent'
  const width = Number(props.width || 100)
  const style: Record<string, string> = {
    height: `${Number(props.height || fallbackHeight)}px`,
    borderRadius: `${Number(props.radius ?? 8)}px`,
  }

  if (widthMode === 'contained') {
    style.marginLeft = '12px'
    style.marginRight = '12px'
  }
  else if (widthMode === 'custom') {
    style.width = widthUnit === 'percent' ? `${Math.min(Math.max(width, 1), 100)}%` : `${Math.min(Math.max(width, 1), 390)}px`
    style.marginLeft = 'auto'
    style.marginRight = 'auto'
  }
  else {
    style.marginLeft = '0'
    style.marginRight = '0'
  }

  return style
}

function imageOuterStyle(component: DiyComponent) {
  const style = imageWrapStyle(component, 0)
  delete style.height
  delete style.borderRadius
  return style
}

function imageItemStyle(component: DiyComponent, fallbackHeight = 120) {
  const props = component.props || {}
  return {
    height: `${Number(props.height || fallbackHeight)}px`,
    borderRadius: `${Number(props.radius ?? 8)}px`,
  }
}

function imageObjectFit(component: DiyComponent) {
  return component.props?.objectFit || 'cover'
}

function pageTheme(schema: DiySchema) {
  return {
    primaryColor: '#2563eb',
    priceColor: '#ef4444',
    backgroundColor: '#f6f7f8',
    cardRadius: 8,
    buttonShape: 'round',
    ...(schema.page.theme || {}),
  }
}
</script>

<template>
  <section class="phone-preview">
    <div class="phone-preview__device">
      <div class="phone-preview__status" />
      <div class="phone-preview__title">{{ schema.page.title || schema.page.key }}</div>
      <div
        class="phone-preview__body"
        :style="{
          background: pageTheme(schema).backgroundColor,
          '--diy-primary-color': pageTheme(schema).primaryColor,
          '--diy-price-color': pageTheme(schema).priceColor,
          '--diy-card-radius': `${pageTheme(schema).cardRadius}px`,
        }"
      >
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
            <div class="preview-banner" :style="imageWrapStyle(component, 150)">
              <img v-if="imageOf(component.data?.items?.[0])" :src="imageOf(component.data?.items?.[0])" :style="{ objectFit: imageObjectFit(component) }">
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
            <div
              class="preview-image-ad"
              :class="`preview-image-ad--${component.props?.layout || 'single'}`"
              :style="imageOuterStyle(component)"
            >
              <div v-for="(item, index) in (component.data?.items || []).slice(0, component.props?.layout === 'single' ? 1 : 4)" :key="index" :style="imageItemStyle(component, 120)">
                <img v-if="imageOf(item)" :src="imageOf(item)" :style="{ objectFit: imageObjectFit(component) }">
                <span v-else>广告图</span>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'product-group'">
            <div class="preview-section">
              <div class="preview-section__title">
                {{ component.props?.title || '商品组' }}
                <em>{{ productSourceText(component) }}</em>
              </div>
            </div>
            <div class="preview-products">
              <div v-for="(item, index) in products(component).slice(0, 4)" :key="index" class="preview-product">
                <span class="preview-product__img" />
                <strong>{{ item.title || item.name || '商品' }}</strong>
                <em>¥{{ item.price || 0 }}</em>
              </div>
              <div v-if="products(component).length === 0" class="preview-empty">{{ productSourceText(component) }}</div>
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

          <template v-else-if="component.type === 'notice-bar'">
            <div
              class="preview-notice"
              :style="{ background: component.style?.background || '#fff7ed', color: component.style?.color || '#c2410c' }"
            >
              <ma-svg-icon v-if="component.props?.showIcon !== false" name="ph:megaphone" size="14" />
              <span>{{ component.data?.items?.[0]?.text || '公告内容' }}</span>
            </div>
          </template>

          <template v-else-if="component.type === 'coupon-group'">
            <div class="preview-section">
              <div class="preview-section__title">{{ component.props?.title || '领券中心' }}</div>
              <div class="preview-coupons">
                <div v-for="(item, index) in coupons(component).slice(0, component.props?.limit || 3)" :key="index" class="preview-coupon">
                  <strong>¥{{ price(item.value) }}</strong>
                  <span>{{ item.name || '优惠券' }}</span>
                </div>
                <div v-if="coupons(component).length === 0" class="preview-empty">优惠券组</div>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'seckill-group'">
            <div class="preview-section">
              <div class="preview-section__title">
                {{ component.props?.title || '限时秒杀' }}
                <em>{{ component.data?.session?.title || '未选择场次' }}</em>
              </div>
              <div class="preview-products preview-products--scroll">
                <div v-for="index in Math.min(component.props?.limit || 3, 3)" :key="index" class="preview-product">
                  <span class="preview-product__img" />
                  <strong>秒杀商品</strong>
                  <em>¥0.00</em>
                </div>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'group-buy-group'">
            <div class="preview-section">
              <div class="preview-section__title">{{ component.props?.title || '多人拼团' }}</div>
              <div class="preview-products">
                <div v-for="(item, index) in groupBuys(component).slice(0, 4)" :key="index" class="preview-product">
                  <span class="preview-product__img" />
                  <strong>{{ item.title || '拼团活动' }}</strong>
                  <em>¥{{ price(item.group_price) }}</em>
                </div>
                <div v-if="groupBuys(component).length === 0" class="preview-empty">拼团组</div>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'product-rank'">
            <div class="preview-section">
              <div class="preview-section__title">{{ component.props?.title || '商品榜单' }}</div>
              <div class="preview-rank">
                <div v-for="index in 3" :key="index" class="preview-rank__item">
                  <b>{{ index }}</b>
                  <span class="preview-rank__image" />
                  <strong>{{ component.props?.rankType === 'new' ? '新品商品' : '热销商品' }}</strong>
                </div>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'search-bar'">
            <div class="preview-search" :class="{ 'preview-search--square': component.props?.shape === 'square' }">
              <ma-svg-icon name="ph:magnifying-glass" size="14" />
              <span>{{ component.props?.placeholder || '搜索商品' }}</span>
            </div>
          </template>

          <template v-else-if="component.type === 'shop-info'">
            <div class="preview-shop">
              <img v-if="component.props?.logo" :src="component.props.logo">
              <span v-else class="preview-shop__logo" />
              <div>
                <strong>{{ component.props?.name || '官方商城' }}</strong>
                <p>{{ component.props?.description || '精选好物，安心选购' }}</p>
                <em v-for="tag in (component.data?.tags || []).slice(0, 3)" :key="tag">{{ tag }}</em>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'rich-text'">
            <div class="preview-rich-text" :style="{ padding: `${component.props?.padding || 12}px` }" v-html="component.data?.content || '<p>请输入图文内容</p>'" />
          </template>

          <template v-else-if="component.type === 'image-cube'">
            <div
              class="preview-cube"
              :class="`preview-cube--${component.props?.layout || 'two'}`"
              :style="{ ...imageOuterStyle(component), gap: `${component.props?.gap || 8}px` }"
            >
              <div v-for="(item, index) in (component.data?.items || []).slice(0, 4)" :key="index" :style="imageItemStyle(component, 120)">
                <img v-if="imageOf(item)" :src="imageOf(item)" :style="{ objectFit: imageObjectFit(component) }">
                <span v-else>{{ item.title || '图片' }}</span>
              </div>
            </div>
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
  margin-top: 12px;
  margin-bottom: 12px;
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
  border-radius: var(--diy-card-radius, 10px);
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
  margin-top: 10px;
  margin-bottom: 10px;
  display: grid;
  gap: 8px;

  div {
    min-height: 100%;
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
  border-radius: var(--diy-card-radius, 8px);
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
    color: var(--diy-price-color, #ef4444);
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

.preview-notice,
.preview-search {
  margin: 10px 12px;
  min-height: 36px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  border-radius: 18px;
  font-size: 12px;
}

.preview-section {
  margin: 10px 12px;
}

.preview-section__title {
  margin-bottom: 8px;
  display: flex;
  justify-content: space-between;
  font-size: 15px;
  font-weight: 700;

  em {
    max-width: 160px;
    overflow: hidden;
    color: #ef4444;
    font-size: 12px;
    font-style: normal;
    font-weight: 400;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.preview-image-ad--single {
  grid-template-columns: 1fr;
}

.preview-image-ad--two-column {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.preview-image-ad--horizontal {
  display: flex;
  overflow: hidden;

  div {
    min-width: 160px;
    flex: 0 0 160px;
  }
}

.preview-image-ad--vertical {
  grid-template-columns: 1fr;
}

.preview-coupons {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.preview-coupon {
  min-height: 64px;
  padding: 8px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  border-radius: 8px;
  background: #fff1f2;
  color: var(--diy-price-color, #e11d48);

  strong {
    font-size: 16px;
  }

  span {
    overflow: hidden;
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.preview-products--scroll {
  display: flex;
  overflow: hidden;

  .preview-product {
    min-width: 112px;
  }
}

.preview-rank {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.preview-rank__item {
  min-height: 54px;
  padding: 8px;
  display: grid;
  grid-template-columns: 24px 42px 1fr;
  align-items: center;
  gap: 8px;
  border-radius: var(--diy-card-radius, 8px);
  background: #fff;

  b {
    color: var(--diy-primary-color, #ef4444);
  }

  strong {
    overflow: hidden;
    font-size: 12px;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.preview-rank__image,
.preview-shop__logo {
  width: 42px;
  height: 42px;
  border-radius: 8px;
  background: #e5e7eb;
}

.preview-search {
  background: #fff;
  color: #9ca3af;
}

.preview-search--square {
  border-radius: 8px;
}

.preview-shop {
  margin: 10px 12px;
  padding: 12px;
  display: grid;
  grid-template-columns: 48px 1fr;
  gap: 10px;
  border-radius: var(--diy-card-radius, 10px);
  background: #fff;

  img,
  .preview-shop__logo {
    width: 48px;
    height: 48px;
    border-radius: 10px;
  }

  strong,
  p {
    display: block;
    margin: 0 0 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  p {
    color: #6b7280;
    font-size: 12px;
  }

  em {
    margin-right: 4px;
    padding: 1px 5px;
    border-radius: 4px;
    background: #ecfdf5;
    color: #047857;
    font-size: 10px;
    font-style: normal;
  }
}

.preview-rich-text {
  margin: 10px 12px;
  border-radius: var(--diy-card-radius, 8px);
  background: #fff;
  color: #374151;
  font-size: 12px;
  line-height: 1.6;
}

.preview-cube {
  margin-top: 10px;
  margin-bottom: 10px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));

  div {
    min-height: 100%;
    border-radius: 8px;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    font-size: 12px;
    color: #6b7280;
  }

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.preview-cube--one {
  grid-template-columns: 1fr;
}

.preview-cube--three {
  grid-template-columns: 1.2fr 1fr;
}

.preview-cube--four {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.preview-cube--left-one-right-two {
  grid-template-columns: 1.2fr 1fr;
}

.preview-cube--left-one-right-two div:first-child {
  grid-row: span 2;
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
