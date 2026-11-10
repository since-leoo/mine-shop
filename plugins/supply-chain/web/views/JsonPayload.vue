<script setup lang="ts">
import { computed } from 'vue'
import { ElMessage } from 'element-plus'
import { safeJson } from './format'

const props = defineProps<{ title: string, value?: unknown, testId?: string }>()
const text = computed(() => safeJson(props.value))
const lines = computed(() => text.value.split('\n').length)
// 使用文本节点着色，参数中的 HTML 不会被解释或执行。
const tokens = computed(() => {
  const result: Array<{ text: string, kind: string }> = []
  const pattern = /("(?:\\.|[^"\\])*")(\s*:)?|\b(true|false|null)\b|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)/g
  let cursor = 0
  for (const match of text.value.matchAll(pattern)) {
    const index = match.index!
    if (index > cursor) result.push({ text: text.value.slice(cursor, index), kind: 'plain' })
    result.push({ text: match[0], kind: match[2] ? 'key' : match[1] ? 'string' : match[3] ? 'literal' : 'number' })
    cursor = index + match[0].length
  }
  if (cursor < text.value.length) result.push({ text: text.value.slice(cursor), kind: 'plain' })
  return result
})

async function copy() {
  try {
    await navigator.clipboard.writeText(text.value)
    ElMessage.success(`${props.title}已复制`)
  }
  catch { ElMessage.warning('复制失败，请选中内容手动复制') }
}
</script>

<template>
  <section class="json-panel" :aria-label="title">
    <header class="json-header">
      <div class="json-title"><span class="json-symbol" aria-hidden="true">{ }</span><h3>{{ title }}</h3></div>
      <div class="json-actions"><span class="redacted">已脱敏</span><el-button text size="small" :aria-label="`复制${title}`" :disabled="value === undefined || value === null" @click="copy">复制</el-button></div>
    </header>
    <div v-if="value !== undefined && value !== null" class="json-scroll" tabindex="0" :aria-label="`${title}内容`">
      <div class="json-code">
        <pre class="line-numbers" aria-hidden="true">{{ Array.from({ length: lines }, (_, index) => index + 1).join('\n') }}</pre>
        <pre class="json-value" :data-testid="testId"><code><span v-for="(token, index) in tokens" :key="index" :class="`token-${token.kind}`">{{ token.text }}</span></code></pre>
      </div>
    </div>
    <div v-else class="json-empty" :data-testid="testId">暂无记录内容</div>
    <footer class="json-footer"><span>JSON</span><span>{{ value === undefined || value === null ? '无数据' : `${lines} 行` }}</span></footer>
  </section>
</template>

<style scoped>
.json-panel { display: flex; flex-direction: column; min-width: 0; overflow: hidden; border: 1px solid var(--el-border-color-lighter); border-radius: 12px; background: var(--el-bg-color); }
.json-header, .json-title, .json-actions, .json-footer { display: flex; align-items: center; }
.json-header { justify-content: space-between; gap: 8px; padding: 13px 16px; border-bottom: 1px solid var(--el-border-color-lighter); }
.json-title { min-width: 0; gap: 9px; }
.json-symbol { color: var(--el-color-primary); font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 13px; font-weight: 600; }
.json-title h3 { margin: 0; color: var(--el-text-color-primary); font-size: 14px; font-weight: 600; }
.json-actions { flex-shrink: 0; gap: 10px; }
.redacted { color: var(--el-text-color-regular); font-size: 11px; }
.json-scroll { flex: 1; min-height: 180px; max-height: 420px; overflow: auto; background: var(--el-fill-color-light); }
.json-scroll:focus-visible { outline: 2px solid var(--el-color-primary); outline-offset: -2px; }
.json-code { display: flex; align-items: stretch; min-width: 100%; width: max-content; padding: 18px 0; }
.json-code pre { margin: 0; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; line-height: 1.9; tab-size: 2; }
.line-numbers { position: sticky; left: 0; flex: 0 0 auto; min-width: 34px; padding: 0 12px; border-right: 1px solid var(--el-border-color-lighter); background: var(--el-fill-color-light); color: var(--el-text-color-secondary); text-align: right; user-select: none; }
.json-value { padding: 0 18px; color: var(--el-text-color-regular); }
.json-value code { font: inherit; }
.token-key { color: var(--el-color-primary); }
.token-string { color: var(--el-color-success-dark-2); }
.token-number, .token-literal { color: var(--el-color-warning-dark-2); }
.json-empty { display: grid; flex: 1; min-height: 180px; place-items: center; color: var(--el-text-color-secondary); font-size: 13px; background: var(--el-fill-color-light); }
.json-footer { justify-content: space-between; padding: 8px 16px; border-top: 1px solid var(--el-border-color-lighter); color: var(--el-text-color-secondary); font-size: 11px; }
@media (max-width: 540px) { .json-header { padding: 12px; } .json-actions { gap: 4px; } .json-code pre { font-size: 11px; } }
</style>
