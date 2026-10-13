<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, ref, type Component } from 'vue'
import useHttp from '@/hooks/auto-imports/useHttp.ts'
import usePluginStore from '@/store/modules/usePluginStore.ts'
const props = defineProps<{ plugin: string, loader: () => Promise<{ default: Component }> }>()
const available = ref(false)
const loading = ref(true)
const failed = ref(false)
const store = usePluginStore()
const enabled = computed(() => available.value && store.getPlugin(props.plugin)?.config.enable !== false)
const page = defineAsyncComponent(props.loader)
onMounted(async () => {
  try {
    const result = await useHttp().get<Array<{ name: string, installed: boolean, enabled: boolean }>>('/admin/plugin-center/plugins')
    available.value = (result.data ?? []).some(item => item.name === props.plugin && item.installed && item.enabled)
  }
  catch { failed.value = true }
  finally { loading.value = false }
})
</script>
<template>
  <div v-loading="loading">
    <component :is="page" v-if="!loading && enabled" v-bind="$attrs" />
    <el-empty v-else-if="!loading" :description="failed ? '暂时无法读取插件状态，请刷新重试' : '该功能插件未安装或已停用'" />
  </div>
</template>
