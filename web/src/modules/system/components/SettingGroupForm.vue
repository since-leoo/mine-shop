<template>
  <div class="setting-group-root">
    <div class="setting-group-page">
      <section class="group-hero">
      <div class="hero-text">
        <div class="hero-label">System Configuration</div>
        <div class="hero-title">{{ computedTitle }}</div>
        <div class="hero-desc">
          {{ computedDescription || t('mall.system.configDesc') }}
        </div>
      </div>
      <div class="hero-actions">
        <el-button
          :icon="RefreshRight"
          text
          type="primary"
          :loading="loading"
          @click="handleRefresh"
        >
          {{ t('mall.system.refreshData') }}
        </el-button>
      </div>
    </section>

      <section class="group-body">
      <div v-if="loading" class="skeleton-wrap">
        <el-skeleton :rows="6" animated />
      </div>
      <el-empty v-else-if="!settings.length" :description="t('mall.system.noConfigItems')" />
      <div v-else class="setting-grid">
        <div
          v-for="item in displaySettings"
          :key="item.key"
          class="setting-grid__item"
          :class="cardSizeClass(item)"
        >
          <el-card class="setting-card" shadow="never">
            <div class="setting-card__header">
              <div class="setting-card__meta">
                <div class="setting-card__icon">
                  <component :is="resolveIcon(item)" />
                </div>
                <div>
                  <div class="setting-card__title">{{ item.label }}</div>
                  <div class="setting-card__desc">{{ item.description || t('mall.system.noDescription') }}</div>
                </div>
              </div>
              <div class="setting-card__tags">
                <el-tag size="small" :type="resolveTagType(item.type)" effect="dark">
                  {{ resolveTypeLabel(item.type) }}
                </el-tag>
                <el-tag
                  v-if="item.is_sensitive"
                  size="small"
                  type="danger"
                  effect="plain"
                >
                  {{ t('mall.system.sensitive') }}
                </el-tag>
              </div>
            </div>
        <div class="setting-card__body">
            <div v-if="isStandaloneDialog(item)" class="dialog-card">
              <p class="dialog-card__desc">{{ item.description || t('mall.system.clickToConfig') }}</p>
              <el-button type="primary" @click="openDialog(item)">
                {{ dialogButtonLabel(item) }}
              </el-button>
            </div>
            <template v-else>
            <div v-if="isSelect(item)" class="control-inline">
              <el-select
                v-model="formValues[item.key]"
                :placeholder="t('mall.system.selectPlaceholder')"
                clearable
                class="w-full"
              >
                <el-option
                  v-for="option in item.meta?.options || []"
                  :key="option.value"
                  :label="option.label"
                  :value="option.value"
                />
              </el-select>
              <div v-if="hasDialogAction(item.key)" class="dialog-actions">
                <el-button
                  v-for="dialog in getDialogTargets(item.key)"
                  :key="dialog.key"
                  size="small"
                  link
                  type="primary"
                  :disabled="!isDialogTriggerActive(item.key, dialog)"
                  @click="openDialog(dialog)"
                >
                  {{ dialogButtonLabel(dialog) }}
                </el-button>
              </div>
            </div>

            <div v-else-if="item.type === 'boolean'" class="control-inline">
              <el-switch v-model="formValues[item.key]" />
              <div v-if="hasDialogAction(item.key)" class="dialog-actions">
                <el-button
                  v-for="dialog in getDialogTargets(item.key)"
                  :key="dialog.key"
                  size="small"
                  link
                  type="primary"
                  :disabled="!isDialogTriggerActive(item.key, dialog)"
                  @click="openDialog(dialog)"
                >
                  {{ dialogButtonLabel(dialog) }}
                </el-button>
              </div>
            </div>

            <el-input-number
              v-else-if="item.type === 'integer' || item.type === 'decimal'"
              v-model="formValues[item.key]"
              :precision="item.type === 'decimal' ? 2 : 0"
              :step="item.type === 'decimal' ? 0.01 : 1"
              :controls="false"
              class="w-200px"
            />

            <MaUploadImage
              v-else-if="isUpload(item)"
              v-model="formValues[item.key]"
              :limit="1"
              :size="item.meta?.preview_size ?? 90"
            />

            <div
              v-else-if="isStructuredForm(item) && formValues[item.key]"
              :class="['structured-form', { 'structured-form--inline': item.meta?.layout === 'inline' }]"
            >
              <div
                v-for="field in getStructuredFields(item)"
                :key="field.key"
                class="structured-form__row"
              >
                <div class="structured-form__label">
                  {{ field.label }}
                  <span v-if="field.required" class="structured-form__required">*</span>
                </div>
                <el-switch
                  v-if="getFieldComponent(field) === 'switch'"
                  v-model="formValues[item.key][field.key]"
                />
                <el-input-number
                  v-else-if="getFieldComponent(field) === 'number'"
                  v-model="formValues[item.key][field.key]"
                  :min="field.min"
                  :max="field.max"
                  :controls="false"
                  class="w-200px"
                />
                <el-select
                  v-else-if="getFieldComponent(field) === 'select'"
                  v-model="formValues[item.key][field.key]"
                  :placeholder="t('mall.system.selectPlaceholder')"
                  clearable
                  class="w-full"
                >
                  <el-option
                    v-for="option in field.options || []"
                    :key="option.value"
                    :label="option.label"
                    :value="option.value"
                  />
                </el-select>
                <el-input
                  v-else-if="getFieldComponent(field) === 'textarea'"
                  v-model="formValues[item.key][field.key]"
                  type="textarea"
                  :rows="field.rows || 3"
                  :placeholder="field.placeholder || t('form.pleaseInput', { msg: field.label })"
                />
                <el-input
                  v-else
                  v-model="formValues[item.key][field.key]"
                  :placeholder="field.placeholder || t('form.pleaseInput', { msg: field.label })"
                  :show-password="getFieldComponent(field) === 'password'"
                  :type="getFieldComponent(field) === 'password' ? 'password' : 'text'"
                  clearable
                />
                <div v-if="structuredErrors[item.key]?.[field.key]" class="structured-form__error">
                  {{ structuredErrors[item.key][field.key] }}
                </div>
              </div>
            </div>

            <div v-else-if="isCollectionForm(item)" class="collection-form">
              <div
                v-for="(entry, index) in formValues[item.key]"
                :key="index"
                class="collection-form__entry"
              >
                <div class="collection-form__header">
                  <div>{{ t('mall.system.configN', { n: index + 1 }) }}</div>
                  <el-button
                    text
                    type="danger"
                    :disabled="!canRemoveCollection(item)"
                    @click="removeCollectionRow(item, index)"
                  >
                    {{ t('mall.system.deleteAction') }}
                  </el-button>
                </div>
                <div class="collection-form__fields">
                  <div
                    v-for="field in getStructuredFields(item)"
                    :key="field.key"
                    class="structured-form__row"
                  >
                    <div class="structured-form__label">
                      {{ field.label }}
                      <span v-if="field.required" class="structured-form__required">*</span>
                    </div>
                    <el-switch
                      v-if="getFieldComponent(field) === 'switch'"
                      v-model="formValues[item.key][index][field.key]"
                    />
                    <el-input-number
                      v-else-if="getFieldComponent(field) === 'number'"
                      v-model="formValues[item.key][index][field.key]"
                      :min="field.min"
                      :max="field.max"
                      :controls="false"
                      class="w-200px"
                    />
                    <el-select
                      v-else-if="getFieldComponent(field) === 'select'"
                      v-model="formValues[item.key][index][field.key]"
                      :placeholder="t('mall.system.selectPlaceholder')"
                      clearable
                      class="w-full"
                    >
                      <el-option
                        v-for="option in field.options || []"
                        :key="option.value"
                        :label="option.label"
                        :value="option.value"
                      />
                    </el-select>
                    <el-input
                      v-else-if="getFieldComponent(field) === 'textarea'"
                      v-model="formValues[item.key][index][field.key]"
                      type="textarea"
                      :rows="field.rows || 3"
                      :placeholder="field.placeholder || t('form.pleaseInput', { msg: field.label })"
                    />
                    <el-input
                      v-else
                      v-model="formValues[item.key][index][field.key]"
                      :placeholder="field.placeholder || t('form.pleaseInput', { msg: field.label })"
                      :show-password="getFieldComponent(field) === 'password'"
                      :type="getFieldComponent(field) === 'password' ? 'password' : 'text'"
                      clearable
                    />
                    <div
                      v-if="collectionErrors[item.key]?.[`${index}-${field.key}`]"
                      class="structured-form__error"
                    >
                      {{ collectionErrors[item.key][`${index}-${field.key}`] }}
                    </div>
                  </div>
                </div>
              </div>
              <div class="collection-form__footer">
                <el-button
                  v-if="canAddCollection(item)"
                  :icon="Plus"
                  text
                  type="primary"
                  @click="addCollectionRow(item)"
                >
                  {{ getCollectionAddLabel(item) }}
                </el-button>
                <span v-else class="collection-limit-tip">{{ t('mall.config.maxReached') }}</span>
              </div>
            </div>

            <div v-else-if="isTagList(item)" class="structured-form">
              <el-select
                v-model="formValues[item.key]"
                multiple
                filterable
                allow-create
                default-first-option
                :placeholder="getTagPlaceholder(item)"
                class="w-full"
              >
                <el-option
                  v-for="option in getTagOptions(item)"
                  :key="option.value"
                  :label="option.label"
                  :value="option.value"
                />
              </el-select>
              <div v-if="tagErrors[item.key]" class="json-error">
                {{ tagErrors[item.key] }}
              </div>
            </div>

            <el-input
              v-else-if="item.type === 'json'"
              v-model="formValues[item.key]"
              type="textarea"
              :rows="6"
              :placeholder="t('mall.system.inputJson')"
            />

            <el-input
              v-else-if="item.type === 'text'"
              v-model="formValues[item.key]"
              type="textarea"
              :rows="4"
              :placeholder="t('mall.system.inputContent')"
            />

            <el-input
              v-else
              v-model="formValues[item.key]"
              :show-password="!!item.is_sensitive"
              :type="item.is_sensitive ? 'password' : 'text'"
              :placeholder="t('mall.system.inputConfigValue')"
              clearable
            />
            <div v-if="jsonErrors[item.key]" class="json-error">
              {{ jsonErrors[item.key] }}
            </div>
            </template>
            </div>
            <div class="setting-card__footer">
              <div class="setting-card__key">{{ item.key }}</div>
              <div class="setting-card__actions">
                <el-button text type="primary" @click="handleReset(item)">{{ t('mall.config.resetDefault') }}</el-button>
                <el-button
                  type="primary"
                  :loading="savingKey === item.key"
                  @click="handleSave(item)"
                >
                  {{ t('mall.system.saveAction') }}
                </el-button>
              </div>
            </div>
          </el-card>
        </div>
      </div>
      </section>
    </div>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogSetting?.label"
      width="520px"
      class="setting-dialog"
      @close="closeDialog"
    >
      <template v-if="dialogSetting">
      <el-alert
        v-if="dialogSetting.description"
        :title="dialogSetting.description"
        type="info"
        :closable="false"
        class="mb-3"
      />
      <div class="dialog-form">
        <div class="setting-card__body">
          <div v-if="isStructuredForm(dialogSetting)" class="structured-form">
            <div
              v-for="field in getStructuredFields(dialogSetting)"
              :key="field.key"
              class="structured-form__row"
            >
              <div class="structured-form__label">
                {{ field.label }}
                <span v-if="field.required" class="structured-form__required">*</span>
              </div>
              <el-switch
                v-if="getFieldComponent(field) === 'switch'"
                v-model="formValues[dialogSetting.key][field.key]"
              />
              <el-input-number
                v-else-if="getFieldComponent(field) === 'number'"
                v-model="formValues[dialogSetting.key][field.key]"
                :min="field.min"
                :max="field.max"
                :controls="false"
                class="w-200px"
              />
              <el-select
                v-else-if="getFieldComponent(field) === 'select'"
                v-model="formValues[dialogSetting.key][field.key]"
                :placeholder="t('mall.system.selectPlaceholder')"
                clearable
                class="w-full"
              >
                <el-option
                  v-for="option in field.options || []"
                  :key="option.value"
                  :label="option.label"
                  :value="option.value"
                />
              </el-select>
              <el-input
                v-else-if="getFieldComponent(field) === 'textarea'"
                v-model="formValues[dialogSetting.key][field.key]"
                type="textarea"
                :rows="field.rows || 3"
                :placeholder="field.placeholder || t('form.pleaseInput', { msg: field.label })"
              />
              <el-input
                v-else
                v-model="formValues[dialogSetting.key][field.key]"
                :placeholder="field.placeholder || t('form.pleaseInput', { msg: field.label })"
                :show-password="getFieldComponent(field) === 'password'"
                :type="getFieldComponent(field) === 'password' ? 'password' : 'text'"
                clearable
              />
              <div
                v-if="structuredErrors[dialogSetting.key]?.[field.key]"
                class="structured-form__error"
              >
                {{ structuredErrors[dialogSetting.key][field.key] }}
              </div>
            </div>
          </div>

          <div
            v-else-if="isCollectionForm(dialogSetting)"
            class="collection-form"
          >
            <div
              v-for="(entry, index) in formValues[dialogSetting.key]"
              :key="index"
              class="collection-form__entry"
            >
              <div class="collection-form__header">
                <div>{{ t('mall.system.configN', { n: index + 1 }) }}</div>
                <el-button
                  text
                  type="danger"
                  :disabled="!canRemoveCollection(dialogSetting)"
                  @click="removeCollectionRow(dialogSetting, index)"
                >
                  {{ t('mall.system.deleteAction') }}
                </el-button>
              </div>
              <div class="collection-form__fields">
                <div
                  v-for="field in getStructuredFields(dialogSetting)"
                  :key="field.key"
                  class="structured-form__row"
                >
                  <div class="structured-form__label">
                    {{ field.label }}
                    <span v-if="field.required" class="structured-form__required">*</span>
                  </div>
                  <el-switch
                    v-if="getFieldComponent(field) === 'switch'"
                    v-model="formValues[dialogSetting.key][index][field.key]"
                  />
                  <el-input-number
                    v-else-if="getFieldComponent(field) === 'number'"
                    v-model="formValues[dialogSetting.key][index][field.key]"
                    :min="field.min"
                    :max="field.max"
                    :controls="false"
                    class="w-200px"
                  />
                  <el-select
                    v-else-if="getFieldComponent(field) === 'select'"
                    v-model="formValues[dialogSetting.key][index][field.key]"
                    :placeholder="t('mall.system.selectPlaceholder')"
                    clearable
                    class="w-full"
                  >
                    <el-option
                      v-for="option in field.options || []"
                      :key="option.value"
                      :label="option.label"
                      :value="option.value"
                    />
                  </el-select>
                  <el-input
                    v-else-if="getFieldComponent(field) === 'textarea'"
                    v-model="formValues[dialogSetting.key][index][field.key]"
                    type="textarea"
                    :rows="field.rows || 3"
                    :placeholder="field.placeholder || t('form.pleaseInput', { msg: field.label })"
                  />
                  <el-input
                    v-else
                    v-model="formValues[dialogSetting.key][index][field.key]"
                    :placeholder="field.placeholder || t('form.pleaseInput', { msg: field.label })"
                    :show-password="getFieldComponent(field) === 'password'"
                    :type="getFieldComponent(field) === 'password' ? 'password' : 'text'"
                    clearable
                  />
                  <div
                    v-if="collectionErrors[dialogSetting.key]?.[`${index}-${field.key}`]"
                    class="structured-form__error"
                  >
                    {{ collectionErrors[dialogSetting.key][`${index}-${field.key}`] }}
                  </div>
                </div>
              </div>
            </div>
            <div class="collection-form__footer">
              <el-button
                v-if="canAddCollection(dialogSetting)"
                :icon="Plus"
                text
                type="primary"
                @click="addCollectionRow(dialogSetting)"
              >
                {{ getCollectionAddLabel(dialogSetting) }}
              </el-button>
              <span v-else class="collection-limit-tip">{{ t('mall.config.maxReached') }}</span>
            </div>
          </div>

          <el-input
            v-else-if="dialogSetting.type === 'text'"
            v-model="formValues[dialogSetting.key]"
            type="textarea"
            :rows="5"
            :placeholder="t('mall.system.inputContent')"
          />

          <el-input
            v-else-if="dialogSetting.type === 'json'"
            v-model="formValues[dialogSetting.key]"
            type="textarea"
            :rows="6"
            :placeholder="t('mall.system.inputJson')"
          />

          <el-input
            v-else
            v-model="formValues[dialogSetting.key]"
            :type="dialogSetting.is_sensitive ? 'password' : 'text'"
            :show-password="!!dialogSetting.is_sensitive"
            :placeholder="t('mall.system.inputConfigValue')"
            clearable
          />
          <div v-if="jsonErrors[dialogSetting.key]" class="json-error">
            {{ jsonErrors[dialogSetting.key] }}
          </div>
        </div>
      </div>
      </template>
      <template #footer>
      <el-button @click="closeDialog">{{ t('mall.system.cancelAction') }}</el-button>
      <el-button
        type="primary"
        :disabled="!dialogSetting"
        :loading="dialogSetting && savingKey === dialogSetting.key"
        @click="handleDialogSave"
      >
        {{ t('mall.system.saveAction') }}
      </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue'
import { computed, reactive, ref, watch, onMounted, onActivated } from 'vue'
import { ElMessage } from 'element-plus'
import {
  Collection,
  Document,
  EditPen,
  Money,
  Plus,
  RefreshRight,
  Setting,
  SwitchButton,
  Tickets,
} from '@element-plus/icons-vue'
import type { SystemSettingItem } from '@/modules/system/api/setting'
import { systemSettingApi } from '@/modules/system/api/setting'
import MaUploadImage from '@/components/ma-upload-image/index.vue'
import { useI18n } from 'vue-i18n'

interface Props {
  groupKey: string
  title?: string
  description?: string
}

interface StructuredField {
  key: string
  label: string
  placeholder?: string
  input_type?: string
  component?: string
  options?: Array<{ label: string; value: any }>
  required?: boolean
  rows?: number
  min?: number
  max?: number
  default?: any
}

interface CollectionMeta {
  minItems: number
  maxItems: number
  addLabel: string
}

interface TagOption {
  label: string
  value: string
}

const props = defineProps<Props>()

const { t } = useI18n()
const loading = ref(false)
const settings = ref<SystemSettingItem[]>([])
const formValues = reactive<Record<string, any>>({})
const jsonErrors = reactive<Record<string, string>>({})
const structuredErrors = reactive<Record<string, Record<string, string>>>({})
const collectionErrors = reactive<Record<string, Record<string, string>>>({})
const tagErrors = reactive<Record<string, string>>({})
const savingKey = ref('')
const internalTitle = ref('')
const internalDescription = ref('')

const computedTitle = computed(() => props.title ?? internalTitle.value ?? props.groupKey)
const computedDescription = computed(() => props.description ?? internalDescription.value ?? '')
const dialogTriggerMap = computed<Record<string, SystemSettingItem[]>>(() => {
  const map: Record<string, SystemSettingItem[]> = {}
  settings.value.forEach((item) => {
    if (item.meta?.display === 'dialog' && typeof item.meta?.trigger_key === 'string') {
      const key = item.meta.trigger_key as string
      if (!map[key]) {
        map[key] = []
      }
      map[key].push(item)
    }
  })
  return map
})
const displaySettings = computed(() => {
  const items = settings.value.filter(item => item.meta?.display !== 'dialog' || !item.meta?.trigger_key)
  return items.sort((a, b) => Number(isWideCard(a)) - Number(isWideCard(b)))
})
const dialogSetting = ref<SystemSettingItem | null>(null)
const dialogVisible = ref(false)

const typeIconMap: Record<string, Component> = {
  string: EditPen,
  text: Document,
  integer: Tickets,
  decimal: Money,
  boolean: SwitchButton,
  json: Collection,
}

const typeTagMap: Record<string, 'info' | 'success' | 'warning' | 'danger' | 'primary'> = {
  boolean: 'success',
  integer: 'warning',
  decimal: 'warning',
  json: 'primary',
  text: 'info',
  string: 'info',
}

const resolveIcon = (item: SystemSettingItem) => typeIconMap[item.type] ?? Setting
const resolveTypeLabel = (type: string) => {
  const map: Record<string, string> = {
    string: t('mall.system.typeString'),
    text: t('mall.system.typeText'),
    integer: t('mall.system.typeInteger'),
    decimal: t('mall.system.typeDecimal'),
    boolean: t('mall.system.typeBoolean'),
    json: t('mall.system.typeJson'),
  }
  return map[type] ?? t('mall.system.configItem')
}
const resolveTagType = (type: string) => typeTagMap[type] ?? 'info'

const fetchMeta = async () => {
  if (props.title && props.description) {
    internalTitle.value = props.title
    internalDescription.value = props.description
    return
  }
  try {
    const res = await systemSettingApi.groups()
    const normalized = Array.isArray(res.data) ? res.data : []
    const match = normalized.find(item => item.key === props.groupKey)
    if (match) {
      internalTitle.value = match.label
      internalDescription.value = match.description ?? ''
    }
  }
  catch (error) {
    console.error(t('mall.system.loadGroupFailed'), error)
  }
}

const loadSettings = async (options: { skipLoading?: boolean } = {}) => {
  if (!props.groupKey) return
  if (!options.skipLoading) {
    loading.value = true
  }
  try {
    const res = await systemSettingApi.groupSettings(props.groupKey)
    const normalized = Array.isArray(res.data) ? res.data : []
    settings.value = normalized
    initializeForm(normalized)
  }
  catch (error: any) {
    ElMessage.error(error?.message || t('mall.system.loadSettingsFailed'))
  }
  finally {
    if (!options.skipLoading) {
      loading.value = false
    }
  }
}

const initializeForm = (items: SystemSettingItem[]) => {
  items.forEach((item) => {
    formValues[item.key] = formatIncomingValue(item)
    resetItemState(item.key)
  })
}

const resetItemState = (key: string) => {
  delete jsonErrors[key]
  delete tagErrors[key]
  delete structuredErrors[key]
  delete collectionErrors[key]
}

const formatIncomingValue = (item: SystemSettingItem) => {
  const fallback = item.value ?? item.default ?? null
  switch (item.type) {
    case 'boolean':
      return Boolean(fallback)
    case 'integer':
      return fallback === null ? 0 : Number(fallback)
    case 'decimal':
      return fallback === null ? 0 : Number(fallback)
    case 'json':
      if (isStructuredForm(item)) {
        return buildStructuredValue(item, fallback)
      }
      if (isCollectionForm(item)) {
        return buildCollectionValue(item, fallback)
      }
      if (isTagList(item)) {
        return buildTagListValue(item, fallback)
      }
      try {
        return JSON.stringify(fallback ?? {}, null, 2)
      }
      catch {
        return typeof fallback === 'string' ? fallback : ''
      }
    default:
      return fallback ?? ''
  }
}

const isSelect = (item: SystemSettingItem) => {
  return item.meta?.component === 'select' && Array.isArray(item.meta?.options)
}

const isUpload = (item: SystemSettingItem) => {
  return item.meta?.component === 'upload'
}

const isWideCard = (item: SystemSettingItem) => {
  if (Array.isArray(item.meta?.display_span)) {
    if (item.meta.display_span.includes('full')) {
      return true
    }
    if (item.meta.display_span.includes('wide')) {
      return true
    }
    if (item.meta.display_span.includes('compact')) {
      return false
    }
  }
  if (isStructuredForm(item) || isCollectionForm(item) || isTagList(item)) {
    return true
  }
  if (item.type === 'json' || item.type === 'text') {
    return true
  }
  if (isUpload(item)) {
    return false
  }
  return false
}

const cardSizeClass = (item: SystemSettingItem) => {
  if (Array.isArray(item.meta?.display_span) && item.meta.display_span.includes('full')) {
    return 'setting-grid__item--full'
  }
  return isWideCard(item) ? 'setting-grid__item--wide' : 'setting-grid__item--compact'
}

const isStandaloneDialog = (item: SystemSettingItem) => {
  return item.meta?.display === 'dialog' && !item.meta?.trigger_key
}

const getDialogTargets = (key: string) => {
  return dialogTriggerMap.value[key] ?? []
}

const hasDialogAction = (key: string) => getDialogTargets(key).length > 0

const dialogButtonLabel = (dialogItem: SystemSettingItem) => {
  if (typeof dialogItem.meta?.button_label === 'string') {
    return dialogItem.meta.button_label
  }
  return t('mall.system.configLabel')
}

const isDialogTriggerActive = (triggerKey: string, dialogItem: SystemSettingItem) => {
  const triggerMeta = dialogItem.meta ?? {}
  const currentValue = formValues[triggerKey]
  if (Array.isArray(triggerMeta.trigger_values)) {
    return triggerMeta.trigger_values.includes(currentValue)
  }
  if (triggerMeta.trigger_value !== undefined) {
    return currentValue === triggerMeta.trigger_value
  }
  if (triggerMeta.trigger_not_value !== undefined) {
    return currentValue !== triggerMeta.trigger_not_value
  }
  return Boolean(currentValue)
}

const openDialog = (item: SystemSettingItem) => {
  dialogSetting.value = item
  dialogVisible.value = true
}

const closeDialog = () => {
  dialogVisible.value = false
  dialogSetting.value = null
}

const handleDialogSave = async () => {
  if (!dialogSetting.value) {
    return
  }
  await handleSave(dialogSetting.value)
  if (!jsonErrors[dialogSetting.value.key]) {
    closeDialog()
  }
}

const normalizeValue = (item: SystemSettingItem) => {
  const currentValue = formValues[item.key]
  switch (item.type) {
    case 'boolean':
      return Boolean(currentValue)
    case 'integer':
      return currentValue === '' ? 0 : Number(currentValue)
    case 'decimal':
      return currentValue === '' ? 0 : Number(currentValue)
    case 'json':
      if (isStructuredForm(item)) {
        return collectStructuredPayload(item)
      }
      if (isCollectionForm(item)) {
        return collectCollectionPayload(item)
      }
      if (isTagList(item)) {
        return collectTagPayload(item)
      }
      try {
        delete jsonErrors[item.key]
        if (typeof currentValue === 'string') {
          return currentValue.trim() ? JSON.parse(currentValue) : {}
        }
        return currentValue ?? {}
      }
      catch (error: any) {
        jsonErrors[item.key] = error?.message || 'JSON parse error'
        throw error
      }
    default:
      return currentValue
  }
}

const handleSave = async (item: SystemSettingItem) => {
  try {
    const value = normalizeValue(item)
    savingKey.value = item.key
    await systemSettingApi.update(item.key, value)
    item.value = value
    ElMessage.success(t('mall.system.configUpdated'))
  }
  catch (error: any) {
    if (!jsonErrors[item.key]) {
      ElMessage.error(error?.message || t('mall.system.saveFailed'))
    }
  }
  finally {
    savingKey.value = ''
  }
}

const handleReset = (item: SystemSettingItem) => {
  const defaultValue = item.default ?? null
  switch (item.type) {
    case 'boolean':
      formValues[item.key] = Boolean(defaultValue)
      break
    case 'integer':
    case 'decimal':
      formValues[item.key] = defaultValue === null ? 0 : Number(defaultValue)
      break
    case 'json':
      if (isStructuredForm(item)) {
        formValues[item.key] = buildStructuredValue(item, defaultValue)
        resetItemState(item.key)
        break
      }
      if (isCollectionForm(item)) {
        formValues[item.key] = buildCollectionValue(item, defaultValue)
        resetItemState(item.key)
        break
      }
      if (isTagList(item)) {
        formValues[item.key] = buildTagListValue(item, defaultValue)
        resetItemState(item.key)
        break
      }
      try {
        formValues[item.key] = JSON.stringify(defaultValue ?? {}, null, 2)
        resetItemState(item.key)
      }
      catch {
        formValues[item.key] = defaultValue ?? ''
      }
      break
    default:
      formValues[item.key] = defaultValue ?? ''
      resetItemState(item.key)
  }
}

const handleRefresh = () => {
      loadSettings()
}

const isStructuredForm = (item: SystemSettingItem) => {
  return item.type === 'json' && item.meta?.component === 'form' && Array.isArray(item.meta?.fields)
}

const isCollectionForm = (item: SystemSettingItem) => {
  return item.type === 'json' && item.meta?.component === 'collection' && Array.isArray(item.meta?.fields)
}

const isTagList = (item: SystemSettingItem) => {
  return item.type === 'json' && item.meta?.component === 'tags'
}

const getStructuredFields = (item: SystemSettingItem): StructuredField[] => {
  if (!Array.isArray(item.meta?.fields)) return []
  return (item.meta?.fields || []) as StructuredField[]
}

const getFieldComponent = (field: StructuredField) => {
  if (field.component) return field.component
  if (field.input_type === 'password') return 'password'
  return 'input'
}

const ensureFieldErrors = (bucket: Record<string, Record<string, string>>, key: string) => {
  if (!bucket[key]) {
    bucket[key] = {}
  }
  return bucket[key]
}

const defaultFieldValue = (field: StructuredField, seed: unknown) => {
  if (seed !== undefined && seed !== null && seed !== '') {
    return seed
  }

  if (field.default !== undefined) {
    return field.default
  }

  switch (getFieldComponent(field)) {
    case 'number':
      return field.min ?? 0
    case 'switch':
      return false
    default:
      return ''
  }
}

const normalizeFieldValue = (field: StructuredField, value: unknown) => {
  switch (getFieldComponent(field)) {
    case 'number':
      if (value === '' || value === null || value === undefined) return null
      return Number(value)
    case 'switch':
      return Boolean(value)
    default:
      return value ?? ''
  }
}

const isEmptyValue = (value: unknown, field?: StructuredField) => {
  const component = field ? getFieldComponent(field) : 'input'
  if (component === 'switch') {
    return false
  }

  if (component === 'number') {
    return value === '' || value === null || value === undefined
  }

  return value === '' || value === null || value === undefined
}

const isPlainObject = (value: unknown): value is Record<string, any> => {
  return Object.prototype.toString.call(value) === '[object Object]'
}

const buildStructuredValue = (item: SystemSettingItem, fallback: unknown) => {
  const base = isPlainObject(fallback) ? fallback : {}
  const result: Record<string, any> = {}
  getStructuredFields(item).forEach((field) => {
    result[field.key] = defaultFieldValue(field, base[field.key])
  })
  return result
}

const collectStructuredPayload = (item: SystemSettingItem, validate = true) => {
  const currentValue = isPlainObject(formValues[item.key]) ? formValues[item.key] : {}
  const payload: Record<string, any> = {}
  const errorBag = ensureFieldErrors(structuredErrors, item.key)
  let hasError = false

  getStructuredFields(item).forEach((field) => {
    const value = currentValue[field.key]
    if (validate && field.required && isEmptyValue(value, field)) {
      errorBag[field.key] = `${field.label} ${t('mall.system.fieldRequired')}`
      hasError = true
    }
    else {
      delete errorBag[field.key]
    }

    payload[field.key] = normalizeFieldValue(field, value)
  })

  if (validate && hasError) {
    jsonErrors[item.key] = t('mall.system.requiredFields')
    throw new Error(jsonErrors[item.key])
  }

  delete jsonErrors[item.key]
  if (Object.keys(errorBag).length === 0) {
    delete structuredErrors[item.key]
  }

  return payload
}

const getCollectionMeta = (item: SystemSettingItem): CollectionMeta => {
  const meta = item.meta || {}
  const minItems = Number.isFinite(meta.min_items) ? Number(meta.min_items) : 0
  const maxItems = Number.isFinite(meta.max_items) ? Number(meta.max_items) : Infinity
  const addLabel = typeof meta.add_label === 'string' ? meta.add_label : t('mall.system.addRow')
  return {
    minItems: Math.max(0, minItems),
    maxItems: maxItems <= 0 ? Infinity : maxItems,
    addLabel,
  }
}

const buildCollectionValue = (item: SystemSettingItem, fallback: unknown) => {
  const base = Array.isArray(fallback) ? fallback : Array.isArray(item.default) ? item.default : []
  const normalized = base
    .map((row) => createCollectionRow(item, row))
    .filter((row) => row)

  if (normalized.length) {
    return normalized
  }

  const meta = getCollectionMeta(item)
  const count = meta.minItems > 0 ? meta.minItems : 1
  return Array.from({ length: count }).map(() => createCollectionRow(item))
}

const createCollectionRow = (item: SystemSettingItem, seed?: unknown) => {
  const source = isPlainObject(seed) ? seed : {}
  const row: Record<string, any> = {}
  getStructuredFields(item).forEach((field) => {
    row[field.key] = defaultFieldValue(field, source[field.key])
  })
  return row
}

const addCollectionRow = (item: SystemSettingItem) => {
  if (!Array.isArray(formValues[item.key])) {
    formValues[item.key] = []
  }
  if (!canAddCollection(item)) return
  formValues[item.key].push(createCollectionRow(item))
}

const removeCollectionRow = (item: SystemSettingItem, index: number) => {
  if (!canRemoveCollection(item)) return
  formValues[item.key].splice(index, 1)
  cleanupCollectionErrors(item.key, index)
}

const canAddCollection = (item: SystemSettingItem) => {
  const meta = getCollectionMeta(item)
  if (!Array.isArray(formValues[item.key])) return true
  return formValues[item.key].length < meta.maxItems
}

const canRemoveCollection = (item: SystemSettingItem) => {
  const meta = getCollectionMeta(item)
  if (!Array.isArray(formValues[item.key])) return false
  return formValues[item.key].length > Math.max(meta.minItems, 0)
}

const getCollectionAddLabel = (item: SystemSettingItem) => {
  return getCollectionMeta(item).addLabel
}

const collectCollectionPayload = (item: SystemSettingItem, validate = true) => {
  const rows = Array.isArray(formValues[item.key]) ? formValues[item.key] : []
  const meta = getCollectionMeta(item)
  const errorBag = ensureFieldErrors(collectionErrors, item.key)
  Object.keys(errorBag).forEach((fieldKey) => delete errorBag[fieldKey])

  if (validate && rows.length < meta.minItems) {
    jsonErrors[item.key] = t('mall.system.minRows', { n: meta.minItems })
    throw new Error(jsonErrors[item.key])
  }

  let hasError = false
  const payload = rows.map((row, rowIndex) => {
    const normalizedRow: Record<string, any> = {}
    getStructuredFields(item).forEach((field) => {
      const value = row?.[field.key]
      if (validate && field.required && isEmptyValue(value, field)) {
        errorBag[`${rowIndex}-${field.key}`] = `${field.label} ${t('mall.system.fieldRequired')}`
        hasError = true
      }
      else {
        delete errorBag[`${rowIndex}-${field.key}`]
      }
      normalizedRow[field.key] = normalizeFieldValue(field, value)
    })
    return normalizedRow
  })

  if (validate && hasError) {
    jsonErrors[item.key] = t('mall.system.requiredFields')
    throw new Error(jsonErrors[item.key])
  }

  delete jsonErrors[item.key]
  if (Object.keys(errorBag).length === 0) {
    delete collectionErrors[item.key]
  }

  return payload
}

const cleanupCollectionErrors = (itemKey: string, removedIndex: number) => {
  const errorBag = collectionErrors[itemKey]
  if (!errorBag) return
  const next: Record<string, string> = {}
  Object.entries(errorBag).forEach(([key, message]) => {
    const [indexStr, ...rest] = key.split('-')
    const index = Number(indexStr)
    if (Number.isNaN(index)) return
    if (index < removedIndex) {
      next[key] = message
    }
    else if (index > removedIndex) {
      next[`${index - 1}-${rest.join('-')}`] = message
    }
  })
  if (Object.keys(next).length) {
    collectionErrors[itemKey] = next
  }
  else {
    delete collectionErrors[itemKey]
  }
}

const buildTagListValue = (item: SystemSettingItem, fallback: unknown) => {
  if (Array.isArray(fallback)) {
    return fallback.map(value => String(value))
  }
  if (Array.isArray(item.default)) {
    return item.default.map(value => String(value))
  }
  return []
}

const getTagOptions = (item: SystemSettingItem): TagOption[] => {
  const options = item.meta?.options
  if (!Array.isArray(options)) return []
  return options.map((option: any) => {
    if (typeof option === 'string') {
      return { label: option, value: option }
    }
    return {
      label: option.label ?? option.value ?? '',
      value: option.value ?? option.label ?? '',
    }
  })
}

const getTagPlaceholder = (item: SystemSettingItem) => {
  return typeof item.meta?.placeholder === 'string' ? item.meta.placeholder : t('mall.system.inputHintEnter')
}

const collectTagPayload = (item: SystemSettingItem, validate = true) => {
  const values = Array.isArray(formValues[item.key]) ? formValues[item.key] : []
  const normalized = values
    .map((value) => String(value).trim())
    .filter((value) => value.length)

  const minItems = Number.isFinite(item.meta?.min_items) ? Number(item.meta.min_items) : 0
  if (validate && normalized.length < minItems) {
    tagErrors[item.key] = t('mall.system.minTags', { n: minItems })
    jsonErrors[item.key] = tagErrors[item.key]
    throw new Error(tagErrors[item.key])
  }

  delete tagErrors[item.key]
  delete jsonErrors[item.key]
  return normalized
}

const resetState = () => {
  settings.value = []
  Object.keys(formValues).forEach((k) => delete formValues[k])
  Object.keys(jsonErrors).forEach((k) => delete jsonErrors[k])
  Object.keys(structuredErrors).forEach((k) => delete structuredErrors[k])
  Object.keys(collectionErrors).forEach((k) => delete collectionErrors[k])
  Object.keys(tagErrors).forEach((k) => delete tagErrors[k])
}

let bootstrapToken = 0
const hasBootstrapped = ref(false)

interface BootstrapOptions {
  preserveData?: boolean
}

const bootstrap = async (options: BootstrapOptions = {}) => {
  if (!props.groupKey) return
  const token = ++bootstrapToken
  loading.value = true
  if (!options.preserveData) {
    resetState()
  }
  await fetchMeta()
  await loadSettings({ skipLoading: true })
  if (token === bootstrapToken) {
    loading.value = false
    hasBootstrapped.value = true
  }
}

watch(
  () => props.groupKey,
  async (key, prev) => {
    if (!key || key === prev) {
      return
    }
    await bootstrap()
  },
)

onMounted(async () => {
  await bootstrap()
})

onActivated(async () => {
  if (hasBootstrapped.value) {
    await bootstrap({ preserveData: true })
  }
})
</script>

<style scoped lang="scss">
.setting-group-root {
  width: 100%;
  --setting-surface: var(--el-bg-color);
  --setting-surface-muted: color-mix(in srgb, var(--el-bg-color) 96%, transparent);
  --setting-card-bg: color-mix(in srgb, var(--el-bg-color) 98%, transparent);
  --setting-card-border: color-mix(in srgb, var(--el-border-color) 70%, transparent);
  --setting-card-icon-bg: color-mix(in srgb, var(--el-color-primary) 15%, var(--setting-card-bg));
  --setting-card-icon-color: var(--el-color-primary);
  --setting-collection-bg: color-mix(in srgb, var(--el-color-primary) 6%, var(--el-bg-color));
  --setting-collection-border: color-mix(in srgb, var(--el-border-color) 60%, transparent);
  --setting-hero-overlay-1: color-mix(in srgb, rgb(var(--ui-primary)) 8%, transparent);
  --setting-hero-overlay-2: color-mix(in srgb, rgb(var(--ui-primary)) 4%, transparent);
}

:global(html.dark) {
  .setting-group-root {
    --setting-surface: var(--el-bg-color-overlay);
    --setting-surface-muted: color-mix(in srgb, var(--el-bg-color-overlay) 88%, transparent);
    --setting-card-bg: color-mix(in srgb, var(--el-bg-color-overlay) 92%, rgba(0, 0, 0, 0.1));
    --setting-card-border: rgba(255, 255, 255, 0.08);
    --setting-card-icon-bg: color-mix(in srgb, var(--el-color-primary) 30%, var(--setting-card-bg));
    --setting-card-icon-color: color-mix(in srgb, var(--el-color-white) 80%, var(--el-color-primary) 20%);
    --setting-collection-bg: color-mix(in srgb, var(--el-bg-color-overlay) 80%, rgba(255, 255, 255, 0.05));
    --setting-collection-border: rgba(255, 255, 255, 0.12);
    --setting-hero-overlay-1: color-mix(in srgb, rgb(var(--ui-primary)) 16%, transparent);
    --setting-hero-overlay-2: color-mix(in srgb, rgb(var(--ui-primary)) 8%, transparent);
  }
}

.setting-group-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(
    180deg,
    var(--setting-hero-overlay-1) 0%,
    var(--setting-hero-overlay-2) 55%,
    var(--setting-surface) 100%
  );
  transition: background 0.3s ease;
}

.group-hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 28px;
  border-radius: 18px;
  background: linear-gradient(
    135deg,
    rgb(var(--ui-primary) / 1) 0%,
    rgb(var(--ui-primary) / 0.85) 65%
  );
  color: var(--el-color-white);
  box-shadow: 0 12px 26px rgba(20, 52, 109, 0.25);

  .hero-text {
    max-width: 65%;
  }

  .hero-label {
    font-size: 12px;
    letter-spacing: 0.1em;
    opacity: 0.8;
    text-transform: uppercase;
  }

  .hero-title {
    font-size: 24px;
    font-weight: 600;
    margin: 4px 0;
  }

  .hero-desc {
    font-size: 13px;
    opacity: 0.9;
  }

  .hero-actions {
    display: flex;
    gap: 10px;
  }
}

.group-body {
  background: var(--setting-surface-muted);
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
}

.skeleton-wrap {
  padding: 30px 0;
}

.setting-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 14px;
  grid-auto-flow: dense;
}

.setting-grid__item {
  align-self: flex-start;
}

.setting-grid__item--full {
  grid-column: 1 / -1;
}

.setting-grid__item--wide {
  grid-column: span 2;
}

.setting-grid__item--compact {
  grid-column: span 1;
}

@media (max-width: 1024px) {
  .setting-grid__item--wide {
    grid-column: span 1;
  }

  .setting-grid__item--full {
    grid-column: span 1;
  }
}

.setting-card {
  border-radius: 16px;
  border: 1px solid var(--setting-card-border);
  background: var(--setting-card-bg);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  overflow: hidden;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
  }

  &__header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
  }

  &__meta {
    display: flex;
    gap: 14px;
    align-items: flex-start;
  }

  &__icon {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--setting-card-icon-bg);
    color: var(--setting-card-icon-color);
    font-size: 18px;
  }

  &__title {
    font-size: 16px;
    font-weight: 600;
  }

  &__desc {
    color: var(--el-text-color-secondary);
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.5;
  }

  &__tags {
    display: flex;
    gap: 8px;
    align-items: flex-start;
  }

  &__body {
    width: 100%;
  }

  &__footer {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px dashed var(--el-border-color-lighter);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
  }

  &__key {
    font-size: 12px;
    color: var(--el-text-color-secondary);
  }

  &__actions {
    display: flex;
    gap: 8px;
    align-items: center;
    white-space: nowrap;
  }
}

:global(html.dark) {
  .group-body {
    background: color-mix(in srgb, var(--setting-surface-muted) 85%, rgba(0, 0, 0, 0.2));
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.55);
  }

  .setting-card {
    backdrop-filter: blur(12px);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.65);
  }

  .setting-card__footer {
    border-color: rgba(255, 255, 255, 0.08);
  }
}

.json-error {
  margin-top: 6px;
  color: var(--el-color-error);
  font-size: 13px;
}

.structured-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.structured-form--inline {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 6px 8px;
  width: 100%;

  .structured-form__row {
    min-width: 0;
  }
}

.structured-form__row {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.structured-form__label {
  font-size: 13px;
  color: var(--el-text-color-regular);
}

.structured-form__required {
  margin-left: 4px;
  color: var(--el-color-danger);
}

.structured-form__error {
  font-size: 12px;
  color: var(--el-color-error);
  margin-top: 2px;
}

.collection-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.collection-form__entry {
  border: 1px dashed var(--setting-collection-border);
  border-radius: 10px;
  padding: 12px;
  background: var(--setting-collection-bg);
}

.collection-form__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-weight: 500;
}

.collection-form__fields {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.collection-form__footer {
  margin-top: 4px;
}

.collection-limit-tip {
  font-size: 13px;
  color: var(--el-text-color-placeholder);
}

.control-inline {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dialog-actions {
  display: flex;
  gap: 8px;
}

.setting-dialog :deep(.el-dialog__body) {
  padding-top: 10px;
}

.dialog-form {
  padding: 10px 0;
}

.dialog-card {
  display: flex;
  flex-direction: column;
  gap: 10px;

  &__desc {
    font-size: 13px;
    color: var(--el-text-color-regular);
  }
}
</style>
