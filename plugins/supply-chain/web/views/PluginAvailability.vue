<script setup lang="ts">
import type { Component } from 'vue'
import { ref } from 'vue'
import OptionalPluginPage from '@/components/optional-plugin-page/index.vue'

defineOptions({ inheritAttrs: false })
defineProps<{ loader: () => Promise<{ default: Component }> }>()
const refreshVersion = ref(0)
</script>

<template>
  <section>
    <div class="plugin-status-action">
      <el-button text data-testid="refresh-plugin-status" @click="refreshVersion++">刷新插件状态</el-button>
    </div>
    <OptionalPluginPage :key="refreshVersion" plugin="since/supply-chain" :loader="loader" v-bind="$attrs" />
  </section>
</template>

<style scoped>
.plugin-status-action { display: flex; justify-content: flex-end; margin-bottom: 8px; }
</style>
