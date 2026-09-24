<script setup lang="ts">
defineOptions({ name: 'sms:plugin-center' })
</script>

<template>
  <div class="sms-page">
    <el-card shadow="never" class="hero-card">
      <div class="hero-icon"><ma-svg-icon name="carbon:notification" :size="30" /></div>
      <div>
        <h2>短信服务</h2>
        <p>为登录、注册和安全验证提供统一的短信验证码能力。</p>
      </div>
    </el-card>
    <div class="info-grid">
      <el-card shadow="never">
        <template #header><span class="card-title">服务能力</span></template>
        <ul class="feature-list"><li>短信验证码生成与发送</li><li>Redis 验证码存储</li><li>发送频率限制</li></ul>
      </el-card>
      <el-card shadow="never">
        <template #header><span class="card-title">配置说明</span></template>
        <p class="muted">短信服务商、签名和模板参数请在系统设置的短信通道中配置。插件启用后，业务模块会自动使用统一短信服务。</p>
      </el-card>
    </div>
  </div>
</template>

<style scoped>
.sms-page { display: flex; flex-direction: column; gap: 16px; }
.hero-card :deep(.el-card__body) { display: flex; align-items: center; gap: 16px; padding: 24px; }
.hero-icon { display: inline-flex; align-items: center; justify-content: center; width: 58px; height: 58px; border-radius: 16px; background: var(--el-color-primary-light-9); color: var(--el-color-primary); }
h2 { margin: 0; color: var(--el-text-color-primary); font-size: 20px; }
p { margin: 7px 0 0; color: var(--el-text-color-secondary); line-height: 1.6; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
.card-title { font-weight: 600; }
.feature-list { margin: 0; padding-left: 18px; color: var(--el-text-color-regular); line-height: 2; }
.muted { font-size: 13px; }
</style>
