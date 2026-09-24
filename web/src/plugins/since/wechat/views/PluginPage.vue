<script setup lang="ts">
defineOptions({ name: 'wechat:plugin-center' })

const capabilities = [
  { icon: 'ant-design:mobile-outlined', title: '微信小程序', description: '支持静默登录、手机号解析、二维码和短链接能力。' },
  { icon: 'ant-design:notification-outlined', title: '微信公众号', description: '提供公众号服务端实例和消息处理能力。' },
  { icon: 'ant-design:safety-certificate-outlined', title: '统一配置', description: '微信密钥和回调配置由服务端安全保存并统一读取。' },
]
</script>

<template>
  <div class="wechat-plugin">
    <div class="hero-card">
      <div class="hero-icon"><ma-svg-icon name="ant-design:wechat-outlined" :size="34" /></div>
      <div>
        <h2>微信组件</h2>
        <p>为商城提供微信小程序与公众号的基础接入能力。</p>
      </div>
      <el-tag type="success" effect="light">运行中</el-tag>
    </div>

    <div class="section-heading">
      <div><h3>能力概览</h3><p>插件已为业务模块提供统一的微信客户端实例。</p></div>
    </div>
    <div class="capability-grid">
      <el-card v-for="item in capabilities" :key="item.title" class="capability-card" shadow="never">
        <div class="capability-icon"><ma-svg-icon :name="item.icon" :size="22" /></div>
        <h4>{{ item.title }}</h4>
        <p>{{ item.description }}</p>
      </el-card>
    </div>

    <el-alert class="config-alert" title="配置提示" type="info" :closable="false" show-icon>
      请在服务端配置文件 <code>config/autoload/wechat.php</code> 中填写小程序或公众号凭据，修改后重启服务使配置生效。
    </el-alert>
  </div>
</template>

<style scoped>
.wechat-plugin { min-height: 100%; padding: 24px; background: var(--el-bg-color-page, #f6f8fc); }
.hero-card { display: flex; align-items: center; gap: 16px; padding: 24px; border: 1px solid var(--el-border-color-lighter); border-radius: 14px; background: var(--el-bg-color, #fff); box-shadow: 0 8px 24px rgb(15 23 42 / 5%); }
.hero-icon { display: flex; width: 60px; height: 60px; align-items: center; justify-content: center; border-radius: 16px; color: #fff; background: linear-gradient(135deg, #22c55e, #16a34a); }
.hero-card h2 { margin: 0 0 6px; font-size: 20px; color: var(--el-text-color-primary); }
.hero-card p, .section-heading p { margin: 0; color: var(--el-text-color-secondary); }
.hero-card .el-tag { margin-left: auto; }
.section-heading { margin: 28px 0 14px; }
.section-heading h3 { margin: 0 0 6px; font-size: 16px; }
.capability-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.capability-card { border-radius: 12px; }
.capability-icon { display: flex; width: 42px; height: 42px; align-items: center; justify-content: center; margin-bottom: 14px; border-radius: 11px; color: var(--el-color-primary); background: var(--el-color-primary-light-9); }
.capability-card h4 { margin: 0 0 8px; font-size: 15px; }
.capability-card p { min-height: 42px; margin: 0; line-height: 1.6; color: var(--el-text-color-secondary); }
.config-alert { margin-top: 20px; }
code { padding: 2px 5px; border-radius: 4px; background: var(--el-fill-color-light); }
@media (max-width: 768px) { .capability-grid { grid-template-columns: 1fr; } .wechat-plugin { padding: 16px; } }
</style>
