<script setup lang="ts">
import type { DiyComponent, DiyLink } from '../schema/types'
import type { DiyCouponSelectorVo, DiyGroupBuySelectorVo, DiyProductSelectorVo, DiySeckillSelectorVo } from '~/mall/api/diySelector'
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'
import CouponSelector from './selectors/CouponSelector.vue'
import GroupBuySelector from './selectors/GroupBuySelector.vue'
import ProductSelector from './selectors/ProductSelector.vue'
import SeckillSelector from './selectors/SeckillSelector.vue'
import MaUploadImage from '@/components/ma-upload-image/index.vue'

type EditableItem = Record<string, any> & {
  link?: DiyLink
}

const props = defineProps<{
  component?: DiyComponent | null
}>()

const emit = defineEmits<{
  update: [component: DiyComponent]
}>()

const advancedVisible = ref<string[]>([])
const activeTab = ref('content')
const propsJson = ref('{}')
const dataJson = ref('{}')
const styleJson = ref('{}')
const productSelectorVisible = ref(false)
const couponSelectorVisible = ref(false)
const seckillSelectorVisible = ref(false)
const groupBuySelectorVisible = ref(false)

const title = computed(() => props.component ? `${props.component.name} / ${props.component.type}` : '未选择组件')

const imageWidthModeOptions = [
  { label: '通栏', value: 'full' },
  { label: '留边', value: 'contained' },
  { label: '自定义', value: 'custom' },
]

const imageObjectFitOptions = [
  { label: '裁剪填充', value: 'cover' },
  { label: '完整显示', value: 'contain' },
  { label: '拉伸铺满', value: 'fill' },
]

const textAlignOptions = [
  { label: '左对齐', value: 'left' },
  { label: '居中', value: 'center' },
  { label: '右对齐', value: 'right' },
]

const fontFamilyOptions = [
  { label: '系统默认', value: '' },
  { label: '苹方/雅黑', value: 'PingFang SC, Microsoft YaHei, sans-serif' },
  { label: '数字强调', value: 'DIN Alternate, Arial, sans-serif' },
  { label: '宋体标题', value: 'SimSun, serif' },
]

const fontWeightOptions = [
  { label: '常规', value: 400 },
  { label: '中等', value: 500 },
  { label: '加粗', value: 700 },
]

const productLayoutOptions = [
  { label: '双列', value: 'two-column' },
  { label: '单列', value: 'single' },
  { label: '横滑', value: 'scroll' },
]

const linkTypeOptions = [
  { label: '页面路径', value: 'page' },
  { label: '商品详情', value: 'product' },
  { label: '分类结果', value: 'category' },
  { label: '优惠券', value: 'coupon' },
  { label: '拼团', value: 'group_buy' },
  { label: '秒杀', value: 'seckill' },
]

const productIdsText = computed({
  get: () => {
    const ids = props.component?.data?.product_ids
    return Array.isArray(ids) ? ids.join(',') : ''
  },
  set: (value: string) => {
    const productIds = value
      .split(',')
      .map(item => item.trim())
      .filter(Boolean)
      .map(item => Number(item))
      .filter(item => Number.isFinite(item) && item > 0)
    patchData({ product_ids: productIds })
  },
})

const productSource = computed(() => {
  return props.component?.props?.source || props.component?.data?.source || props.component?.data?.mode || 'recommend'
})

function edgeValue(source: Record<string, any> | undefined, key: 'margin' | 'padding', side: 'top' | 'right' | 'bottom' | 'left') {
  const edge = source?.[key]
  if (edge && typeof edge === 'object' && !Array.isArray(edge)) {
    return Number(edge[side] ?? 0)
  }

  const propName = `${key}${side.slice(0, 1).toUpperCase()}${side.slice(1)}`
  return Number(source?.[propName] ?? (typeof edge === 'number' ? edge : 0))
}

function patchStyleEdge(key: 'margin' | 'padding', side: 'top' | 'right' | 'bottom' | 'left', value: number | undefined) {
  const current = props.component?.style?.[key]
  const next = current && typeof current === 'object' && !Array.isArray(current)
    ? { ...current }
    : {
        top: edgeValue(props.component?.style, key, 'top'),
        right: edgeValue(props.component?.style, key, 'right'),
        bottom: edgeValue(props.component?.style, key, 'bottom'),
        left: edgeValue(props.component?.style, key, 'left'),
      }

  next[side] = value ?? 0
  patchStyle({ [key]: next })
}

function pickValue(source: Record<string, any>, keys: string[]) {
  for (const key of keys) {
    if (source[key] !== undefined && source[key] !== null && source[key] !== '') {
      return source[key]
    }
  }

  return undefined
}

function toFiniteNumber(value: any) {
  const next = Number(value)
  return Number.isFinite(next) ? next : undefined
}

function radiusCornerValue(radius: Record<string, any>, side: 'top' | 'bottom', corner: 'Left' | 'Right') {
  const cornerName = corner.toLowerCase()
  return toFiniteNumber(pickValue(radius, [
    `${side}${corner}`,
    `${side}_${cornerName}`,
    `${side}-${cornerName}`,
  ]))
}

function radiusSideValue(side: 'top' | 'bottom') {
  const radius = props.component?.style?.borderRadius
  if (radius && typeof radius === 'object' && !Array.isArray(radius)) {
    return radiusCornerValue(radius, side, 'Left') ?? radiusCornerValue(radius, side, 'Right') ?? 0
  }

  return toFiniteNumber(radius) ?? 0
}

function patchRadiusSide(side: 'top' | 'bottom', value: number | undefined) {
  const current = props.component?.style?.borderRadius
  const currentRadius = toFiniteNumber(current) ?? 0
  const next = current && typeof current === 'object' && !Array.isArray(current)
    ? { ...current }
    : {
        topLeft: currentRadius,
        topRight: currentRadius,
        bottomLeft: currentRadius,
        bottomRight: currentRadius,
      }

  const radius = value ?? 0
  next[`${side}Left`] = radius
  next[`${side}Right`] = radius
  patchStyle({ borderRadius: next })
}

function listPaddingValue(side: 'top' | 'right' | 'bottom' | 'left') {
  return edgeValue({ padding: props.component?.props?.listPadding }, 'padding', side)
}

function patchListPadding(side: 'top' | 'right' | 'bottom' | 'left', value: number | undefined) {
  const current = props.component?.props?.listPadding
  const next = current && typeof current === 'object' && !Array.isArray(current)
    ? { ...current }
    : {
        top: listPaddingValue('top'),
        right: listPaddingValue('right'),
        bottom: listPaddingValue('bottom'),
        left: listPaddingValue('left'),
      }

  next[side] = value ?? 0
  patchProps({ listPadding: next })
}

watch(() => props.component, (component) => {
  propsJson.value = JSON.stringify(component?.props || {}, null, 2)
  dataJson.value = JSON.stringify(component?.data || {}, null, 2)
  styleJson.value = JSON.stringify(component?.style || {}, null, 2)
}, { immediate: true, deep: true })

function patch(payload: Partial<DiyComponent>) {
  if (!props.component) {
    return
  }
  emit('update', { ...props.component, ...payload })
}

function patchProps(payload: Record<string, any>) {
  patch({ props: { ...(props.component?.props || {}), ...payload } })
}

function patchStyle(payload: Record<string, any>) {
  patch({ style: { ...(props.component?.style || {}), ...payload } })
}

function patchData(payload: Record<string, any>) {
  patch({ data: { ...(props.component?.data || {}), ...payload } })
}

function items(): EditableItem[] {
  const source = props.component?.data?.items
  return Array.isArray(source) ? source : []
}

function patchItems(nextItems: EditableItem[]) {
  patchData({ items: nextItems })
}

function patchItem(index: number, payload: Record<string, any>) {
  const nextItems = [...items()]
  nextItems[index] = { ...(nextItems[index] || {}), ...payload }
  patchItems(nextItems)
}

function patchItemLink(index: number, payload: Record<string, any>) {
  const item = items()[index] || {}
  patchItem(index, { link: { ...(item.link || {}), ...payload } })
}

function addItem(seed: EditableItem) {
  patchItems([...items(), seed])
}

function removeItem(index: number) {
  patchItems(items().filter((_, itemIndex) => itemIndex !== index))
}

function patchItemImage(index: number, value: string | string[]) {
  patchItem(index, { image: Array.isArray(value) ? value[0] || '' : value })
}

function patchItemIcon(index: number, value: string | string[]) {
  const icon = Array.isArray(value) ? value[0] || '' : value
  patchItem(index, { icon, image: icon })
}

function patchItemSvg(index: number, value: string) {
  patchItem(index, { svg: value, svgCode: value ? undefined : '' })
}

function patchLink(payload: Record<string, any>) {
  patchProps({ link: { ...(props.component?.props?.link || {}), ...payload } })
}

function applyJson() {
  if (!props.component) {
    return
  }

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

function defaultImageItem(): EditableItem {
  return {
    image: '',
    title: '',
    link: { type: 'page', path: '' },
  }
}

function defaultNavItem(): EditableItem {
  return {
    icon: '',
    title: '入口',
    link: { type: 'page', path: '' },
  }
}

function defaultMarketingEntryItem(): EditableItem {
  return {
    title: '活动入口',
    subtitle: '点击进入专题页',
    badge: '立即查看',
    background: '#F0A18E',
    color: '#FFFFFF',
    link: { type: 'page', path: '' },
  }
}

function defaultUserMenuItem(): EditableItem {
  return {
    label: '菜单入口',
    value: '',
    icon: '',
    link: { type: 'page', path: '' },
  }
}

function imageSizeDefaults(componentType?: string) {
  return {
    widthMode: props.component?.props?.widthMode || 'full',
    widthUnit: props.component?.props?.widthUnit || 'percent',
    width: props.component?.props?.width || 100,
    height: props.component?.props?.height || (componentType === 'banner' ? 150 : 120),
    radius: props.component?.props?.radius || 8,
    objectFit: props.component?.props?.objectFit || 'cover',
  }
}

function selectedProducts() {
  const source = props.component?.data?.products
  return Array.isArray(source) ? source : []
}

function selectedCoupons() {
  const source = props.component?.data?.coupons
  return Array.isArray(source) ? source : []
}

function selectedGroupBuys() {
  const source = props.component?.data?.activities
  return Array.isArray(source) ? source : []
}

function onProductsSelected(items: DiyProductSelectorVo[]) {
  patchProps({ source: 'manual' })
  patchData({
    mode: 'manual',
    source: 'manual',
    product_ids: items.map(item => item.id),
    products: items,
  })
}

function onCouponsSelected(items: DiyCouponSelectorVo[]) {
  patchData({
    couponIds: items.map(item => item.id),
    coupons: items,
  })
}

function onSeckillSelected(item: DiySeckillSelectorVo) {
  patchData({
    sessionId: item.id,
    activityId: item.activity_id,
    session: item,
  })
}

function onGroupBuysSelected(items: DiyGroupBuySelectorVo[]) {
  patchData({
    groupBuyIds: items.map(item => item.id),
    activities: items,
  })
}
</script>

<template>
  <aside class="property-panel">
    <div class="property-panel__head">
      {{ title }}
    </div>
    <el-empty v-if="!component" description="请选择组件" />
    <template v-else>
      <el-form label-position="top" class="property-panel__form">
        <el-form-item label="组件名称">
          <el-input :model-value="component.name" @update:model-value="patch({ name: String($event) })" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch :model-value="component.enabled !== false" @update:model-value="patch({ enabled: Boolean($event) })" />
        </el-form-item>

        <el-tabs v-model="activeTab" class="property-panel__tabs">
          <el-tab-pane label="内容" name="content">
            <template v-if="component.type === 'banner'">
              <el-divider>轮播设置</el-divider>
              <el-form-item label="宽度模式">
                <el-segmented
                  :model-value="imageSizeDefaults('banner').widthMode"
                  :options="imageWidthModeOptions"
                  @update:model-value="patchProps({ widthMode: $event })"
                />
              </el-form-item>
              <div v-if="imageSizeDefaults('banner').widthMode === 'custom'" class="property-panel__grid">
                <el-form-item label="宽度单位">
                  <el-select :model-value="imageSizeDefaults('banner').widthUnit" @update:model-value="patchProps({ widthUnit: $event })">
                    <el-option label="百分比" value="percent" />
                    <el-option label="PX" value="px" />
                    <el-option label="RPX" value="rpx" />
                  </el-select>
                </el-form-item>
                <el-form-item label="宽度">
                  <el-input-number :model-value="imageSizeDefaults('banner').width" :min="1" :max="imageSizeDefaults('banner').widthUnit === 'percent' ? 100 : 750" @update:model-value="patchProps({ width: $event || 100 })" />
                </el-form-item>
              </div>
              <div class="property-panel__grid">
                <el-form-item label="高度">
                  <el-input-number :model-value="imageSizeDefaults('banner').height" :min="80" :max="360" @update:model-value="patchProps({ height: $event || 150 })" />
                </el-form-item>
                <el-form-item label="圆角">
                  <el-input-number :model-value="imageSizeDefaults('banner').radius" :min="0" :max="32" @update:model-value="patchProps({ radius: $event ?? 0 })" />
                </el-form-item>
              </div>
              <el-form-item label="填充方式">
                <el-select :model-value="imageSizeDefaults('banner').objectFit" @update:model-value="patchProps({ objectFit: $event })">
                  <el-option v-for="option in imageObjectFitOptions" :key="option.value" :label="option.label" :value="option.value" />
                </el-select>
              </el-form-item>
              <el-form-item label="自动播放">
                <el-switch :model-value="component.props?.autoplay !== false" @update:model-value="patchProps({ autoplay: Boolean($event) })" />
              </el-form-item>
              <div class="property-panel__items">
                <div v-for="(item, index) in items()" :key="index" class="property-panel__item">
                  <div class="property-panel__item-head">
                    <strong>轮播图 {{ index + 1 }}</strong>
                    <el-button text type="danger" @click="removeItem(index)">
                      删除
                    </el-button>
                  </div>
                  <el-form-item label="图片">
                    <MaUploadImage
                      :model-value="item.image || item.url || ''"
                      :size="72"
                      @update:model-value="patchItemImage(index, $event)"
                    />
                  </el-form-item>
                  <el-form-item label="标题">
                    <el-input :model-value="item.title || ''" @update:model-value="patchItem(index, { title: String($event) })" />
                  </el-form-item>
                  <el-form-item label="跳转类型">
                    <el-select :model-value="item.link?.type || 'page'" @update:model-value="patchItemLink(index, { type: $event })">
                      <el-option v-for="option in linkTypeOptions" :key="option.value" :label="option.label" :value="option.value" />
                    </el-select>
                  </el-form-item>
                  <el-form-item v-if="(item.link?.type || 'page') === 'page'" label="页面路径">
                    <el-input :model-value="item.link?.path || item.link?.url || ''" @update:model-value="patchItemLink(index, { path: String($event) })" />
                  </el-form-item>
                  <el-form-item v-else label="业务 ID">
                    <el-input :model-value="item.link?.id || ''" @update:model-value="patchItemLink(index, { id: $event })" />
                  </el-form-item>
                </div>
                <el-button class="property-panel__add" @click="addItem(defaultImageItem())">
                  添加轮播图
                </el-button>
              </div>
            </template>

            <template v-else-if="component.type === 'quick-nav'">
              <el-divider>金刚区设置</el-divider>
              <div class="property-panel__grid">
                <el-form-item label="列数">
                  <el-input-number :model-value="component.props?.columns || 5" :min="3" :max="5" @update:model-value="patchProps({ columns: $event || 5 })" />
                </el-form-item>
                <el-form-item label="行数">
                  <el-input-number :model-value="component.props?.rows || 1" :min="1" :max="4" @update:model-value="patchProps({ rows: $event || 1 })" />
                </el-form-item>
              </div>
              <div class="property-panel__items">
                <div v-for="(item, index) in items()" :key="index" class="property-panel__item">
                  <div class="property-panel__item-head">
                    <strong>入口 {{ index + 1 }}</strong>
                    <el-button text type="danger" @click="removeItem(index)">
                      删除
                    </el-button>
                  </div>
                  <el-form-item label="入口名称">
                    <el-input :model-value="item.title || item.name || ''" @update:model-value="patchItem(index, { title: String($event) })" />
                  </el-form-item>
                  <el-form-item label="图标">
                    <MaUploadImage
                      :model-value="item.icon || item.image || ''"
                      :size="56"
                      @update:model-value="patchItemIcon(index, $event)"
                    />
                  </el-form-item>
                  <el-form-item label="SVG 代码">
                    <el-input
                      :model-value="item.svg || item.svgCode || ''"
                      type="textarea"
                      :rows="4"
                      spellcheck="false"
                      placeholder="<svg ...></svg>"
                      @update:model-value="patchItemSvg(index, String($event))"
                    />
                    <span class="property-panel__hint">填写后优先使用 SVG；留空则使用上传图片</span>
                  </el-form-item>
                  <el-form-item label="页面路径">
                    <el-input :model-value="item.link?.path || item.link?.url || ''" @update:model-value="patchItemLink(index, { type: 'page', path: String($event) })" />
                  </el-form-item>
                </div>
                <el-button class="property-panel__add" @click="addItem(defaultNavItem())">
                  添加入口
                </el-button>
              </div>
            </template>

            <template v-else-if="component.type === 'image-ad'">
              <el-divider>图片广告</el-divider>
              <el-form-item label="布局">
                <el-select
                  :model-value="component.props?.layout || 'single'"
                  @update:model-value="patchProps({ layout: $event })"
                >
                  <el-option label="单图" value="single" />
                  <el-option label="双列" value="two-column" />
                  <el-option label="横向滑动" value="horizontal" />
                  <el-option label="竖向列表" value="vertical" />
                </el-select>
              </el-form-item>
              <el-form-item label="宽度模式">
                <el-segmented
                  :model-value="imageSizeDefaults('image-ad').widthMode"
                  :options="imageWidthModeOptions"
                  @update:model-value="patchProps({ widthMode: $event })"
                />
              </el-form-item>
              <div v-if="imageSizeDefaults('image-ad').widthMode === 'custom'" class="property-panel__grid">
                <el-form-item label="宽度单位">
                  <el-select :model-value="imageSizeDefaults('image-ad').widthUnit" @update:model-value="patchProps({ widthUnit: $event })">
                    <el-option label="百分比" value="percent" />
                    <el-option label="PX" value="px" />
                    <el-option label="RPX" value="rpx" />
                  </el-select>
                </el-form-item>
                <el-form-item label="宽度">
                  <el-input-number :model-value="imageSizeDefaults('image-ad').width" :min="1" :max="imageSizeDefaults('image-ad').widthUnit === 'percent' ? 100 : 750" @update:model-value="patchProps({ width: $event || 100 })" />
                </el-form-item>
              </div>
              <div class="property-panel__grid">
                <el-form-item label="高度">
                  <el-input-number :model-value="imageSizeDefaults('image-ad').height" :min="40" :max="600" @update:model-value="patchProps({ height: $event || 120 })" />
                </el-form-item>
                <el-form-item label="圆角">
                  <el-input-number :model-value="imageSizeDefaults('image-ad').radius" :min="0" :max="64" @update:model-value="patchProps({ radius: $event ?? 0 })" />
                </el-form-item>
              </div>
              <el-form-item label="填充方式">
                <el-select :model-value="imageSizeDefaults('image-ad').objectFit" @update:model-value="patchProps({ objectFit: $event })">
                  <el-option v-for="option in imageObjectFitOptions" :key="option.value" :label="option.label" :value="option.value" />
                </el-select>
              </el-form-item>
              <div class="property-panel__items">
                <div v-for="(item, index) in items()" :key="index" class="property-panel__item">
                  <div class="property-panel__item-head">
                    <strong>广告图 {{ index + 1 }}</strong>
                    <el-button text type="danger" @click="removeItem(index)">
                      删除
                    </el-button>
                  </div>
                  <el-form-item label="图片">
                    <MaUploadImage
                      :model-value="item.image || item.url || ''"
                      :size="72"
                      @update:model-value="patchItemImage(index, $event)"
                    />
                  </el-form-item>
                  <el-form-item label="页面路径">
                    <el-input :model-value="item.link?.path || item.link?.url || ''" @update:model-value="patchItemLink(index, { type: 'page', path: String($event) })" />
                  </el-form-item>
                </div>
                <el-button class="property-panel__add" @click="addItem(defaultImageItem())">
                  添加广告图
                </el-button>
              </div>
            </template>

            <template v-else-if="component.type === 'product-group'">
              <el-divider>商品组设置</el-divider>
              <el-form-item label="标题">
                <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
              </el-form-item>
              <el-form-item label="商品来源">
                <el-select :model-value="productSource" @update:model-value="patchProps({ source: $event })">
                  <el-option label="手动选择" value="manual" />
                  <el-option label="推荐商品" value="recommend" />
                  <el-option label="热卖商品" value="hot" />
                  <el-option label="新品商品" value="new" />
                  <el-option label="按分类" value="category" />
                  <el-option label="按标签" value="tag" />
                  <el-option label="按活动" value="activity" />
                </el-select>
              </el-form-item>
              <div v-if="productSource === 'category'" class="property-panel__grid">
                <el-form-item label="分类 ID">
                  <el-input :model-value="component.props?.categoryId || ''" @update:model-value="patchProps({ categoryId: $event })" />
                </el-form-item>
                <el-form-item label="排序">
                  <el-select :model-value="component.props?.sort || 'default'" @update:model-value="patchProps({ sort: $event })">
                    <el-option label="综合" value="default" />
                    <el-option label="销量优先" value="sales" />
                    <el-option label="价格升序" value="price_asc" />
                    <el-option label="价格降序" value="price_desc" />
                    <el-option label="新品优先" value="new" />
                  </el-select>
                </el-form-item>
              </div>
              <el-form-item v-else-if="productSource === 'tag'" label="标签 ID">
                <el-input
                  :model-value="Array.isArray(component.props?.tagIds) ? component.props.tagIds.join(',') : ''"
                  placeholder="多个标签 ID 用英文逗号分隔"
                  @update:model-value="patchProps({ tagIds: String($event).split(',').map(item => item.trim()).filter(Boolean).map(Number).filter(item => Number.isFinite(item) && item > 0) })"
                />
              </el-form-item>
              <el-form-item v-else-if="productSource === 'activity'" label="活动 ID">
                <el-input :model-value="component.props?.activityId || ''" @update:model-value="patchProps({ activityId: $event })" />
              </el-form-item>
              <el-form-item label="展示数量">
                <el-input-number :model-value="component.props?.limit || 10" :min="1" :max="50" @update:model-value="patchProps({ limit: $event || 10 })" />
              </el-form-item>
              <el-form-item v-if="!['category'].includes(productSource)" label="排序">
                <el-select :model-value="component.props?.sort || 'default'" @update:model-value="patchProps({ sort: $event })">
                  <el-option label="综合" value="default" />
                  <el-option label="销量优先" value="sales" />
                  <el-option label="价格升序" value="price_asc" />
                  <el-option label="价格降序" value="price_desc" />
                  <el-option label="新品优先" value="new" />
                </el-select>
              </el-form-item>
              <el-form-item label="布局">
                <el-segmented :model-value="component.props?.layout || 'two-column'" :options="productLayoutOptions" @update:model-value="patchProps({ layout: $event })" />
              </el-form-item>
              <el-form-item v-if="productSource === 'manual'" label="商品 ID">
                <el-input v-model="productIdsText" type="textarea" :rows="3" placeholder="多个商品 ID 用英文逗号分隔" />
              </el-form-item>
              <el-form-item v-if="productSource === 'manual'" label="商品选择">
                <el-button @click="productSelectorVisible = true">
                  选择商品
                </el-button>
                <span class="property-panel__hint">已选 {{ selectedProducts().length }} 个</span>
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'notice-bar'">
              <el-divider>公告栏设置</el-divider>
              <el-form-item label="公告文案">
                <el-input
                  :model-value="items()[0]?.text || ''"
                  maxlength="60"
                  show-word-limit
                  @update:model-value="patchItems([{ ...(items()[0] || {}), text: String($event), link: items()[0]?.link || { type: 'page', path: '' } }])"
                />
              </el-form-item>
              <div class="property-panel__grid">
                <el-form-item label="滚动速度">
                  <el-input-number :model-value="component.props?.speed || 40" :min="10" :max="120" @update:model-value="patchProps({ speed: $event || 40 })" />
                </el-form-item>
                <el-form-item label="图标">
                  <el-switch :model-value="component.props?.showIcon !== false" @update:model-value="patchProps({ showIcon: Boolean($event) })" />
                </el-form-item>
              </div>
              <div class="property-panel__grid">
                <el-form-item label="背景色">
                  <el-color-picker :model-value="component.style?.background || '#fff7ed'" @update:model-value="patchStyle({ background: $event || '#fff7ed' })" />
                </el-form-item>
                <el-form-item label="文字色">
                  <el-color-picker :model-value="component.style?.color || '#c2410c'" @update:model-value="patchStyle({ color: $event || '#c2410c' })" />
                </el-form-item>
              </div>
            </template>

            <template v-else-if="component.type === 'coupon-group'">
              <el-divider>优惠券组设置</el-divider>
              <el-form-item label="标题">
                <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
              </el-form-item>
              <div class="property-panel__grid">
                <el-form-item label="展示数量">
                  <el-input-number :model-value="component.props?.limit || 3" :min="1" :max="10" @update:model-value="patchProps({ limit: $event || 3 })" />
                </el-form-item>
                <el-form-item label="布局">
                  <el-select :model-value="component.props?.layout || 'scroll'" @update:model-value="patchProps({ layout: $event })">
                    <el-option label="横向滚动" value="scroll" />
                    <el-option label="双列" value="two-column" />
                  </el-select>
                </el-form-item>
              </div>
              <el-form-item label="优惠券">
                <el-button @click="couponSelectorVisible = true">
                  选择优惠券
                </el-button>
                <span class="property-panel__hint">已选 {{ selectedCoupons().length }} 张</span>
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'marketing-entry'">
              <el-divider>营销入口设置</el-divider>
              <el-form-item label="标题">
                <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
              </el-form-item>
              <div class="property-panel__grid">
                <el-form-item label="卡片间距">
                  <el-input-number :model-value="component.props?.itemGap || 10" :min="0" :max="32" @update:model-value="patchProps({ itemGap: $event ?? 0 })" />
                </el-form-item>
                <el-form-item label="卡片圆角">
                  <el-input-number :model-value="component.props?.cardRadius || 10" :min="0" :max="32" @update:model-value="patchProps({ cardRadius: $event ?? 0 })" />
                </el-form-item>
              </div>
              <div class="property-panel__items">
                <div v-for="(item, index) in items()" :key="index" class="property-panel__item">
                  <div class="property-panel__item-head">
                    <strong>入口 {{ index + 1 }}</strong>
                    <el-button text type="danger" @click="removeItem(index)">
                      删除
                    </el-button>
                  </div>
                  <el-form-item label="主标题">
                    <el-input :model-value="item.title || ''" @update:model-value="patchItem(index, { title: String($event) })" />
                  </el-form-item>
                  <el-form-item label="副标题">
                    <el-input :model-value="item.subtitle || ''" @update:model-value="patchItem(index, { subtitle: String($event) })" />
                  </el-form-item>
                  <el-form-item label="角标文案">
                    <el-input :model-value="item.badge || ''" @update:model-value="patchItem(index, { badge: String($event) })" />
                  </el-form-item>
                  <div class="property-panel__grid">
                    <el-form-item label="背景色">
                      <el-color-picker :model-value="item.background || '#F0A18E'" @update:model-value="patchItem(index, { background: $event || '#F0A18E' })" />
                    </el-form-item>
                    <el-form-item label="文字色">
                      <el-color-picker :model-value="item.color || '#FFFFFF'" @update:model-value="patchItem(index, { color: $event || '#FFFFFF' })" />
                    </el-form-item>
                  </div>
                  <el-form-item label="跳转类型">
                    <el-select :model-value="item.link?.type || 'page'" @update:model-value="patchItemLink(index, { type: $event })">
                      <el-option v-for="option in linkTypeOptions" :key="option.value" :label="option.label" :value="option.value" />
                    </el-select>
                  </el-form-item>
                  <el-form-item v-if="(item.link?.type || 'page') === 'page'" label="页面路径">
                    <el-input :model-value="item.link?.path || item.link?.url || ''" @update:model-value="patchItemLink(index, { path: String($event) })" />
                  </el-form-item>
                  <el-form-item v-else label="业务 ID">
                    <el-input :model-value="item.link?.id || ''" @update:model-value="patchItemLink(index, { id: $event })" />
                  </el-form-item>
                </div>
                <el-button class="property-panel__add" @click="addItem(defaultMarketingEntryItem())">
                  添加入口
                </el-button>
              </div>
            </template>

            <template v-else-if="component.type === 'category-panel'">
              <el-divider>商品分类设置</el-divider>
              <el-form-item label="标题">
                <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
              </el-form-item>
              <div class="property-panel__grid">
                <el-form-item label="列数">
                  <el-input-number :model-value="component.props?.columns || 3" :min="2" :max="4" @update:model-value="patchProps({ columns: $event || 3 })" />
                </el-form-item>
                <el-form-item label="默认选中">
                  <el-input-number :model-value="component.props?.activeIndex || 0" :min="0" :max="20" @update:model-value="patchProps({ activeIndex: $event || 0 })" />
                </el-form-item>
              </div>
            </template>

            <template v-else-if="component.type === 'seckill-group'">
              <el-divider>秒杀组设置</el-divider>
              <el-form-item label="标题">
                <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
              </el-form-item>
              <div class="property-panel__grid">
                <el-form-item label="展示数量">
                  <el-input-number :model-value="component.props?.limit || 6" :min="1" :max="20" @update:model-value="patchProps({ limit: $event || 6 })" />
                </el-form-item>
                <el-form-item label="布局">
                  <el-select :model-value="component.props?.layout || 'scroll'" @update:model-value="patchProps({ layout: $event })">
                    <el-option label="横向滚动" value="scroll" />
                    <el-option label="双列" value="two-column" />
                  </el-select>
                </el-form-item>
              </div>
              <el-form-item label="秒杀场次">
                <el-button @click="seckillSelectorVisible = true">
                  选择场次
                </el-button>
                <span class="property-panel__hint">{{ component.data?.session?.title || '未选择' }}</span>
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'group-buy-group'">
              <el-divider>拼团组设置</el-divider>
              <el-form-item label="标题">
                <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
              </el-form-item>
              <div class="property-panel__grid">
                <el-form-item label="展示数量">
                  <el-input-number :model-value="component.props?.limit || 6" :min="1" :max="20" @update:model-value="patchProps({ limit: $event || 6 })" />
                </el-form-item>
                <el-form-item label="布局">
                  <el-select :model-value="component.props?.layout || 'two-column'" @update:model-value="patchProps({ layout: $event })">
                    <el-option label="双列" value="two-column" />
                    <el-option label="横向滚动" value="scroll" />
                  </el-select>
                </el-form-item>
              </div>
              <el-form-item label="拼团活动">
                <el-button @click="groupBuySelectorVisible = true">
                  选择活动
                </el-button>
                <span class="property-panel__hint">已选 {{ selectedGroupBuys().length }} 个</span>
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'product-rank'">
              <el-divider>商品榜单设置</el-divider>
              <el-form-item label="标题">
                <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
              </el-form-item>
              <div class="property-panel__grid">
                <el-form-item label="榜单类型">
                  <el-select :model-value="component.props?.rankType || 'hot'" @update:model-value="patchProps({ rankType: $event })">
                    <el-option label="热销榜" value="hot" />
                    <el-option label="新品榜" value="new" />
                    <el-option label="推荐榜" value="recommend" />
                  </el-select>
                </el-form-item>
                <el-form-item label="展示数量">
                  <el-input-number :model-value="component.props?.limit || 10" :min="1" :max="50" @update:model-value="patchProps({ limit: $event || 10 })" />
                </el-form-item>
              </div>
            </template>

            <template v-else-if="component.type === 'search-bar'">
              <el-divider>搜索框设置</el-divider>
              <el-form-item label="占位文案">
                <el-input :model-value="component.props?.placeholder || ''" maxlength="30" show-word-limit @update:model-value="patchProps({ placeholder: String($event) })" />
              </el-form-item>
              <div class="property-panel__grid">
                <el-form-item label="样式">
                  <el-select :model-value="component.props?.shape || 'round'" @update:model-value="patchProps({ shape: $event })">
                    <el-option label="圆角" value="round" />
                    <el-option label="方形" value="square" />
                  </el-select>
                </el-form-item>
                <el-form-item label="目标路径">
                  <el-input :model-value="component.props?.target || ''" @update:model-value="patchProps({ target: String($event) })" />
                </el-form-item>
              </div>
            </template>

            <template v-else-if="component.type === 'shop-info'">
              <el-divider>店铺信息设置</el-divider>
              <el-form-item label="店铺名称">
                <el-input :model-value="component.props?.name || ''" @update:model-value="patchProps({ name: String($event) })" />
              </el-form-item>
              <el-form-item label="店铺说明">
                <el-input :model-value="component.props?.description || ''" @update:model-value="patchProps({ description: String($event) })" />
              </el-form-item>
              <el-form-item label="Logo">
                <MaUploadImage
                  :model-value="component.props?.logo || ''"
                  :size="72"
                  @update:model-value="patchProps({ logo: Array.isArray($event) ? $event[0] || '' : $event })"
                />
              </el-form-item>
              <el-form-item label="服务标签">
                <el-input :model-value="(component.data?.tags || []).join(',')" placeholder="英文逗号分隔" @update:model-value="patchData({ tags: String($event).split(',').map(item => item.trim()).filter(Boolean) })" />
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'user-profile-header'">
              <el-divider>用户头部设置</el-divider>
              <el-form-item label="头像">
                <MaUploadImage
                  :model-value="component.props?.avatar || ''"
                  :size="72"
                  @update:model-value="patchProps({ avatar: Array.isArray($event) ? $event[0] || '' : $event })"
                />
              </el-form-item>
              <el-form-item label="昵称">
                <el-input :model-value="component.props?.nickname || ''" @update:model-value="patchProps({ nickname: String($event) })" />
              </el-form-item>
              <el-form-item label="邀请码">
                <el-input :model-value="component.props?.inviteCode || ''" @update:model-value="patchProps({ inviteCode: String($event) })" />
              </el-form-item>
              <el-form-item label="二维码图标">
                <MaUploadImage
                  :model-value="component.props?.qrcodeIcon || ''"
                  :size="48"
                  @update:model-value="patchProps({ qrcodeIcon: Array.isArray($event) ? $event[0] || '' : $event })"
                />
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'user-menu-list'">
              <el-divider>用户菜单设置</el-divider>
              <div class="property-panel__items">
                <div v-for="(item, index) in items()" :key="index" class="property-panel__item">
                  <div class="property-panel__item-head">
                    <strong>菜单 {{ index + 1 }}</strong>
                    <el-button text type="danger" @click="removeItem(index)">
                      删除
                    </el-button>
                  </div>
                  <div class="property-panel__grid">
                    <el-form-item label="菜单名称">
                      <el-input :model-value="item.label || ''" @update:model-value="patchItem(index, { label: String($event) })" />
                    </el-form-item>
                    <el-form-item label="右侧文案">
                      <el-input :model-value="item.value || ''" placeholder="例如：3 张可用" @update:model-value="patchItem(index, { value: String($event) })" />
                    </el-form-item>
                  </div>
                  <el-form-item label="图标">
                    <MaUploadImage
                      :model-value="item.icon || item.image || ''"
                      :size="56"
                      @update:model-value="patchItemIcon(index, $event)"
                    />
                  </el-form-item>
                  <el-form-item label="跳转类型">
                    <el-select :model-value="item.link?.type || 'page'" @update:model-value="patchItemLink(index, { type: $event })">
                      <el-option v-for="option in linkTypeOptions" :key="option.value" :label="option.label" :value="option.value" />
                    </el-select>
                  </el-form-item>
                  <el-form-item v-if="(item.link?.type || 'page') === 'page'" label="页面路径">
                    <el-input
                      :model-value="item.link?.path || item.link?.url || ''"
                      placeholder="例如：/pages/customer-service/index"
                      @update:model-value="patchItemLink(index, { type: 'page', path: String($event) })"
                    />
                  </el-form-item>
                  <el-form-item v-else label="业务 ID">
                    <el-input :model-value="item.link?.id || ''" @update:model-value="patchItemLink(index, { id: $event })" />
                  </el-form-item>
                </div>
                <el-button class="property-panel__add" @click="addItem(defaultUserMenuItem())">
                  添加菜单
                </el-button>
              </div>
            </template>

            <template v-else-if="component.type === 'rich-text'">
              <el-divider>富文本设置</el-divider>
              <el-form-item label="内容">
                <el-input :model-value="component.data?.content || ''" type="textarea" :rows="8" @update:model-value="patchData({ content: String($event) })" />
              </el-form-item>
              <el-form-item label="内边距">
                <el-input-number :model-value="component.props?.padding || 12" :min="0" :max="40" @update:model-value="patchProps({ padding: $event || 0 })" />
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'image-cube'">
              <el-divider>图片魔方设置</el-divider>
              <div class="property-panel__grid">
                <el-form-item label="布局">
                  <el-select :model-value="component.props?.layout || 'two'" @update:model-value="patchProps({ layout: $event })">
                    <el-option label="单图" value="one" />
                    <el-option label="两图" value="two" />
                    <el-option label="三图" value="three" />
                    <el-option label="四图" value="four" />
                    <el-option label="左一右二" value="left-one-right-two" />
                  </el-select>
                </el-form-item>
                <el-form-item label="间距">
                  <el-input-number :model-value="component.props?.gap || 8" :min="0" :max="24" @update:model-value="patchProps({ gap: $event || 0 })" />
                </el-form-item>
              </div>
              <el-form-item label="宽度模式">
                <el-segmented
                  :model-value="imageSizeDefaults('image-cube').widthMode"
                  :options="imageWidthModeOptions"
                  @update:model-value="patchProps({ widthMode: $event })"
                />
              </el-form-item>
              <div v-if="imageSizeDefaults('image-cube').widthMode === 'custom'" class="property-panel__grid">
                <el-form-item label="宽度单位">
                  <el-select :model-value="imageSizeDefaults('image-cube').widthUnit" @update:model-value="patchProps({ widthUnit: $event })">
                    <el-option label="百分比" value="percent" />
                    <el-option label="PX" value="px" />
                    <el-option label="RPX" value="rpx" />
                  </el-select>
                </el-form-item>
                <el-form-item label="宽度">
                  <el-input-number :model-value="imageSizeDefaults('image-cube').width" :min="1" :max="imageSizeDefaults('image-cube').widthUnit === 'percent' ? 100 : 750" @update:model-value="patchProps({ width: $event || 100 })" />
                </el-form-item>
              </div>
              <div class="property-panel__grid">
                <el-form-item label="图片高度">
                  <el-input-number :model-value="imageSizeDefaults('image-cube').height" :min="40" :max="600" @update:model-value="patchProps({ height: $event || 120 })" />
                </el-form-item>
                <el-form-item label="圆角">
                  <el-input-number :model-value="imageSizeDefaults('image-cube').radius" :min="0" :max="64" @update:model-value="patchProps({ radius: $event ?? 0 })" />
                </el-form-item>
              </div>
              <el-form-item label="填充方式">
                <el-select :model-value="imageSizeDefaults('image-cube').objectFit" @update:model-value="patchProps({ objectFit: $event })">
                  <el-option v-for="option in imageObjectFitOptions" :key="option.value" :label="option.label" :value="option.value" />
                </el-select>
              </el-form-item>
              <div class="property-panel__items">
                <div v-for="(item, index) in items()" :key="index" class="property-panel__item">
                  <div class="property-panel__item-head">
                    <strong>图片 {{ index + 1 }}</strong>
                    <el-button text type="danger" @click="removeItem(index)">
                      删除
                    </el-button>
                  </div>
                  <el-form-item label="图片">
                    <MaUploadImage
                      :model-value="item.image || ''"
                      :size="72"
                      @update:model-value="patchItemImage(index, $event)"
                    />
                  </el-form-item>
                  <el-form-item label="标题">
                    <el-input :model-value="item.title || ''" @update:model-value="patchItem(index, { title: String($event) })" />
                  </el-form-item>
                </div>
                <el-button class="property-panel__add" @click="addItem(defaultImageItem())">
                  添加图片
                </el-button>
              </div>
            </template>

            <template v-else-if="component.type === 'title-bar'">
              <el-divider>标题栏设置</el-divider>
              <el-form-item label="主标题">
                <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
              </el-form-item>
              <el-form-item label="副标题">
                <el-input :model-value="component.props?.subtitle || ''" @update:model-value="patchProps({ subtitle: String($event) })" />
              </el-form-item>
              <el-form-item label="标题颜色">
                <el-color-picker :model-value="component.style?.color || '#1f2937'" @update:model-value="patchStyle({ color: $event || '#1f2937' })" />
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'gap'">
              <el-divider>空白设置</el-divider>
              <el-form-item label="高度">
                <el-input-number :model-value="component.props?.height || 16" :min="4" :max="120" @update:model-value="patchProps({ height: $event || 16 })" />
              </el-form-item>
              <el-form-item label="背景色">
                <el-color-picker :model-value="component.props?.background || '#f6f7f8'" @update:model-value="patchProps({ background: $event || 'transparent' })" />
              </el-form-item>
            </template>

            <template v-else-if="component.type === 'divider'">
              <el-divider>分割线设置</el-divider>
              <el-form-item label="颜色">
                <el-color-picker :model-value="component.props?.color || '#e8ecef'" @update:model-value="patchProps({ color: $event || '#e8ecef' })" />
              </el-form-item>
              <el-form-item label="左右边距">
                <el-input-number :model-value="component.props?.margin || 24" :min="0" :max="80" @update:model-value="patchProps({ margin: $event || 0 })" />
              </el-form-item>
            </template>
          </el-tab-pane>
          <el-tab-pane label="样式" name="style">
            <el-divider>文字</el-divider>
            <el-form-item label="文字对齐">
              <el-segmented :model-value="component.style?.textAlign || 'left'" :options="textAlignOptions" @update:model-value="patchStyle({ textAlign: $event })" />
            </el-form-item>
            <div class="property-panel__grid">
              <el-form-item label="字号">
                <el-input-number :model-value="component.style?.fontSize || 14" :min="10" :max="32" @update:model-value="patchStyle({ fontSize: $event || 14 })" />
              </el-form-item>
              <el-form-item label="字重">
                <el-select :model-value="component.style?.fontWeight || 400" @update:model-value="patchStyle({ fontWeight: $event })">
                  <el-option v-for="option in fontWeightOptions" :key="option.value" :label="option.label" :value="option.value" />
                </el-select>
              </el-form-item>
            </div>
            <el-form-item label="字体">
              <el-select :model-value="component.style?.fontFamily || ''" @update:model-value="patchStyle({ fontFamily: $event })">
                <el-option v-for="option in fontFamilyOptions" :key="option.label" :label="option.label" :value="option.value" />
              </el-select>
            </el-form-item>
            <div class="property-panel__grid">
              <el-form-item label="文字色">
                <el-color-picker :model-value="component.style?.color || '#1f2937'" @update:model-value="patchStyle({ color: $event || '#1f2937' })" />
              </el-form-item>
              <el-form-item label="背景色">
                <el-color-picker :model-value="component.style?.backgroundColor || component.style?.background || '#ffffff'" @update:model-value="patchStyle({ backgroundColor: $event || '#ffffff' })" />
              </el-form-item>
            </div>
            <el-divider>容器</el-divider>
            <el-form-item label="外边距">
              <div class="property-panel__edge-grid">
                <label><span>上</span><el-input-number :model-value="edgeValue(component.style, 'margin', 'top')" :min="-80" :max="120" :controls="false" @update:model-value="patchStyleEdge('margin', 'top', $event ?? 0)" /></label>
                <label><span>右</span><el-input-number :model-value="edgeValue(component.style, 'margin', 'right')" :min="0" :max="120" :controls="false" @update:model-value="patchStyleEdge('margin', 'right', $event ?? 0)" /></label>
                <label><span>下</span><el-input-number :model-value="edgeValue(component.style, 'margin', 'bottom')" :min="0" :max="120" :controls="false" @update:model-value="patchStyleEdge('margin', 'bottom', $event ?? 0)" /></label>
                <label><span>左</span><el-input-number :model-value="edgeValue(component.style, 'margin', 'left')" :min="0" :max="120" :controls="false" @update:model-value="patchStyleEdge('margin', 'left', $event ?? 0)" /></label>
              </div>
              <span class="property-panel__hint">顺序：上 / 右 / 下 / 左，单位 px，小程序自动转 rpx</span>
            </el-form-item>
            <el-form-item label="内边距">
              <div class="property-panel__edge-grid">
                <label><span>上</span><el-input-number :model-value="edgeValue(component.style, 'padding', 'top')" :min="0" :max="120" :controls="false" @update:model-value="patchStyleEdge('padding', 'top', $event ?? 0)" /></label>
                <label><span>右</span><el-input-number :model-value="edgeValue(component.style, 'padding', 'right')" :min="0" :max="120" :controls="false" @update:model-value="patchStyleEdge('padding', 'right', $event ?? 0)" /></label>
                <label><span>下</span><el-input-number :model-value="edgeValue(component.style, 'padding', 'bottom')" :min="0" :max="120" :controls="false" @update:model-value="patchStyleEdge('padding', 'bottom', $event ?? 0)" /></label>
                <label><span>左</span><el-input-number :model-value="edgeValue(component.style, 'padding', 'left')" :min="0" :max="120" :controls="false" @update:model-value="patchStyleEdge('padding', 'left', $event ?? 0)" /></label>
              </div>
              <span class="property-panel__hint">顺序：上 / 右 / 下 / 左，单位 px，小程序自动转 rpx</span>
            </el-form-item>
            <div class="property-panel__grid">
              <el-form-item label="上边距">
                <el-input-number :model-value="edgeValue(component.style, 'margin', 'top')" :min="-80" :max="80" @update:model-value="patchStyleEdge('margin', 'top', $event ?? 0)" />
              </el-form-item>
              <el-form-item label="下边距">
                <el-input-number :model-value="edgeValue(component.style, 'margin', 'bottom')" :min="0" :max="80" @update:model-value="patchStyleEdge('margin', 'bottom', $event ?? 0)" />
              </el-form-item>
              <el-form-item label="内边距(同步四边)">
                <el-input-number
                  :model-value="edgeValue(component.style, 'padding', 'top')"
                  :min="0"
                  :max="80"
                  @update:model-value="patchStyle({ padding: { top: $event ?? 0, right: $event ?? 0, bottom: $event ?? 0, left: $event ?? 0 } })"
                />
              </el-form-item>
              <el-form-item label="顶部圆角">
                <el-input-number :model-value="radiusSideValue('top')" :min="0" :max="40" @update:model-value="patchRadiusSide('top', $event ?? 0)" />
              </el-form-item>
              <el-form-item label="底部圆角">
                <el-input-number :model-value="radiusSideValue('bottom')" :min="0" :max="40" @update:model-value="patchRadiusSide('bottom', $event ?? 0)" />
              </el-form-item>
            </div>
            <template v-if="component.type === 'product-group'">
              <el-divider>商品列表</el-divider>
              <div class="property-panel__grid">
                <el-form-item label="卡片间距">
                  <el-input-number :model-value="component.props?.gap || 10" :min="0" :max="40" @update:model-value="patchProps({ gap: $event ?? 0 })" />
                </el-form-item>
              </div>
              <el-form-item label="列表边距">
                <div class="property-panel__edge-grid">
                  <label><span>上</span><el-input-number :model-value="listPaddingValue('top')" :min="0" :max="120" :controls="false" @update:model-value="patchListPadding('top', $event ?? 0)" /></label>
                  <label><span>右</span><el-input-number :model-value="listPaddingValue('right')" :min="0" :max="120" :controls="false" @update:model-value="patchListPadding('right', $event ?? 0)" /></label>
                  <label><span>下</span><el-input-number :model-value="listPaddingValue('bottom')" :min="0" :max="120" :controls="false" @update:model-value="patchListPadding('bottom', $event ?? 0)" /></label>
                  <label><span>左</span><el-input-number :model-value="listPaddingValue('left')" :min="0" :max="120" :controls="false" @update:model-value="patchListPadding('left', $event ?? 0)" /></label>
                </div>
                <span class="property-panel__hint">控制商品卡片列表与屏幕两侧的距离，首页设计稿是 0 / 16 / 12 / 16</span>
              </el-form-item>
            </template>
          </el-tab-pane>
          <el-tab-pane label="交互" name="interaction">
            <el-divider>点击行为</el-divider>
            <el-form-item label="跳转类型">
              <el-select :model-value="component.props?.link?.type || 'none'" @update:model-value="patchLink({ type: $event })">
                <el-option label="无跳转" value="none" />
                <el-option v-for="option in linkTypeOptions" :key="option.value" :label="option.label" :value="option.value" />
              </el-select>
            </el-form-item>
            <el-form-item v-if="component.props?.link?.type && component.props.link.type !== 'none' && component.props.link.type !== 'page'" label="业务 ID">
              <el-input :model-value="component.props?.link?.id || ''" @update:model-value="patchLink({ id: $event })" />
            </el-form-item>
            <el-form-item v-else-if="component.props?.link?.type === 'page'" label="页面路径">
              <el-input :model-value="component.props?.link?.path || ''" @update:model-value="patchLink({ path: String($event) })" />
            </el-form-item>
            <el-divider>展示行为</el-divider>
            <div class="property-panel__grid">
              <el-form-item label="登录可见">
                <el-switch :model-value="component.props?.needLogin === true" @update:model-value="patchProps({ needLogin: Boolean($event) })" />
              </el-form-item>
              <el-form-item label="点击统计">
                <el-switch :model-value="component.props?.trackClick !== false" @update:model-value="patchProps({ trackClick: Boolean($event) })" />
              </el-form-item>
            </div>
          </el-tab-pane>
          <el-tab-pane label="数据源" name="datasource">
            <template v-if="component.type === 'product-group' || component.type === 'product-rank'">
              <el-form-item label="商品来源">
                <el-select :model-value="productSource" @update:model-value="patchProps({ source: $event })">
                  <el-option label="手动选择" value="manual" />
                  <el-option label="推荐商品" value="recommend" />
                  <el-option label="热卖商品" value="hot" />
                  <el-option label="新品商品" value="new" />
                  <el-option label="按分类" value="category" />
                  <el-option label="按标签" value="tag" />
                </el-select>
              </el-form-item>
              <el-form-item label="商品选择">
                <el-button @click="productSelectorVisible = true">
                  选择商品
                </el-button>
                <span class="property-panel__hint">已选 {{ selectedProducts().length }} 个</span>
              </el-form-item>
            </template>
            <template v-else-if="component.type === 'coupon-group'">
              <el-form-item label="优惠券">
                <el-button @click="couponSelectorVisible = true">
                  选择优惠券
                </el-button>
                <span class="property-panel__hint">已选 {{ selectedCoupons().length }} 张</span>
              </el-form-item>
            </template>
            <template v-else-if="component.type === 'seckill-group'">
              <el-form-item label="秒杀场次">
                <el-button @click="seckillSelectorVisible = true">
                  选择场次
                </el-button>
                <span class="property-panel__hint">{{ component.data?.session?.title || '未选择' }}</span>
              </el-form-item>
            </template>
            <template v-else-if="component.type === 'group-buy-group'">
              <el-form-item label="拼团活动">
                <el-button @click="groupBuySelectorVisible = true">
                  选择活动
                </el-button>
                <span class="property-panel__hint">已选 {{ selectedGroupBuys().length }} 个</span>
              </el-form-item>
            </template>
            <el-empty v-else description="当前组件不需要外部数据源" />
          </el-tab-pane>
          <el-tab-pane label="高级" name="advanced">
            <el-collapse v-model="advancedVisible" class="property-panel__advanced">
              <el-collapse-item title="高级 JSON 配置" name="json">
                <el-form-item label="Props JSON">
                  <el-input v-model="propsJson" type="textarea" :rows="7" spellcheck="false" />
                </el-form-item>
                <el-form-item label="Data JSON">
                  <el-input v-model="dataJson" type="textarea" :rows="10" spellcheck="false" />
                </el-form-item>
                <el-form-item label="Style JSON">
                  <el-input v-model="styleJson" type="textarea" :rows="5" spellcheck="false" />
                </el-form-item>
                <el-button type="primary" @click="applyJson">
                  应用 JSON
                </el-button>
              </el-collapse-item>
            </el-collapse>
          </el-tab-pane>
        </el-tabs>
      </el-form>
      <ProductSelector v-model:visible="productSelectorVisible" @confirm="onProductsSelected" />
      <CouponSelector v-model:visible="couponSelectorVisible" @confirm="onCouponsSelected" />
      <SeckillSelector v-model:visible="seckillSelectorVisible" @confirm="onSeckillSelected" />
      <GroupBuySelector v-model:visible="groupBuySelectorVisible" @confirm="onGroupBuysSelected" />
    </template>
  </aside>
</template>

<style scoped lang="scss">
.property-panel {
  width: 380px;
  border-left: 1px solid #e5eaf2;
  background: #fff;
  color: #111827;
  overflow: auto;

  --el-bg-color: #ffffff;
  --el-bg-color-overlay: #ffffff;
  --el-fill-color-blank: #ffffff;
  --el-fill-color-light: #f8fafc;
  --el-border-color: #d8dee9;
  --el-border-color-light: #e5eaf2;
  --el-text-color-primary: #111827;
  --el-text-color-regular: #334155;
  --el-text-color-secondary: #64748b;
  --el-input-bg-color: #ffffff;
  --el-input-border-color: #d8dee9;
  --el-input-hover-border-color: #b7c2d4;
  --el-input-focus-border-color: #2563eb;
}

.property-panel__head {
  height: 48px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #eef2f7;
  color: #111827;
  font-size: 14px;
  font-weight: 600;
}

.property-panel__form {
  padding: 16px;
}

.property-panel :deep(.el-form-item__label) {
  color: #94a3b8;
}

.property-panel :deep(.el-input__wrapper),
.property-panel :deep(.el-textarea__inner),
.property-panel :deep(.el-select__wrapper),
.property-panel :deep(.el-input-number .el-input__wrapper) {
  background: #ffffff;
  box-shadow: 0 0 0 1px #d8dee9 inset;
}

.property-panel :deep(.el-input__inner),
.property-panel :deep(.el-textarea__inner) {
  color: #111827;
  -webkit-text-fill-color: #111827;
}

.property-panel :deep(.el-input__wrapper.is-focus),
.property-panel :deep(.el-select__wrapper.is-focused),
.property-panel :deep(.el-textarea__inner:focus) {
  box-shadow: 0 0 0 1px #2563eb inset;
}

.property-panel :deep(.el-tabs__item) {
  color: #94a3b8;
}

.property-panel :deep(.el-tabs__item.is-active) {
  color: #2563eb;
}

.property-panel :deep(.el-divider__text) {
  background: #ffffff;
  color: #111827;
}

.property-panel :deep(.ma-upload-container) {
  border-color: #d8dee9;
  background: #f8fafc;
  color: #64748b;
}

.property-panel :deep(.ma-upload-placeholder__text) {
  color: #64748b;
}

.property-panel :deep(.ma-resource-btn) {
  border-color: #d8dee9;
  background: rgba(255, 255, 255, 0.96);
  color: #64748b;
}

.property-panel__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.property-panel__edge-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;

  label {
    display: grid;
    grid-template-columns: 24px minmax(0, 1fr);
    align-items: center;
    gap: 6px;
    min-width: 0;
  }

  span {
    color: #64748b;
    font-size: 12px;
    text-align: right;
  }

  :deep(.el-input-number) {
    width: 100%;
  }

  :deep(.el-input__inner) {
    padding-left: 8px;
    padding-right: 8px;
    text-align: left;
  }
}

.property-panel__items {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.property-panel__item {
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
}

.property-panel__item-head {
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;

  strong {
    font-size: 13px;
  }
}

.property-panel__add {
  width: 100%;
}

.property-panel__hint {
  margin-left: 10px;
  font-size: 12px;
  color: #6b7280;
}

.property-panel__advanced {
  margin-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.property-panel__tabs {
  margin-top: 8px;
}

.property-panel__placeholder {
  padding: 18px 12px;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  background: #f9fafb;
  color: #6b7280;
  font-size: 12px;
  line-height: 18px;
}
</style>
