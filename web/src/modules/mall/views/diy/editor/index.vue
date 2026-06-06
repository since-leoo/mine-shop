<script setup lang="ts">
import type { DiyComponent, DiySchema } from '../schema/types'
import { ElMessage } from 'element-plus'
import { useRoute, useRouter } from 'vue-router'
import { cloneDeep } from 'lodash-es'
import { getDiyPage, publishDiyPage, resetDiyDraft, saveDiyDraft } from '~/mall/api/diyPage'
import ComponentLibrary from '../components/ComponentLibrary.vue'
import PhonePreview from '../components/PhonePreview.vue'
import PropertyPanel from '../components/PropertyPanel.vue'
import { componentRegistry, createDefaultSchema } from '../schema/componentRegistry'

defineOptions({ name: 'mall:diy:editor' })

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const pageId = computed(() => Number(route.query.id || 0))
const pageInfo = ref<any>(null)
const selectedId = ref('')
const schema = ref<DiySchema>(createDefaultSchema('home', '首页'))
const selectedComponent = computed(() => schema.value.components.find(item => item.id === selectedId.value) || null)

function latestDraft(page: any) {
  const versions = Array.isArray(page?.versions) ? page.versions : []
  return versions.find((item: any) => item.status === 'draft') || page?.published_version || versions[0]
}

async function load() {
  if (!pageId.value) {
    ElMessage.error('缺少页面 ID')
    return
  }

  loading.value = true
  try {
    const res = await getDiyPage(pageId.value)
    pageInfo.value = res.data
    const draft = latestDraft(res.data)
    schema.value = draft?.schema || createDefaultSchema(res.data.page_key, res.data.title)
    schema.value.page.key = res.data.page_key
    schema.value.page.title = res.data.title
    selectedId.value = schema.value.components[0]?.id || ''
  }
  finally {
    loading.value = false
  }
}

function addComponent(type: string) {
  const meta = componentRegistry.find(item => item.type === type)
  if (!meta)
    return
  const component = meta.defaults()
  schema.value.components.push(component)
  selectedId.value = component.id
}

function indexOf(id: string) {
  return schema.value.components.findIndex(item => item.id === id)
}

function move(id: string, offset: number) {
  const index = indexOf(id)
  const next = index + offset
  if (index < 0 || next < 0 || next >= schema.value.components.length)
    return
  const list = schema.value.components
  const [item] = list.splice(index, 1)
  list.splice(next, 0, item)
}

function copyComponent(id: string) {
  const source = schema.value.components.find(item => item.id === id)
  if (!source)
    return
  const copy = cloneDeep(source)
  copy.id = `${source.type}-${Date.now()}`
  copy.name = `${source.name}副本`
  schema.value.components.splice(indexOf(id) + 1, 0, copy)
  selectedId.value = copy.id
}

function removeComponent(id: string) {
  schema.value.components = schema.value.components.filter(item => item.id !== id)
  selectedId.value = schema.value.components[0]?.id || ''
}

function toggleComponent(id: string) {
  const component = schema.value.components.find(item => item.id === id)
  if (component)
    component.enabled = component.enabled === false
}

function updateComponent(component: DiyComponent) {
  const index = indexOf(component.id)
  if (index >= 0)
    schema.value.components[index] = component
}

async function saveDraft() {
  saving.value = true
  try {
    await saveDiyDraft(pageId.value, schema.value)
    ElMessage.success('草稿已保存')
  }
  finally {
    saving.value = false
  }
}

async function publish() {
  await saveDraft()
  await publishDiyPage(pageId.value)
  ElMessage.success('页面已发布')
  load()
}

async function resetDraft() {
  await resetDiyDraft(pageId.value)
  ElMessage.success('草稿已重置')
  load()
}

onMounted(load)
</script>

<template>
  <div v-loading="loading" class="diy-editor">
    <header class="diy-editor__toolbar">
      <div class="diy-editor__title">
        <strong>{{ pageInfo?.title || 'DIY 页面装修' }}</strong>
        <span>{{ pageInfo?.page_key }} / {{ pageInfo?.page_type }}</span>
      </div>
      <div class="diy-editor__actions">
        <el-button @click="router.push('/mall/diy/page')"><ma-svg-icon name="ph:arrow-left" size="14" />返回列表</el-button>
        <el-button @click="resetDraft">重置草稿</el-button>
        <el-button :loading="saving" type="primary" @click="saveDraft">保存草稿</el-button>
        <el-button type="success" @click="publish">发布</el-button>
      </div>
    </header>
    <main class="diy-editor__main">
      <ComponentLibrary @add="addComponent" />
      <PhonePreview
        :schema="schema"
        :selected-id="selectedId"
        @select="selectedId = $event"
        @move-up="move($event, -1)"
        @move-down="move($event, 1)"
        @copy="copyComponent"
        @remove="removeComponent"
        @toggle="toggleComponent"
      />
      <PropertyPanel :component="selectedComponent" @update="updateComponent" />
    </main>
  </div>
</template>

<style scoped lang="scss">
.diy-editor {
  height: calc(100vh - 84px);
  display: flex;
  flex-direction: column;
  background: #f3f4f6;
}

.diy-editor__toolbar {
  height: 56px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
}

.diy-editor__title {
  display: flex;
  flex-direction: column;
  gap: 3px;

  strong {
    font-size: 15px;
    line-height: 20px;
  }

  span {
    font-size: 12px;
    color: #6b7280;
  }
}

.diy-editor__actions {
  display: flex;
  gap: 8px;
}

.diy-editor__main {
  flex: 1;
  min-height: 0;
  display: flex;
}
</style>
