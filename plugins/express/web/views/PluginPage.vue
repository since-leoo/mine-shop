<script setup lang="ts">
defineOptions({ name: 'express:plugin-center' })
</script>

<template>
  <div class="express-page">
    <el-card shadow="never" class="hero-card">
      <div class="hero-icon"><ma-svg-icon name="carbon:delivery-truck" :size="30" /></div>
      <div>
        <h2>物流查询</h2>
        <p>统一管理物流轨迹查询服务，订单物流查询将使用这里配置的服务商。</p>
      </div>
    </el-card>
    <div class="info-grid">
      <el-card shadow="never">
        <template #header><span class="card-title">已支持服务商</span></template>
        <div class="provider-row"><span class="provider-dot" />快递100</div>
        <p class="muted">可在系统设置的物流查询配置中填写 Customer、Key 和查询地址。</p>
      </el-card>
      <el-card shadow="never">
        <template #header><span class="card-title">插件能力</span></template>
        <ul class="feature-list"><li>物流轨迹查询</li><li>查询结果缓存</li><li>统一的物流服务接口</li></ul>
      </el-card>
    </div>
  </div>
</template>

<style scoped>
.express-page { display: flex; flex-direction: column; gap: 16px; }
.hero-card :deep(.el-card__body) { display: flex; align-items: center; gap: 16px; padding: 24px; }
.hero-icon { display: inline-flex; align-items: center; justify-content: center; width: 58px; height: 58px; border-radius: 16px; background: var(--el-color-primary-light-9); color: var(--el-color-primary); }
h2 { margin: 0; color: var(--el-text-color-primary); font-size: 20px; }
p { margin: 7px 0 0; color: var(--el-text-color-secondary); line-height: 1.6; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
.card-title { font-weight: 600; }
.provider-row { display: flex; align-items: center; gap: 8px; color: var(--el-text-color-primary); font-weight: 500; }
.provider-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--el-color-success); }
.muted { font-size: 13px; }
.feature-list { margin: 0; padding-left: 18px; color: var(--el-text-color-regular); line-height: 2; }
</style>
