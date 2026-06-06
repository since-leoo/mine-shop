<script setup lang="ts">
import type { DiyComponent, DiyLink } from '../schema/types'
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'

type EditableItem = Record<string, any> & {
  link?: DiyLink
}

const props = defineProps<{
  component?: DiyComponent | null
}>()

const emit = defineEmits<{
  update: [component: DiyComponent]
}>()

const advancedVisible = ref(false)
const propsJson = ref('{}')
const dataJson = ref('{}')
const styleJson = ref('{}')

const title = computed(() => props.component ? `${props.component.name} / ${props.component.type}` : '未选择组件')

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

        <template v-if="component.type === 'banner'">
          <el-divider>轮播设置</el-divider>
          <div class="property-panel__grid">
            <el-form-item label="高度">
              <el-input-number :model-value="component.props?.height || 160" :min="80" :max="360" @update:model-value="patchProps({ height: $event || 160 })" />
            </el-form-item>
            <el-form-item label="圆角">
              <el-input-number :model-value="component.props?.radius || 8" :min="0" :max="32" @update:model-value="patchProps({ radius: $event || 0 })" />
            </el-form-item>
          </div>
          <el-form-item label="自动播放">
            <el-switch :model-value="component.props?.autoplay !== false" @update:model-value="patchProps({ autoplay: Boolean($event) })" />
          </el-form-item>
          <div class="property-panel__items">
            <div v-for="(item, index) in items()" :key="index" class="property-panel__item">
              <div class="property-panel__item-head">
                <strong>轮播图 {{ index + 1 }}</strong>
                <el-button text type="danger" @click="removeItem(index)">删除</el-button>
              </div>
              <el-form-item label="图片地址">
                <el-input :model-value="item.image || item.url || ''" @update:model-value="patchItem(index, { image: String($event) })" />
              </el-form-item>
              <el-form-item label="标题">
                <el-input :model-value="item.title || ''" @update:model-value="patchItem(index, { title: String($event) })" />
              </el-form-item>
              <el-form-item label="跳转类型">
                <el-select :model-value="item.link?.type || 'page'" @update:model-value="patchItemLink(index, { type: $event })">
                  <el-option label="页面路径" value="page" />
                  <el-option label="商品详情" value="product" />
                  <el-option label="分类结果" value="category" />
                  <el-option label="优惠券" value="coupon" />
                  <el-option label="拼团" value="group_buy" />
                  <el-option label="秒杀" value="seckill" />
                </el-select>
              </el-form-item>
              <el-form-item v-if="(item.link?.type || 'page') === 'page'" label="页面路径">
                <el-input :model-value="item.link?.path || item.link?.url || ''" @update:model-value="patchItemLink(index, { path: String($event) })" />
              </el-form-item>
              <el-form-item v-else label="业务 ID">
                <el-input :model-value="item.link?.id || ''" @update:model-value="patchItemLink(index, { id: $event })" />
              </el-form-item>
            </div>
            <el-button class="property-panel__add" @click="addItem(defaultImageItem())">添加轮播图</el-button>
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
                <el-button text type="danger" @click="removeItem(index)">删除</el-button>
              </div>
              <el-form-item label="入口名称">
                <el-input :model-value="item.title || item.name || ''" @update:model-value="patchItem(index, { title: String($event) })" />
              </el-form-item>
              <el-form-item label="图标地址">
                <el-input :model-value="item.icon || item.image || ''" @update:model-value="patchItem(index, { icon: String($event) })" />
              </el-form-item>
              <el-form-item label="页面路径">
                <el-input :model-value="item.link?.path || item.link?.url || ''" @update:model-value="patchItemLink(index, { type: 'page', path: String($event) })" />
              </el-form-item>
            </div>
            <el-button class="property-panel__add" @click="addItem(defaultNavItem())">添加入口</el-button>
          </div>
        </template>

        <template v-else-if="component.type === 'image-ad'">
          <el-divider>图片广告</el-divider>
          <el-form-item label="布局">
            <el-segmented
              :model-value="component.props?.layout || 'single'"
              :options="[
                { label: '单图', value: 'single' },
                { label: '双列', value: 'two-column' },
              ]"
              @update:model-value="patchProps({ layout: $event })"
            />
          </el-form-item>
          <div class="property-panel__items">
            <div v-for="(item, index) in items()" :key="index" class="property-panel__item">
              <div class="property-panel__item-head">
                <strong>广告图 {{ index + 1 }}</strong>
                <el-button text type="danger" @click="removeItem(index)">删除</el-button>
              </div>
              <el-form-item label="图片地址">
                <el-input :model-value="item.image || item.url || ''" @update:model-value="patchItem(index, { image: String($event) })" />
              </el-form-item>
              <el-form-item label="页面路径">
                <el-input :model-value="item.link?.path || item.link?.url || ''" @update:model-value="patchItemLink(index, { type: 'page', path: String($event) })" />
              </el-form-item>
            </div>
            <el-button class="property-panel__add" @click="addItem(defaultImageItem())">添加广告图</el-button>
          </div>
        </template>

        <template v-else-if="component.type === 'product-group'">
          <el-divider>商品组设置</el-divider>
          <el-form-item label="标题">
            <el-input :model-value="component.props?.title || ''" @update:model-value="patchProps({ title: String($event) })" />
          </el-form-item>
          <el-form-item label="商品来源">
            <el-select :model-value="component.data?.mode || 'recommend'" @update:model-value="patchData({ mode: $event })">
              <el-option label="手动选择" value="manual" />
              <el-option label="推荐商品" value="recommend" />
              <el-option label="热卖商品" value="hot" />
              <el-option label="新品商品" value="new" />
            </el-select>
          </el-form-item>
          <el-form-item label="展示数量">
            <el-input-number :model-value="component.props?.limit || 10" :min="1" :max="50" @update:model-value="patchProps({ limit: $event || 10 })" />
          </el-form-item>
          <el-form-item label="布局">
            <el-segmented
              :model-value="component.props?.layout || 'two-column'"
              :options="[
                { label: '双列', value: 'two-column' },
                { label: '单列', value: 'single' },
              ]"
              @update:model-value="patchProps({ layout: $event })"
            />
          </el-form-item>
          <el-form-item v-if="component.data?.mode === 'manual'" label="商品 ID">
            <el-input v-model="productIdsText" type="textarea" :rows="3" placeholder="多个商品 ID 用英文逗号分隔" />
          </el-form-item>
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

        <el-collapse v-model="advancedVisible" class="property-panel__advanced">
          <el-collapse-item title="高级 JSON 配置" :name="true">
            <el-form-item label="Props JSON">
              <el-input v-model="propsJson" type="textarea" :rows="7" spellcheck="false" />
            </el-form-item>
            <el-form-item label="Data JSON">
              <el-input v-model="dataJson" type="textarea" :rows="10" spellcheck="false" />
            </el-form-item>
            <el-form-item label="Style JSON">
              <el-input v-model="styleJson" type="textarea" :rows="5" spellcheck="false" />
            </el-form-item>
            <el-button type="primary" @click="applyJson">应用 JSON</el-button>
          </el-collapse-item>
        </el-collapse>
      </el-form>
    </template>
  </aside>
</template>

<style scoped lang="scss">
.property-panel {
  width: 360px;
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

.property-panel__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
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

.property-panel__advanced {
  margin-top: 16px;
  border-top: 1px solid #e5e7eb;
}
</style>
