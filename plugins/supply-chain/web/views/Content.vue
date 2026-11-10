<script setup lang="ts">
import type { Connection } from '../api/supply-chain'
import { computed, onMounted, ref } from 'vue'
import hasAuth from '@/utils/permission/hasAuth'
import { getConnections } from '../api/supply-chain'
import Catalog from './Catalog.vue'
import AfterSales from './AfterSales.vue'
import Connections from './ConnectionSettings.vue'
import Records from './Records.vue'
import { errorMessage, statusLabel } from './format'

const tabs = [
  { name: 'catalog', label: '选品中心', permission: 'catalog/read' },
  { name: 'products', label: '已导入商品', permission: 'products/read' },
  { name: 'purchases', label: '采购订单', permission: 'purchases/read' },
  { name: 'after-sales', label: '采购售后', permission: 'after-sales/read' },
  { name: 'tasks', label: '任务与重试', permission: 'tasks/read' },
  { name: 'logs', label: '接口日志', permission: 'logs/read' },
  { name: 'connections', label: '连接设置', permission: 'connections/read' },
] as const
const allowedTabs = computed(() => tabs.filter(tab => hasAuth(`supply-chain:${tab.permission}`)))
const active = ref<string>(allowedTabs.value[0]?.name || '')
const connections = ref<Connection[]>([])
const connectionId = ref<number>()
const selected = computed(() => connections.value.find(connection => connection.id === connectionId.value))
const error = ref('')
const loading = ref(false)
const focusedTaskId = ref<number>()

async function loadConnections() {
  if (!hasAuth('supply-chain:connections/read')) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await getConnections()
    connections.value = Array.isArray(data) ? data : data.list
    if (!connections.value.some(connection => connection.id === connectionId.value)) connectionId.value = connections.value[0]?.id
  }
  catch (failure) { error.value = errorMessage(failure) }
  finally { loading.value = false }
}

function viewTask(id?: number) {
  if (!hasAuth('supply-chain:tasks/read')) return
  focusedTaskId.value = id
  active.value = 'tasks'
}
onMounted(loadConnections)
</script>

<template>
  <div class="supply-page">
    <header><div><h2>供应链</h2><p>选品导入、商品同步、采购订单及执行追踪</p></div>
      <el-select v-if="active !== 'connections' && hasAuth('supply-chain:connections/read')" v-model="connectionId" :loading="loading" placeholder="选择供应链连接" aria-label="供应链连接" clearable>
        <el-option v-for="connection in connections" :key="connection.id" :value="connection.id" :label="`${connection.name} · ${statusLabel(connection.mode)}`" />
      </el-select>
      <el-input-number v-else-if="active !== 'connections'" v-model="connectionId" :min="1" :precision="0" :controls="false" placeholder="供应链连接 ID" aria-label="供应链连接 ID" />
    </header>
    <el-alert v-if="error" :title="error" type="error" :closable="false" show-icon><el-button link @click="loadConnections">重新加载连接</el-button></el-alert>
    <el-empty v-if="!allowedTabs.length" description="暂无供应链插件访问权限" />
    <el-tabs v-else v-model="active">
      <el-tab-pane v-for="tab in allowedTabs" :key="tab.name" :name="tab.name" :label="tab.label" />
    </el-tabs>
    <Catalog v-if="active === 'catalog'" :connection-id="connectionId" :mode="selected?.mode" @task="viewTask" />
    <Connections v-else-if="active === 'connections'" @saved="loadConnections" />
    <AfterSales v-else-if="active === 'after-sales'" :connection-id="connectionId" @task="viewTask" />
    <Records v-else-if="active === 'products' || active === 'purchases' || active === 'tasks' || active === 'logs'" :key="active" :type="active" :connection-id="connectionId" :task-id="focusedTaskId" @task="viewTask" />
  </div>
</template>

<style scoped>
.supply-page { padding: 20px; background: var(--el-bg-color); border-radius: 10px; }
header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
header h2 { margin: 0 0 6px; font-size: 22px; }
header p { margin: 0; color: var(--el-text-color-secondary); }
header .el-select { width: 300px; }
.el-alert { margin-bottom: 16px; }
</style>
