<script setup lang="ts">
import type { ApiLog, TaskDetail } from '../api/supply-chain'
import { ref, watch } from 'vue'
import { statusLabel, statusType } from './format'
import JsonPayload from './JsonPayload.vue'

const props = defineProps<{ task?: TaskDetail, log?: ApiLog, canParams: boolean }>()
const emit = defineEmits<{ log: [id: number] }>()
const activeTab = ref('attempts')
watch(() => props.task?.id, () => { activeTab.value = 'attempts' })
</script>

<template>
  <div class="execution-details">
    <template v-if="task">
      <section class="overview" aria-label="任务概览">
        <div class="overview-heading"><div><span class="field-label">任务类型</span><strong class="task-type mono">{{ task.type }}</strong></div><el-tag :type="statusType(task.status)" effect="light" round>{{ statusLabel(task.status) }}</el-tag></div>
        <dl class="metrics">
          <div><dt>执行次数</dt><dd>{{ task.attempt_count ?? task.attempts?.length ?? 0 }}<small>次</small></dd></div>
          <div><dt>下次执行</dt><dd class="time-value">{{ task.next_retry_at || '暂无计划' }}</dd></div>
          <div><dt>创建时间</dt><dd class="time-value">{{ task.created_at || '—' }}</dd></div>
        </dl>
        <div class="trace"><span>Trace ID</span><code>{{ task.trace_id || '—' }}</code></div>
      </section>
      <div v-if="task.error_message || task.last_error" class="failure-notice" role="status"><span class="notice-mark" aria-hidden="true">!</span><div><strong>最近失败原因</strong><p>{{ task.error_message || task.last_error }}</p></div></div>
      <el-alert v-if="task.status === 'unknown'" title="上游受理结果未确认" description="请在采购订单中查询上游结果，确认后再进行后续操作。" type="warning" :closable="false" show-icon />
      <div v-if="task.next_action" class="next-action"><span>下一步</span><code>{{ task.next_action }}</code></div>

      <el-tabs v-model="activeTab" class="detail-tabs">
        <el-tab-pane name="attempts">
          <template #label><span class="tab-label">执行记录<span class="tab-count">{{ task.attempts?.length || 0 }}</span></span></template>
          <el-empty v-if="!task.attempts?.length" description="尚无执行记录，任务执行后会显示在这里" :image-size="76" />
          <ol v-else class="attempts">
            <li v-for="attempt in task.attempts" :key="attempt.id" class="attempt" :class="`attempt-${statusType(attempt.status)}`">
              <span class="attempt-number" aria-hidden="true">{{ attempt.attempt_no }}</span>
              <article class="attempt-card">
                <header class="attempt-heading"><strong>第 {{ attempt.attempt_no }} 次执行</strong><el-tag :type="statusType(attempt.status)" size="small" round>{{ statusLabel(attempt.status) }}</el-tag><span class="duration">{{ attempt.duration_ms ?? '—' }} ms</span></header>
                <div class="attempt-time"><span>{{ attempt.started_at || '开始时间未记录' }}</span><span v-if="attempt.finished_at">结束于 {{ attempt.finished_at }}</span></div>
                <div v-if="attempt.failure_stage || attempt.error_category" class="attempt-meta"><span v-if="attempt.failure_stage">失败阶段 <code>{{ attempt.failure_stage }}</code></span><span v-if="attempt.error_category">错误类别 <code>{{ attempt.error_category }}</code></span></div>
                <p v-if="attempt.error_message" class="attempt-error">{{ attempt.error_message }}</p>
                <p v-if="attempt.next_action" class="attempt-next">下一步：{{ attempt.next_action }}</p>
                <el-collapse v-if="canParams && (attempt.request != null || attempt.response != null)" class="attempt-params"><el-collapse-item title="查看本次请求与响应" :name="attempt.id"><div class="payload-grid"><JsonPayload title="请求参数" :value="attempt.request" /><JsonPayload title="响应结果" :value="attempt.response" /></div></el-collapse-item></el-collapse>
              </article>
            </li>
          </ol>
        </el-tab-pane>
        <el-tab-pane v-if="canParams" label="任务参数" name="payload" lazy><JsonPayload title="任务参数" :value="task.payload" /></el-tab-pane>
        <el-tab-pane name="logs" lazy>
          <template #label><span class="tab-label">关联接口<span class="tab-count">{{ task.logs?.length || 0 }}</span></span></template>
          <el-empty v-if="!task.logs?.length" description="暂无关联接口调用" :image-size="76" />
          <div v-else class="related-logs">
            <article v-for="item in task.logs" :key="item.id" class="related-log">
              <div class="related-log-heading"><span class="method-badge">{{ item.method || 'API' }}</span><code>{{ item.path || '—' }}</code><el-tag :type="statusType(item.status)" size="small">{{ statusLabel(item.status) }}</el-tag></div>
              <div class="related-log-meta"><span>#{{ item.id }}</span><span>HTTP {{ item.http_status ?? '—' }}</span><span>{{ item.duration_ms ?? '—' }} ms</span><el-button v-if="canParams" link type="primary" @click="emit('log', item.id)">查看参数</el-button></div>
              <p v-if="item.error_message" class="attempt-error">{{ item.error_message }}</p>
            </article>
          </div>
        </el-tab-pane>
        <el-tab-pane v-if="task.events?.length" label="操作记录" name="events" lazy><JsonPayload title="阶段与操作记录" :value="task.events" /></el-tab-pane>
      </el-tabs>
    </template>

    <template v-if="log">
      <section class="overview" aria-label="接口调用概览">
        <div class="overview-heading"><span class="field-label">接口调用</span><el-tag :type="statusType(log.status)" effect="light" round>{{ statusLabel(log.status) }}</el-tag></div>
        <div class="endpoint"><span class="method-badge">{{ log.method || 'API' }}</span><code>{{ log.path || '未记录接口路径' }}</code></div>
        <dl class="metrics log-metrics">
          <div><dt>HTTP 状态</dt><dd :class="{ 'error-code': log.http_status && log.http_status >= 400 }">{{ log.http_status ?? '—' }}</dd></div>
          <div><dt>业务码</dt><dd class="business-code">{{ log.provider_code ?? '—' }}</dd></div>
          <div><dt>请求耗时</dt><dd>{{ log.duration_ms ?? '—' }}<small>ms</small></dd></div>
          <div><dt>请求时间</dt><dd class="time-value">{{ log.created_at || '—' }}</dd></div>
        </dl>
        <div class="trace"><span>Trace ID</span><code>{{ log.trace_id || '—' }}</code></div>
      </section>
      <div v-if="log.error_message" class="failure-notice" role="status"><span class="notice-mark" aria-hidden="true">!</span><div><strong>请求失败原因</strong><p>{{ log.error_message }}</p></div></div>
      <div v-if="canParams" class="payload-grid log-payloads"><JsonPayload title="请求参数" :value="log.request ?? (log.request_query != null || log.request_body != null ? { query: log.request_query, body: log.request_body } : undefined)" test-id="request-params" /><JsonPayload title="响应结果" :value="log.response ?? log.response_body" test-id="response-params" /></div>
    </template>
  </div>
</template>

<style scoped>
.execution-details { display: grid; gap: 20px; min-width: 0; color: var(--el-text-color-primary); }
.overview { padding: 22px 24px 0; border: 1px solid var(--el-border-color-lighter); border-radius: 12px; background: var(--el-bg-color); }
.overview-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.field-label { color: var(--el-text-color-secondary); font-size: 12px; }
.task-type { display: block; margin-top: 8px; font-size: 18px; font-weight: 600; overflow-wrap: anywhere; }
.mono, code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
.metrics { display: grid; grid-template-columns: .75fr 1.2fr 1.2fr; gap: 20px; margin: 24px 0; }
.metrics > div { min-width: 0; }
.metrics dt { margin-bottom: 9px; color: var(--el-text-color-secondary); font-size: 12px; }
.metrics dd { margin: 0; font-size: 22px; font-weight: 600; line-height: 1.5; overflow-wrap: anywhere; }
.metrics dd.time-value { font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums; }
.metrics dd.business-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 15px; line-height: 2.2; }
.metrics small { margin-left: 6px; color: var(--el-text-color-secondary); font-size: 12px; font-weight: 400; }
.trace { display: flex; align-items: baseline; gap: 16px; padding: 14px 0; border-top: 1px solid var(--el-border-color-lighter); font-size: 12px; }
.trace > span { flex-shrink: 0; color: var(--el-text-color-secondary); }
.trace code { color: var(--el-text-color-regular); overflow-wrap: anywhere; }
.failure-notice { display: flex; align-items: flex-start; gap: 12px; padding: 16px 18px; border: 1px solid var(--el-color-danger-light-8); border-radius: 10px; background: var(--el-color-danger-light-9); }
.notice-mark { display: grid; flex-shrink: 0; width: 20px; height: 20px; place-items: center; border-radius: 50%; background: var(--el-color-danger); color: white; font-size: 13px; font-weight: 700; }
.failure-notice > div { min-width: 0; }
.failure-notice strong { color: var(--el-text-color-primary); font-size: 13px; }
.failure-notice p { margin: 6px 0 0; color: var(--el-text-color-regular); font-size: 13px; line-height: 1.7; white-space: pre-wrap; overflow-wrap: anywhere; }
.next-action { display: flex; gap: 12px; font-size: 13px; overflow-wrap: anywhere; }
.next-action > span { color: var(--el-text-color-secondary); flex-shrink: 0; }
.detail-tabs { min-width: 0; }
.detail-tabs :deep(.el-tabs__header) { margin-bottom: 20px; }
.detail-tabs :deep(.el-tabs__nav-wrap::after) { height: 1px; }
.detail-tabs :deep(.el-tabs__item) { height: 44px; }
.tab-label { display: flex; align-items: center; gap: 7px; }
.tab-count { min-width: 20px; padding: 1px 5px; border-radius: 5px; background: var(--el-fill-color); color: var(--el-text-color-regular); font-size: 11px; line-height: 18px; text-align: center; }
.attempts { margin: 0; padding: 0; list-style: none; }
.attempt { position: relative; padding: 0 0 18px 44px; }
.attempt::before { position: absolute; top: 29px; bottom: 0; left: 14px; width: 1px; background: var(--el-border-color); content: ''; }
.attempt:last-child { padding-bottom: 0; }
.attempt:last-child::before { display: none; }
.attempt-number { position: absolute; top: 1px; left: 0; display: grid; width: 28px; height: 28px; place-items: center; border: 1px solid var(--el-border-color); border-radius: 50%; color: var(--el-text-color-regular); background: var(--el-bg-color); font-size: 12px; font-weight: 600; }
.attempt-danger .attempt-number { border-color: var(--el-color-danger-light-5); color: var(--el-color-danger); background: var(--el-color-danger-light-9); }
.attempt-success .attempt-number { border-color: var(--el-color-success-light-5); color: var(--el-color-success-dark-2); background: var(--el-color-success-light-9); }
.attempt-card, .related-log { min-width: 0; padding: 18px 20px; border: 1px solid var(--el-border-color-lighter); border-radius: 10px; background: var(--el-bg-color); }
.attempt-heading { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
.attempt-heading strong { font-size: 14px; font-weight: 600; }
.duration { margin-left: auto; color: var(--el-text-color-secondary); font-size: 12px; font-variant-numeric: tabular-nums; }
.attempt-time, .attempt-meta { display: flex; flex-wrap: wrap; gap: 8px 20px; margin-top: 12px; color: var(--el-text-color-secondary); font-size: 12px; line-height: 1.5; }
.attempt-meta code { margin-left: 5px; color: var(--el-text-color-regular); overflow-wrap: anywhere; }
.attempt-error { margin: 12px 0 0; color: var(--el-color-danger-dark-2); font-size: 13px; line-height: 1.6; white-space: pre-wrap; overflow-wrap: anywhere; }
.attempt-next { margin: 10px 0 0; color: var(--el-text-color-regular); font-size: 12px; overflow-wrap: anywhere; }
.attempt-params { margin-top: 12px; border-bottom: 0; border-top-color: var(--el-border-color-lighter); }
.attempt-params :deep(.el-collapse-item__header) { height: 40px; border: 0; color: var(--el-color-primary); font-size: 12px; background: transparent; }
.attempt-params :deep(.el-collapse-item__wrap) { border: 0; background: transparent; }
.attempt-params :deep(.el-collapse-item__content) { padding-bottom: 0; }
.payload-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; }
.related-logs { display: grid; gap: 12px; }
.related-log-heading { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
.related-log-heading > code { flex: 1; min-width: 0; overflow-wrap: anywhere; font-size: 13px; }
.related-log-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 14px; margin-top: 14px; color: var(--el-text-color-secondary); font-size: 12px; }
.related-log-meta .el-button { margin-left: auto; }
.method-badge { display: inline-flex; align-items: center; flex-shrink: 0; padding: 4px 8px; border-radius: 5px; background: var(--el-color-primary-light-9); color: var(--el-color-primary); font-size: 11px; font-weight: 700; }
.endpoint { display: flex; align-items: baseline; gap: 12px; margin-top: 14px; }
.endpoint code { font-size: 16px; font-weight: 600; line-height: 1.6; overflow-wrap: anywhere; }
.log-metrics { grid-template-columns: .8fr .8fr .8fr 1.4fr; }
.metrics .error-code { color: var(--el-color-danger-dark-2); }
@media (max-width: 700px) {
  .overview { padding: 18px 16px 0; }
  .log-metrics { grid-template-columns: 1fr 1fr; }
  .payload-grid { grid-template-columns: minmax(0, 1fr); }
  .attempt { padding-left: 36px; }
  .attempt-card { padding: 16px; }
}
@media (max-width: 460px) {
  .execution-details { gap: 16px; }
  .metrics { grid-template-columns: 1fr 1fr; gap: 18px 12px; }
  .metrics:not(.log-metrics) > div:first-child { grid-column: 1 / -1; }
  .trace { display: grid; gap: 6px; }
  .task-type { font-size: 16px; }
  .attempt-time { gap: 5px; }
  .duration { flex-basis: 100%; margin-left: 0; }
  .endpoint code { font-size: 13px; }
  .detail-tabs :deep(.el-tabs__item) { padding: 0 12px; }
}
</style>
