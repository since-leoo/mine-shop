<script setup lang="ts">
import type { DistributionSettings } from '../api/distribution'
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import hasRole from '@/utils/permission/hasRole'
import { getSettings, saveSettings } from '../api/distribution'
import { errorMessage } from './format'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const settings = ref<DistributionSettings>()
const excluded = ref('')
const canRead = computed(() => hasRole('SuperAdmin'))
const canUpdate = canRead

async function load() {
  if (!canRead.value) {
    return
  }
  loading.value = true
  error.value = ''
  try {
    const { data } = await getSettings()
    settings.value = { ...data, product_rules: data.product_rules ?? [], excluded_product_ids: data.excluded_product_ids ?? [] }
    excluded.value = settings.value.excluded_product_ids.join(', ')
  }
  catch (failure) {
    error.value = errorMessage(failure)
  }
  finally {
    loading.value = false
  }
}

async function save() {
  if (!settings.value || !canUpdate.value || saving.value) {
    return
  }
  const data = JSON.parse(JSON.stringify(settings.value)) as DistributionSettings
  data.h5_base_url = (data.h5_base_url || '').trim().replace(/\/$/, '')
  if (data.h5_base_url) {
    try {
      const url = new URL(data.h5_base_url)
      if (!['https:', 'http:'].includes(url.protocol) || url.search || url.hash || url.username || url.password) {
        throw new Error('invalid')
      }
    }
    catch {
      ElMessage.warning('请填写完整的 H5 站点地址，不包含查询参数或 #')
      return
    }
  }
  const ids = excluded.value.split(/[,，\s]+/).filter(Boolean).map(Number)
  if (ids.some(id => !Number.isSafeInteger(id) || id <= 0)) {
    ElMessage.warning('排除商品 ID 必须为正整数，以逗号分隔')
    return
  }
  data.excluded_product_ids = [...new Set(ids)]
  const integerFields = ['commission_rate_bps', 'settlement_days', 'withdrawal_min_amount', 'withdrawal_fee_bps'] as const
  if (integerFields.some(key => !Number.isSafeInteger(data[key]) || data[key] < 0)
    || data.commission_rate_bps > 10000 || data.withdrawal_fee_bps >= 10000 || data.withdrawal_min_amount < 1 || data.settlement_days > 365) {
    ElMessage.warning('请检查设置：佣金比例不超过 100%，提现手续费小于 100%，最低提现不少于 0.01 元')
    return
  }
  const rules = new Set<string>()
  for (const rule of data.product_rules) {
    const key = `${rule.product_id}:${rule.sku_id || 0}`
    if (!Number.isSafeInteger(rule.product_id) || rule.product_id <= 0
      || (rule.sku_id != null && (!Number.isSafeInteger(rule.sku_id) || rule.sku_id <= 0))
      || !Number.isSafeInteger(rule.value) || rule.value < 0 || (rule.mode === 'rate' && rule.value > 10000)) {
      ElMessage.warning('商品规则需填写有效的商品/SKU ID 和佣金数值，比例不得超过 100%')
      return
    }
    if (rules.has(key)) {
      ElMessage.warning('同一个商品/SKU 不能重复配置佣金规则')
      return
    }
    rules.add(key)
  }
  saving.value = true
  error.value = ''
  try {
    const { data: saved } = await saveSettings(data)
    settings.value = saved && typeof saved.enabled === 'boolean' ? saved : data
    excluded.value = (settings.value.excluded_product_ids ?? []).join(', ')
    ElMessage.success('分销与佣金设置已保存')
  }
  catch (failure) {
    error.value = errorMessage(failure)
  }
  finally {
    saving.value = false
  }
}
onMounted(load)
</script>

<template>
  <section v-loading="loading" class="distribution-settings">
    <el-empty v-if="!canRead" description="分销管理仅限超级管理员" />
    <template v-else>
      <el-alert v-if="error" :title="error" type="error" show-icon :closable="false" />
      <el-button v-if="!settings && !loading" @click="load">
        重新加载
      </el-button>
      <el-form v-if="settings" label-position="top" :disabled="!canUpdate || saving" @submit.prevent="save">
        <el-alert title="每人最多 3 个直属粉丝，仅直属上级获得销售佣金。团队统计包含所有后代。" type="info" :closable="false" show-icon />
        <h3>业务与展示</h3>
        <div class="settings-grid">
          <el-form-item label="分销业务">
            <el-switch v-model="settings.enabled" data-testid="distribution-enabled" active-text="开启" inactive-text="关闭" />
          </el-form-item>
          <el-form-item label="H5 展示">
            <el-switch v-model="settings.h5_enabled" active-text="显示" />
          </el-form-item>
          <el-form-item label="小程序展示">
            <el-switch v-model="settings.mini_program_enabled" active-text="显示" />
          </el-form-item>
        </div>
        <el-form-item label="H5 站点地址">
          <el-input v-model="settings.h5_base_url" placeholder="https://shop.example.com" />
        </el-form-item>
        <p class="hint">
          关闭业务后停止新增邀请与新订单计佣；历史关系、退款冲正、已产生佣金及提现继续处理。终端开关与 DIY 组件共同控制入口显示。
        </p>
        <h3>佣金规则</h3>
        <div class="settings-grid">
          <el-form-item label="全局佣金比例（%）">
            <el-input-number :model-value="settings.commission_rate_bps / 100" :min="0" :max="100" :precision="2" :step="0.01" data-testid="commission-rate" @update:model-value="settings.commission_rate_bps = Math.round(Number($event ?? 0) * 100)" />
          </el-form-item>
          <el-form-item label="结算等待天数">
            <el-input-number v-model="settings.settlement_days" :min="0" :max="365" :precision="0" />
          </el-form-item>
          <el-form-item label="计佣订单">
            <el-select v-model="settings.commission_mode">
              <el-option label="所有有效订单（含复购）" value="all_orders" /><el-option label="仅首笔符合计佣条件的支付订单" value="first_order" />
            </el-select>
          </el-form-item>
          <el-form-item label="活动商品参与分佣">
            <el-switch v-model="settings.activity_enabled" />
          </el-form-item>
        </div>
        <p class="hint">
          首单指绑定后首笔符合计佣条件的已支付订单；排除商品、零佣金或业务关闭的新订单不占首单，退款不释放资格。按商品实付金额计佣，运费不参与；订单规则保存快照，后续改价不追溯。完成订单经过等待期且无未结束售后后结算，退款按明细冲回。
        </p>
        <el-form-item label="不参与分佣的商品 ID">
          <el-input v-model="excluded" placeholder="例如：12, 25, 36" />
        </el-form-item>
        <div class="section-title">
          <strong>商品独立规则</strong><el-button size="small" data-testid="add-product-rule" @click="settings.product_rules.push({ product_id: 0, sku_id: null, mode: 'rate', value: 0 })">
            添加规则
          </el-button>
        </div>
        <p class="hint">
          排除商品优先；其余按 SKU 规则、商品规则、全局规则依次匹配。固定佣金按每件商品计算。
        </p>
        <div v-for="(rule, index) in settings.product_rules" :key="index" class="product-rule">
          <el-form-item label="商品 ID">
            <el-input-number v-model="rule.product_id" :min="1" :precision="0" :controls="false" />
          </el-form-item>
          <el-form-item label="SKU ID（留空表示全商品）">
            <el-input-number :model-value="rule.sku_id ?? undefined" :min="1" :precision="0" :controls="false" @update:model-value="rule.sku_id = $event ?? null" />
          </el-form-item>
          <el-form-item label="计算方式">
            <el-select v-model="rule.mode" @change="rule.value = 0">
              <el-option label="比例（%）" value="rate" /><el-option label="固定金额（元/件）" value="fixed" />
            </el-select>
          </el-form-item>
          <el-form-item :label="rule.mode === 'rate' ? '佣金比例（%）' : '固定佣金（元/件）'">
            <el-input-number :model-value="rule.value / 100" :min="0" :max="rule.mode === 'rate' ? 100 : 999999" :precision="2" :step="0.01" :controls="false" @update:model-value="rule.value = Math.round(Number($event ?? 0) * 100)" />
          </el-form-item>
          <el-button link type="danger" @click="settings.product_rules.splice(index, 1)">
            删除
          </el-button>
        </div>
        <h3>提现规则</h3>
        <div class="settings-grid">
          <el-form-item label="最低提现金额（元）">
            <el-input-number :model-value="settings.withdrawal_min_amount / 100" :min="0.01" :max="999999" :precision="2" :step="0.01" data-testid="withdrawal-min" @update:model-value="settings.withdrawal_min_amount = Math.round(Number($event ?? 0) * 100)" />
          </el-form-item>
          <el-form-item label="提现手续费（%）">
            <el-input-number :model-value="settings.withdrawal_fee_bps / 100" :min="0" :max="99.99" :precision="2" :step="0.01" @update:model-value="settings.withdrawal_fee_bps = Math.round(Number($event ?? 0) * 100)" />
          </el-form-item>
        </div>
        <div class="save-bar">
          <el-button type="primary" :loading="saving" data-testid="save-settings" native-type="submit">
            保存设置
          </el-button><span v-if="!canUpdate" class="hint">当前账号仅可查看设置</span>
        </div>
      </el-form>
    </template>
  </section>
</template>

<style scoped>
.distribution-settings { max-width: 1100px; }
.settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 24px; }
h3 { margin: 24px 0 16px; font-size: 16px; }
.hint { color: var(--el-text-color-secondary); font-size: 13px; line-height: 1.7; margin: 4px 0 18px; }
.section-title, .save-bar { display: flex; align-items: center; gap: 16px; margin: 18px 0; }
.section-title { justify-content: space-between; }
.product-rule { display: grid; grid-template-columns: repeat(4, minmax(100px, 1fr)) auto; align-items: center; gap: 10px; padding: 12px; margin-bottom: 10px; border: 1px solid var(--el-border-color); border-radius: 8px; }
.product-rule :deep(.el-input-number) { width: 100%; }
@media (max-width: 800px) { .product-rule { grid-template-columns: repeat(2, minmax(100px, 1fr)); } }
</style>
