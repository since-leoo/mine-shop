<script setup lang="ts">
import { defineAsyncComponent, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import usePluginStore from '@/store/modules/usePluginStore.ts'

defineOptions({ name: 'PluginCenter' })

const route = useRoute()
const router = useRouter()
const pluginStore = usePluginStore()
const plugins = computed(() => pluginStore.getPluginCenterList())
const pluginKey = computed(() => String(route.params.pluginName ?? ''))
const current = computed(() => plugins.value.find(item => item.key === pluginKey.value))
const isDetail = computed(() => Boolean(pluginKey.value) && Boolean(current.value))
const isSettings = computed(() => route.query.tab === 'settings')
const settingsPlugin = ref<NonNullable<typeof current.value>>()
const settingsVisible = ref(false)

function openPlugin(key: string) { router.push({ name: 'MinePluginCenterRoute', params: { pluginName: key } }) }
function goBack() { router.push({ name: 'MinePluginCenterRoute' }) }
function openSettings(item: NonNullable<typeof current.value>) {
  const settings = item.center?.settings
  if (!settings) return
  if (settings.route) { router.push(settings.route); return }
  settingsPlugin.value = item
  settingsVisible.value = true
}
function togglePlugin(item: NonNullable<typeof current.value>) {
  if (item.enabled) pluginStore.disabled(item.key)
  else pluginStore.enabled(item.key)
}
function pageComponent(item: NonNullable<typeof current.value>): Component | null {
  const page = item.center?.page
  if (!page) return null
  return typeof page === 'function' ? defineAsyncComponent(page as any) : page
}
function settingsComponent(item: NonNullable<typeof current.value>): Component | null {
  const page = item.center?.settings?.page
  if (!page) return null
  return typeof page === 'function' ? defineAsyncComponent(page as any) : page
}
</script>

<template>
  <div class="plugin-center-page">
    <template v-if="!isDetail">
      <header class="plugin-center-header">
        <div><div class="plugin-center-eyebrow">EXTENSIONS</div><h1>插件中心</h1><p>发现、管理并使用已安装的业务插件</p></div>
        <div class="plugin-count"><strong>{{ plugins.length }}</strong><span>个插件</span></div>
      </header>
      <el-empty v-if="!plugins.length" description="暂无已安装插件" :image-size="120" />
      <div v-else class="plugin-grid">
        <article v-for="item in plugins" :key="item.key" class="plugin-card" :class="{ disabled: !item.enabled }" tabindex="0" @click="openPlugin(item.key)" @keyup.enter="openPlugin(item.key)">
          <div class="plugin-card-top"><div class="plugin-icon"><ma-svg-icon :name="item.center?.icon || 'carbon:application'" :size="30" /></div><el-tag size="small" :type="item.enabled ? 'success' : 'info'">{{ item.enabled ? '已启用' : '已停用' }}</el-tag></div>
          <h2>{{ item.center?.title || item.info.name }}</h2>
          <p>{{ item.center?.description || item.info.description || '暂无插件描述' }}</p>
          <footer><span>{{ item.info.author || '未知作者' }}</span><span>v{{ item.info.version }}</span><el-button v-if="item.center?.settings" link type="primary" size="small" @click.stop="openSettings(item)">设置</el-button><ma-svg-icon name="carbon:arrow-right" :size="18" /></footer>
        </article>
      </div>
    </template>
    <template v-else-if="current">
      <header class="plugin-detail-header">
        <div class="plugin-detail-title"><div class="plugin-icon"><ma-svg-icon :name="current.center?.icon || 'carbon:application'" :size="28" /></div><div><h1>{{ current.center?.title || current.info.name }}</h1><p>{{ current.center?.description || current.info.description }}</p></div></div>
        <div class="plugin-detail-actions"><el-button link @click="goBack"><ma-svg-icon name="carbon:arrow-left" :size="18" /> 返回</el-button><el-button :type="current.enabled ? 'warning' : 'success'" @click="togglePlugin(current)">{{ current.enabled ? '停用插件' : '启用插件' }}</el-button></div>
      </header>
      <main class="plugin-detail-content"><component :is="settingsComponent(current)" v-if="isSettings && settingsComponent(current) && current.enabled" :plugin-name="current.key" /><component :is="pageComponent(current)" v-else-if="pageComponent(current) && current.enabled" /><el-empty v-else-if="!current.enabled" description="插件已停用，请先启用后使用" /><el-empty v-else description="该插件暂未提供插件中心页面" /></main>
    </template>
    <el-empty v-else description="插件不存在或已卸载" />

    <el-dialog v-model="settingsVisible" :title="`${settingsPlugin?.center?.title || settingsPlugin?.info.name || ''} · 配置`" width="720px" destroy-on-close append-to-body>
      <component :is="settingsPlugin ? settingsComponent(settingsPlugin) : null" v-if="settingsPlugin" :plugin-name="settingsPlugin.key" />
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.plugin-center-page { min-height: 100%; padding: 28px 32px 40px; background: var(--el-bg-color-page); }
.plugin-center-header, .plugin-detail-header { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 28px; }
.plugin-center-eyebrow { color: var(--el-color-primary); font-size: 11px; font-weight: 700; letter-spacing: .16em; }
h1, h2, p { margin: 0; }
.plugin-center-header h1, .plugin-detail-title h1 { margin-top: 5px; color: var(--el-text-color-primary); font-size: 26px; font-weight: 700; }
.plugin-center-header p, .plugin-detail-title p { margin-top: 7px; color: var(--el-text-color-secondary); font-size: 14px; }
.plugin-count { display: flex; align-items: baseline; gap: 6px; padding: 12px 18px; border: 1px solid var(--el-border-color-lighter); border-radius: 12px; background: var(--el-bg-color); color: var(--el-text-color-secondary); }
.plugin-count strong { color: var(--el-color-primary); font-size: 24px; }
.plugin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 18px; }
.plugin-card { min-height: 190px; padding: 22px; border: 1px solid var(--el-border-color-lighter); border-radius: 16px; background: var(--el-bg-color); box-shadow: 0 4px 16px rgb(15 23 42 / 4%); cursor: pointer; transition: transform .2s, box-shadow .2s, border-color .2s; }
.plugin-card:hover, .plugin-card:focus-visible { border-color: var(--el-color-primary-light-5); box-shadow: 0 12px 28px rgb(15 23 42 / 10%); outline: none; transform: translateY(-3px); }
.plugin-card.disabled { opacity: .72; }
.plugin-card-top, .plugin-card footer { display: flex; align-items: center; justify-content: space-between; }
.plugin-icon { display: inline-flex; align-items: center; justify-content: center; width: 54px; height: 54px; flex-shrink: 0; border-radius: 15px; background: var(--el-color-primary-light-9); color: var(--el-color-primary); }
.plugin-card h2 { margin-top: 20px; font-size: 17px; }
.plugin-card p { min-height: 42px; margin-top: 9px; color: var(--el-text-color-secondary); font-size: 13px; line-height: 1.6; }
.plugin-card footer { margin-top: 20px; color: var(--el-text-color-placeholder); font-size: 12px; }
.plugin-detail-header { align-items: flex-start; padding-bottom: 22px; border-bottom: 1px solid var(--el-border-color-lighter); }
.plugin-detail-title { display: flex; align-items: center; gap: 14px; flex: 1; min-width: 260px; }
.plugin-detail-actions { display: inline-flex; align-items: center; gap: 8px; }
.plugin-detail-content { min-height: 420px; }
@media (max-width: 768px) { .plugin-center-page { padding: 20px 16px 28px; } .plugin-center-header { align-items: flex-start; } .plugin-count { display: none; } }
</style>
