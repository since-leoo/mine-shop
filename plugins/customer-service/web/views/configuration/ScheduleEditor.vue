<script setup lang="ts">
import type { Schedule } from '../../api/customer-service'
import { computed, ref } from 'vue'
const props = defineProps<{ modelValue?: Schedule }>()
const emit = defineEmits<{ 'update:modelValue': [Schedule] }>()
const days = ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
const exceptionDate = ref('')
const enabled = computed(() => Object.keys(props.modelValue || {}).length > 0)
function change(key: string, slots: string[][], exception = false) {
  const current = props.modelValue || {}
  const field = exception ? 'exceptions' : 'weekly'
  emit('update:modelValue', { ...current, timezone: current.timezone || 'Asia/Shanghai', [field]: { ...current[field], [key]: slots } })
}
function toggle(value: unknown) { emit('update:modelValue', value ? { timezone: 'Asia/Shanghai', weekly: Object.fromEntries(days.map((_, i) => [String(i + 1), [['09:00', '18:00']]])) } : {}) }
function removeException(key: string) {
  const exceptions = { ...props.modelValue?.exceptions }; delete exceptions[key]
  emit('update:modelValue', { ...props.modelValue, exceptions })
}
</script>
<template>
  <div class="schedule-editor">
    <el-switch :model-value="enabled" active-text="按营业时段" inactive-text="全天" @change="toggle" />
    <template v-if="enabled">
      <el-input :model-value="modelValue?.timezone" placeholder="时区，例如 Asia/Shanghai" @update:model-value="emit('update:modelValue', { ...modelValue, timezone: $event })" />
      <div v-for="(day, index) in days" :key="day" class="schedule-row">
        <span>{{ day }}</span><div><div v-for="(slot, i) in modelValue?.weekly?.[String(index + 1)] || []" :key="i" class="time-slot"><el-time-select :model-value="slot[0]" start="00:00" end="23:59" step="00:15" @update:model-value="change(String(index + 1), (modelValue?.weekly?.[String(index + 1)] || []).map((s, j) => j === i ? [$event || '', s[1]] : s))" /><span>至</span><el-time-select :model-value="slot[1]" start="00:00" end="23:59" step="00:15" @update:model-value="change(String(index + 1), (modelValue?.weekly?.[String(index + 1)] || []).map((s, j) => j === i ? [s[0], $event || ''] : s))" /><el-button text type="danger" @click="change(String(index + 1), (modelValue?.weekly?.[String(index + 1)] || []).filter((_, j) => j !== i))">删除</el-button></div><el-button text @click="change(String(index + 1), [...modelValue?.weekly?.[String(index + 1)] || [], ['09:00', '18:00']])">添加时段</el-button></div>
      </div>
      <p>没有时段的日期为休息日。跨日营业请拆分到两天。</p>
      <div class="time-slot"><el-date-picker v-model="exceptionDate" type="date" value-format="YYYY-MM-DD" placeholder="例外休息日期" /><el-button :disabled="!exceptionDate" @click="change(exceptionDate, [], true)">添加休息日</el-button></div>
      <div v-for="(slots, date) in modelValue?.exceptions" :key="date"><el-tag closable @close="removeException(String(date))">{{ date }} {{ slots.length ? slots.map(s => s.join('–')).join('、') : '休息' }}</el-tag></div>
    </template>
  </div>
</template>
<style scoped>.schedule-editor { display:grid; gap:10px; max-width:660px; }.schedule-row { display:grid; grid-template-columns:55px 1fr; align-items:start; }.time-slot { display:flex; align-items:center; gap:8px; margin-bottom:4px; }.schedule-editor p { color:var(--el-text-color-secondary); font-size:12px; }.time-slot :deep(.el-select) { width:130px; }</style>
