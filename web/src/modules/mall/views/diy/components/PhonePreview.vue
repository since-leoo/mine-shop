<script setup lang="ts">
import type { DiyComponent, DiySchema } from '../schema/types'
import type { CategoryVo } from '~/mall/api/category'
import { tree as fetchCategoryTree } from '~/mall/api/category'
import Sortable from 'sortablejs'

const props = defineProps<{
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
  sort: [ids: string[]]
}>()

const bodyRef = ref<HTMLElement>()
const previewCategoryItems = ref<any[]>([])
let sortable: Sortable | null = null

function bindSortable() {
  sortable?.destroy()
  sortable = null
  if (!bodyRef.value) {
    return
  }

  sortable = Sortable.create(bodyRef.value, {
    animation: 160,
    handle: '.preview-block__drag',
    draggable: '.preview-block',
    ghostClass: 'preview-block--ghost',
    chosenClass: 'preview-block--chosen',
    dragClass: 'preview-block--dragging',
    forceFallback: true,
    onEnd: () => {
      const ids = Array.from(bodyRef.value?.querySelectorAll<HTMLElement>('.preview-block') || [])
        .map(item => item.dataset.id)
        .filter(Boolean) as string[]
      emit('sort', ids)
    },
  })
}

onMounted(() => {
  nextTick(bindSortable)
  loadPreviewCategoryItems()
})
onBeforeUnmount(() => sortable?.destroy())
watch(() => props.schema.components.map(item => item.id).join(','), () => nextTick(bindSortable))

function normalizeCategoryChild(item: any) {
  const id = item?.groupId || item?.categoryId || item?.id || ''
  const title = item?.name || item?.title || ''
  const image = item?.thumbnail || item?.image || item?.icon || ''

  return {
    title,
    name: title,
    image,
    thumbnail: image,
    link: { type: 'category', id },
  }
}

function categoryLeaves(node: any) {
  const children = Array.isArray(node?.children) ? node.children : []
  const leaves: any[] = []

  const walk = (items: any[]) => {
    for (const item of items) {
      const childNodes = Array.isArray(item?.children) ? item.children : []
      if (childNodes.length > 0) {
        walk(childNodes)
      }
      else {
        leaves.push(normalizeCategoryChild(item))
      }
    }
  }

  walk(children)
  return leaves
}

function normalizeCategoryTree(nodes: CategoryVo[] = []) {
  return nodes.map((node: any) => {
    const name = node?.name || node?.title || ''

    return {
      title: name,
      name,
      children: categoryLeaves(node),
    }
  })
}

async function loadPreviewCategoryItems() {
  try {
    const res = await fetchCategoryTree(0)
    previewCategoryItems.value = normalizeCategoryTree(res.data || [])
  }
  catch (error) {
    console.error('Failed to load category panel preview:', error)
  }
}

function resolvePreviewAsset(value: string) {
  if (!value) {
    return ''
  }

  if (/^(https?:)?\/\//.test(value) || value.startsWith('data:') || value.startsWith('/diy-assets/')) {
    return value
  }

  const normalized = value.replace(/^\/+/, '')
  if (normalized.startsWith('assets/')) {
    return `/${normalized.replace(/^assets\//, 'diy-assets/')}`
  }

  return value
}

function svgToDataUri(value: any) {
  if (typeof value !== 'string') {
    return ''
  }

  const svg = value.trim()
  if (!/^<svg[\s>]/i.test(svg)) {
    return ''
  }

  const sanitized = svg
    .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '')
    .replace(/\son[a-z]+\s*=\s*(['"]).*?\1/gi, '')
    .replace(/\s(href|xlink:href)\s*=\s*(['"])\s*javascript:.*?\2/gi, '')
  const normalized = /\sxmlns=/.test(sanitized)
    ? sanitized
    : sanitized.replace(/^<svg\b/i, '<svg xmlns="http://www.w3.org/2000/svg"')

  return `data:image/svg+xml,${encodeURIComponent(normalized)}`
}

function imageOf(item: any) {
  const svg = svgToDataUri(item?.svg || item?.svgCode)
  if (svg) {
    return svg
  }

  return resolvePreviewAsset(item?.image || item?.icon || item?.img || item?.url || item?.thumb || item?.mainImage || item?.main_image || item?.cover || '')
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
  const hasSchemaMargin = Boolean(component.style?.margin || component.style?.marginLeft || component.style?.marginRight)
  const widthMode = hasSchemaMargin ? 'full' : props.widthMode || 'full'
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

function px(value: any) {
  if (value === undefined || value === null || value === '')
    return undefined
  return typeof value === 'number' ? `${value}px` : String(value)
}

function edgeStyle(style: Record<string, any>, key: 'margin' | 'padding') {
  const value = style[key]
  const capitalized = key === 'margin' ? 'Margin' : 'Padding'
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return {
      [`${key}Top`]: px(value.top),
      [`${key}Right`]: px(value.right),
      [`${key}Bottom`]: px(value.bottom),
      [`${key}Left`]: px(value.left),
    }
  }

  return {
    [key]: px(value),
    [`${key}Top`]: px(style[`${key}Top`] ?? style[`${capitalized}Top`]),
    [`${key}Right`]: px(style[`${key}Right`] ?? style[`${capitalized}Right`]),
    [`${key}Bottom`]: px(style[`${key}Bottom`] ?? style[`${capitalized}Bottom`]),
    [`${key}Left`]: px(style[`${key}Left`] ?? style[`${capitalized}Left`]),
  }
}

function normalizedStyle(style: Record<string, any> = {}) {
  return {
    ...edgeStyle(style, 'margin'),
    ...edgeStyle(style, 'padding'),
    width: px(style.width),
    minWidth: px(style.minWidth),
    maxWidth: px(style.maxWidth),
    height: px(style.height),
    minHeight: px(style.minHeight),
    maxHeight: px(style.maxHeight),
    borderRadius: px(style.borderRadius),
    background: style.backgroundColor || style.background,
    boxShadow: style.boxShadow || style.shadow,
    color: style.color,
    textAlign: style.textAlign,
    fontSize: px(style.fontSize),
    lineHeight: px(style.lineHeight),
    letterSpacing: px(style.letterSpacing),
    fontFamily: style.fontFamily || undefined,
    fontWeight: style.fontWeight || undefined,
  }
}

function componentStyle(component: DiyComponent) {
  return normalizedStyle(component.style || {})
}

function titleStyle(component: DiyComponent) {
  const style = component.style || {}
  return {
    color: style.color,
    textAlign: style.textAlign,
    fontSize: px(style.fontSize),
    lineHeight: px(style.lineHeight),
    fontFamily: style.fontFamily || undefined,
    fontWeight: style.fontWeight || undefined,
  }
}

function pageTheme(schema: DiySchema) {
  return {
    primaryColor: '#E8836B',
    priceColor: '#E8836B',
    backgroundColor: '#FAF3ED',
    cardRadius: 16,
    buttonShape: 'round',
    ...(schema.page.theme || {}),
  }
}

function shopLogo(component: DiyComponent) {
  return resolvePreviewAsset(component.props?.logo || '')
}

function quickNavStyle(component: DiyComponent) {
  const columns = Math.min(Math.max(Number(component.props?.columns || 5), 3), 5)
  return {
    gridTemplateColumns: `repeat(${columns}, 1fr)`,
  }
}

function quickNavIconStyle(component: DiyComponent, item: any) {
  const size = Number(component.props?.iconSize || 48)
  return {
    width: `${size}px`,
    height: `${size}px`,
    borderRadius: `${Number(component.props?.iconRadius ?? 16)}px`,
    background: item.iconBg || undefined,
  }
}

function hasSvgIcon(item: any, icon: string) {
  const inlineSvg = item?.svg || item?.svgCode
  if (typeof inlineSvg === 'string' && /^<svg[\s>]/i.test(inlineSvg.trim())) {
    return true
  }

  const source = String(item?.icon || item?.image || icon || '').split('?')[0].split('#')[0]
  return source.endsWith('.svg') || icon.startsWith('data:image/svg+xml')
}

function quickNavImageStyle(component: DiyComponent, item: any) {
  const icon = imageOf(item)
  const baseSize = Number(component.props?.imageSize || 30)
  const size = hasSvgIcon(item, icon) ? Math.round(baseSize * 0.8) : baseSize
  return {
    width: `${size}px`,
    height: `${size}px`,
  }
}

function quickNavTitleStyle(component: DiyComponent) {
  return {
    marginTop: `${Number(component.props?.itemGap ?? 7)}px`,
  }
}

function productListStyle(component: DiyComponent) {
  const listPadding = component.props?.listPadding
  return {
    gap: px(component.props?.gap),
    padding: listPadding && typeof listPadding !== 'object' ? px(listPadding) : undefined,
    ...(
      listPadding && typeof listPadding === 'object'
        ? edgeStyle({ padding: listPadding }, 'padding')
        : {}
    ),
  }
}

function marketingEntryGridStyle(component: DiyComponent) {
  return {
    gap: `${Number(component.props?.itemGap ?? 10)}px`,
  }
}

function marketingEntryCardStyle(component: DiyComponent, item: any, index: number) {
  return {
    background: item.background || (index % 2 === 0 ? '#F0A18E' : '#86BFA9'),
    color: item.color || '#FFFFFF',
    borderRadius: `${Number(component.props?.cardRadius ?? 10)}px`,
  }
}

function categoryPanelActiveIndex(component: DiyComponent) {
  const items = categoryPanelItems(component)
  return Math.min(Math.max(Number(component.props?.activeIndex || 0), 0), Math.max(items.length - 1, 0))
}

function categoryPanelItems(component: DiyComponent) {
  if (previewCategoryItems.value.length > 0) {
    return previewCategoryItems.value
  }

  return Array.isArray(component.data?.items) ? component.data.items : []
}

function categoryPanelActive(component: DiyComponent) {
  return categoryPanelItems(component)[categoryPanelActiveIndex(component)] || {}
}

function categoryPanelChildren(component: DiyComponent) {
  const active = categoryPanelActive(component)
  return Array.isArray(active.children) ? active.children : []
}

function categoryPanelGridStyle(component: DiyComponent) {
  const columns = Math.min(Math.max(Number(component.props?.columns || 3), 2), 4)
  return {
    gridTemplateColumns: `repeat(${columns}, 1fr)`,
  }
}

function pageStyle(schema: DiySchema) {
  const theme = pageTheme(schema)
  return {
    background: schema.page.style?.background || schema.page.style?.backgroundColor || theme.backgroundColor,
    '--diy-primary-color': theme.primaryColor,
    '--diy-price-color': theme.priceColor,
    '--diy-card-radius': `${theme.cardRadius}px`,
  }
}
</script>

<template>
  <section class="phone-preview">
    <div class="phone-preview__device">
      <div class="phone-preview__status" />
      <div class="phone-preview__title">
        {{ schema.page.title || schema.page.key }}
      </div>
      <div
        ref="bodyRef"
        class="phone-preview__body"
        :style="pageStyle(schema)"
      >
        <div
          v-for="component in schema.components"
          :key="component.id"
          :data-id="component.id"
          class="preview-block"
          :class="{ 'preview-block--active': component.id === selectedId, 'preview-block--disabled': component.enabled === false, 'preview-block--fill': component.type === 'category-panel' }"
          @click="emit('select', component.id)"
        >
          <div class="preview-block__tools">
            <button class="preview-block__drag" type="button" title="拖动排序" @click.stop>
              <ma-svg-icon name="ph:dots-six-vertical" size="14" />
            </button>
            <button type="button" @click.stop="emit('moveUp', component.id)">
              <ma-svg-icon name="ph:arrow-up" size="13" />
            </button>
            <button type="button" @click.stop="emit('moveDown', component.id)">
              <ma-svg-icon name="ph:arrow-down" size="13" />
            </button>
            <button type="button" @click.stop="emit('copy', component.id)">
              <ma-svg-icon name="ph:copy" size="13" />
            </button>
            <button type="button" @click.stop="emit('toggle', component.id)">
              <ma-svg-icon :name="component.enabled === false ? 'ph:eye-slash' : 'ph:eye'" size="13" />
            </button>
            <button type="button" @click.stop="emit('remove', component.id)">
              <ma-svg-icon name="ph:trash" size="13" />
            </button>
          </div>

          <template v-if="component.type === 'banner'">
            <div class="preview-banner" :style="{ ...imageWrapStyle(component, 150), ...componentStyle(component) }">
              <img v-if="imageOf(component.data?.items?.[0])" :src="imageOf(component.data?.items?.[0])" :style="{ objectFit: imageObjectFit(component) }">
              <span v-else>轮播图</span>
            </div>
          </template>

          <template v-else-if="component.type === 'quick-nav'">
            <div class="preview-nav" :style="{ ...quickNavStyle(component), ...componentStyle(component) }">
              <div
                v-for="(item, index) in (component.data?.items || []).slice(0, Number(component.props?.columns || 5) * Number(component.props?.rows || 1))"
                :key="index"
                class="preview-nav__item"
              >
                <span class="preview-nav__icon" :style="quickNavIconStyle(component, item)">
                  <img v-if="imageOf(item)" :src="imageOf(item)" :style="quickNavImageStyle(component, item)">
                  <b v-else>{{ item.iconText || (item.title || item.name || '入').slice(0, 1) }}</b>
                </span>
                <em :style="quickNavTitleStyle(component)">{{ item.title || item.name || '入口' }}</em>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'image-ad'">
            <div
              class="preview-image-ad"
              :class="`preview-image-ad--${component.props?.layout || 'single'}`"
              :style="{ ...imageOuterStyle(component), ...componentStyle(component) }"
            >
              <div v-for="(item, index) in (component.data?.items || []).slice(0, component.props?.layout === 'single' ? 1 : 4)" :key="index" :style="imageItemStyle(component, 120)">
                <img v-if="imageOf(item)" :src="imageOf(item)" :style="{ objectFit: imageObjectFit(component) }">
                <span v-else>广告图</span>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'product-group'">
            <div class="preview-product-group" :style="componentStyle(component)">
              <div v-if="component.props?.title" class="preview-section">
                <div class="preview-section__title">
                  {{ component.props?.title }}
                  <em>{{ productSourceText(component) }}</em>
                </div>
              </div>
              <div class="preview-products" :class="`preview-products--${component.props?.layout || 'two-column'}`" :style="productListStyle(component)">
                <div v-for="(item, index) in products(component).slice(0, 4)" :key="index" class="preview-product">
                  <img v-if="imageOf(item)" class="preview-product__img" :src="imageOf(item)">
                  <span v-else class="preview-product__img" />
                  <strong>{{ item.title || item.name || '商品' }}</strong>
                  <em>¥{{ item.price || 0 }}</em>
                </div>
                <div v-if="products(component).length === 0" class="preview-empty">
                  {{ productSourceText(component) }}
                </div>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'title-bar'">
            <div class="preview-title" :style="componentStyle(component)">
              <strong :style="titleStyle(component)">{{ component.props?.title || component.data?.title || component.name }}</strong>
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
              :style="componentStyle(component)"
            >
              <ma-svg-icon v-if="component.props?.showIcon !== false" name="ph:megaphone" size="14" />
              <span>{{ component.data?.items?.[0]?.text || '公告内容' }}</span>
            </div>
          </template>

          <template v-else-if="component.type === 'coupon-group'">
            <div class="preview-section">
              <div class="preview-section__title">
                {{ component.props?.title || '领券中心' }}
              </div>
              <div class="preview-coupons">
                <div v-for="(item, index) in coupons(component).slice(0, component.props?.limit || 3)" :key="index" class="preview-coupon">
                  <strong>¥{{ price(item.value) }}</strong>
                  <span>{{ item.name || '优惠券' }}</span>
                </div>
                <div v-if="coupons(component).length === 0" class="preview-empty">
                  优惠券组
                </div>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'category-panel'">
            <div class="preview-category-panel" :style="componentStyle(component)">
              <div class="preview-category-panel__side">
                <div
                  v-for="(item, index) in categoryPanelItems(component).slice(0, 8)"
                  :key="index"
                  class="preview-category-panel__tab"
                  :class="{ 'preview-category-panel__tab--active': index === categoryPanelActiveIndex(component) }"
                >
                  {{ item.title || item.name || '分类' }}
                </div>
              </div>
              <div class="preview-category-panel__content">
                <div class="preview-category-panel__head">
                  <span />
                  <strong>{{ categoryPanelActive(component).title || categoryPanelActive(component).name || component.props?.title || '商品分类' }}</strong>
                </div>
                <div class="preview-category-panel__grid" :style="categoryPanelGridStyle(component)">
                  <div v-for="(item, index) in categoryPanelChildren(component).slice(0, 12)" :key="index" class="preview-category-panel__card">
                    <span class="preview-category-panel__image">
                      <img v-if="imageOf(item)" :src="imageOf(item)">
                    </span>
                    <em>{{ item.title || item.name || '分类' }}</em>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'marketing-entry'">
            <div class="preview-marketing-entry" :style="componentStyle(component)">
              <div class="preview-marketing-entry__head">
                <span />
                <strong>{{ component.props?.title || '今日活动直达' }}</strong>
              </div>
              <div class="preview-marketing-entry__grid" :style="marketingEntryGridStyle(component)">
                <div
                  v-for="(item, index) in (component.data?.items || []).slice(0, 4)"
                  :key="index"
                  class="preview-marketing-entry__card"
                  :style="marketingEntryCardStyle(component, item, index)"
                >
                  <strong>{{ item.title || '活动入口' }}</strong>
                  <em>{{ item.subtitle || '点击进入专题页' }}</em>
                  <b>{{ item.badge || '立即查看' }}</b>
                </div>
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
              <div class="preview-section__title">
                {{ component.props?.title || '多人拼团' }}
              </div>
              <div class="preview-products">
                <div v-for="(item, index) in groupBuys(component).slice(0, 4)" :key="index" class="preview-product">
                  <span class="preview-product__img" />
                  <strong>{{ item.title || '拼团活动' }}</strong>
                  <em>¥{{ price(item.group_price) }}</em>
                </div>
                <div v-if="groupBuys(component).length === 0" class="preview-empty">
                  拼团组
                </div>
              </div>
            </div>
          </template>

          <template v-else-if="component.type === 'product-rank'">
            <div class="preview-section">
              <div class="preview-section__title">
                {{ component.props?.title || '商品榜单' }}
              </div>
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
            <div class="preview-search" :class="{ 'preview-search--square': component.props?.shape === 'square' }" :style="componentStyle(component)">
              <ma-svg-icon name="ph:magnifying-glass" size="14" />
              <span>{{ component.props?.placeholder || '搜索商品' }}</span>
            </div>
          </template>

          <template v-else-if="component.type === 'shop-info'">
            <div class="preview-shop">
              <img v-if="shopLogo(component)" :src="shopLogo(component)">
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
            <div class="preview-empty">
              {{ component.name }}
            </div>
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
  padding: 28px 0;
  display: flex;
  justify-content: center;
  overflow: auto;
  background: #e8ddd5;
}

.phone-preview__device {
  width: 375px;
  height: 780px;
  border-radius: 44px;
  background: #fff8f3;
  overflow: hidden;
  box-shadow: 0 24px 60px rgba(100, 60, 30, 0.20), 0 0 0 8px #3a3a3a, 0 0 0 10px #1a1a1a;
}

.phone-preview__status {
  height: 44px;
  background: #fff8f3;
}

.phone-preview__title {
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff8f3;
  color: #4a3228;
  font-size: 17px;
  font-weight: 700;
}

.phone-preview__body {
  height: 692px;
  padding: 0 0 64px;
  overflow: auto;
}

.preview-block {
  position: relative;
  min-height: 28px;
  border: 1px solid transparent;
  outline: 0;
  cursor: pointer;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease, background 0.18s ease;

  &:hover,
  &--active {
    border-color: #409eff;
    box-shadow: inset 0 0 0 1px rgba(64, 158, 255, 0.18);
  }
}

.preview-block--fill {
  height: 100%;
  min-height: 0;
}

.preview-block--disabled {
  opacity: 0.45;
}

.preview-block--chosen {
  border-color: #2563eb;
  background: rgba(37, 99, 235, 0.04);
}

.preview-block--ghost {
  opacity: 0.35;
  background: rgba(64, 158, 255, 0.10);
}

.preview-block--dragging {
  cursor: grabbing;
}

.preview-block__tools {
  position: absolute;
  z-index: 2;
  top: 6px;
  right: 6px;
  display: none;
  gap: 4px;
  padding: 3px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14);

  button {
    width: 24px;
    height: 24px;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #334155;
    cursor: pointer;
    transition: background 0.16s ease, color 0.16s ease;

    &:hover {
      background: #eef5ff;
      color: #2563eb;
    }
  }
}

.preview-block__drag {
  cursor: grab;

  &:active {
    cursor: grabbing;
  }
}

.preview-block:hover .preview-block__tools,
.preview-block--active .preview-block__tools {
  display: flex;
}

.preview-banner {
  margin-top: 0;
  margin-bottom: 0;
  border-radius: 10px;
  background: #e8edf4;
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
  margin: 0;
  padding: 14px 8px 10px;
  display: grid;
  background: #fff;
  border-radius: var(--diy-card-radius, 16px);
  box-shadow: 0 2px 8px rgba(200, 140, 110, 0.08);
  row-gap: 12px;
}

.preview-nav__item {
  display: flex;
  align-items: center;
  flex-direction: column;
  min-width: 0;

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
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: #fff1e8;
}

.preview-nav__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;

  img {
    width: 28px;
    height: 28px;
    object-fit: contain;
  }

  b {
    color: var(--diy-primary-color, #E8836B);
    font-size: 18px;
    font-weight: 800;
    line-height: 1;
  }
}

.preview-image-ad {
  margin-top: 0;
  margin-bottom: 0;
  display: grid;
  gap: 8px;

  div {
    min-height: 100%;
    border-radius: 8px;
    background: #e8edf4;
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
  margin: 0;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.preview-products--single {
  grid-template-columns: 1fr;

  .preview-product {
    display: grid;
    grid-template-columns: 86px 1fr;
    grid-template-rows: auto auto;
    align-items: start;
  }

  .preview-product__img {
    grid-row: span 2;
    height: 86px;
  }
}

.preview-products--scroll {
  display: flex;
  overflow: hidden;

  .preview-product {
    min-width: 112px;
  }
}

.preview-product {
  padding: 8px;
  background: #fff;
  border-radius: var(--diy-card-radius, 8px);
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04);
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
  object-fit: cover;
}

.preview-title {
  padding: 0;
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
  background: #e1e7ef;
}

.preview-notice,
.preview-search {
  margin: 0;
  min-height: 36px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  border-radius: 18px;
  font-size: 12px;
}

.preview-section {
  margin: 0;
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

.preview-category-panel {
  height: 100%;
  min-height: 0;
  display: flex;
  overflow: hidden;
  background: #fff5ee;
}

.preview-category-panel__side {
  width: 88px;
  flex-shrink: 0;
  overflow-y: auto;
  background: #fff5ee;
}

.preview-category-panel__tab {
  min-height: 48px;
  padding: 0 5px;
  border-left: 3px solid transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8b6f63;
  font-size: 12px;
  font-weight: 600;
  line-height: 17px;
  text-align: center;
  word-break: break-all;
}

.preview-category-panel__tab--active {
  border-left-color: var(--diy-primary-color, #E8836B);
  background: #fff;
  color: var(--diy-primary-color, #E8836B);
  font-weight: 700;
}

.preview-category-panel__content {
  flex: 1;
  min-width: 0;
  overflow-y: auto;
  padding: 10px 12px 14px;
  background: #fff;
  box-sizing: border-box;
}

.preview-category-panel__head {
  padding: 6px 0 10px;
  display: flex;
  align-items: center;
  gap: 6px;

  span {
    width: 3px;
    height: 14px;
    border-radius: 2px;
    background: var(--diy-primary-color, #E8836B);
  }

  strong {
    overflow: hidden;
    color: #3a2a22;
    font-size: 14px;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.preview-category-panel__grid {
  display: grid;
  gap: 10px;
}

.preview-category-panel__card {
  min-width: 0;
  display: flex;
  align-items: center;
  flex-direction: column;

  em {
    width: 100%;
    margin-top: 6px;
    overflow: hidden;
    color: #6b5248;
    font-size: 11px;
    font-style: normal;
    line-height: 15px;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.preview-category-panel__image {
  width: 100%;
  aspect-ratio: 1;
  border-radius: 5px;
  overflow: hidden;
  background: #fff1e8;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.preview-marketing-entry {
  margin: 0 12px 8px;
  padding: 12px 14px 14px;
  border-radius: var(--diy-card-radius, 16px);
  background: #fff;
  box-shadow: 0 2px 8px rgba(200, 140, 110, 0.08);
}

.preview-marketing-entry__head {
  margin-bottom: 9px;
  display: flex;
  align-items: center;

  span {
    width: 4px;
    height: 14px;
    margin-right: 6px;
    border-radius: 4px;
    background: var(--diy-primary-color, #E8836B);
  }

  strong {
    color: #3a2a22;
    font-size: 15px;
    font-weight: 700;
  }
}

.preview-marketing-entry__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.preview-marketing-entry__card {
  min-width: 0;
  min-height: 67px;
  padding: 9px 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;

  strong,
  em,
  b {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  strong {
    font-size: 15px;
    font-weight: 700;
    line-height: 19px;
  }

  em {
    margin-top: 3px;
    font-size: 11px;
    font-style: normal;
    line-height: 15px;
    opacity: 0.9;
  }

  b {
    margin-top: 7px;
    font-size: 11px;
    line-height: 15px;
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
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04);

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
  border: 1px solid #edf0f5;
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
