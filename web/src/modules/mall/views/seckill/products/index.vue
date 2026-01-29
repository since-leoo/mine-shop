<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { useRoute, useRouter } from 'vue-router'
import { productPage, productRemove } from '~/mall/api/seckill'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import ProductForm from './form.vue'

defineOptions({ name: 'mall:seckill:product' })

const route = useRoute()
const router = useRouter()
const sessionId = computed(() => Number(route.query.session_id) || 0)
const activityId = computed(() => Number(route.query.activity_id) || 0)

const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref()
const selections = ref<any[]>([])
const msg = useMessage()

const maDialog: UseDialogExpose = useDialog({
  lgWidth: '600px',
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    const elForm = formRef.value.maForm.getElFormRef()
    elForm.validate().then(() => {
      const action = formType === 'add' ? formRef.value.add : formRef.value.edit
      action().then((res: any) => {
        res.code === ResultCode.SUCCESS ? msg.success('操作成功') : msg.error(res.message)
        maDialog.close()
        proTableRef.value.refresh()
      }).catch((err: any) => {
        msg.alertError(err)
      }).finally(() => okLoadingState(false))
    }).catch(() => okLoadingState(false))
  },
})

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 161,
  header: {
    mainTitle: () => '商品配置',
    subTitle: () => '配置秒杀场次的商品和价格',
  },
  tableOptions: {
    on: {
      onSelectionChange: (selection: any[]) => selections.value = selection,
    },
  },
  searchOptions: {
    fold: true,
    text: {
      searchBtn: () => '搜索',
      resetBtn: () => '重置',
      isFoldBtn: () => '展开',
      notFoldBtn: () => '收起',
    },
  },
  searchFormOptions: { labelWidth: '90px' },
  requestOptions: {
    api: (params: any) => productPage({ ...params, session_id: sessionId.value }),
  },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(maDialog, proTableRef),
})

function handleBatchDelete() {
  const ids = selections.value.map(item => item.id)
  if (ids.length < 1) {
    msg.warning('请选择要删除的数据')
    return
  }
  msg.delConfirm('确定移除选中的商品吗？').then(async () => {
    const tasks = ids.map((id: number) => productRemove(id))
    const results = await Promise.all(tasks)
    if (results.every(item => item.code === ResultCode.SUCCESS)) {
      msg.success('删除成功')
      proTableRef.value.refresh()
    }
  })
}

function goBack() {
  router.push({ path: '/mall/seckill/sessions', query: { activity_id: activityId.value } })
}
</script>

<template>
  <div class="mine-layout pt-3">
    <div class="mb-3">
      <el-button @click="goBack">
        <ma-svg-icon name="material-symbols:arrow-back" class="mr-1" />
        返回场次列表
      </el-button>
    </div>
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button
          v-auth="['seckill:product:create']"
          type="primary"
          @click="() => {
            maDialog.setTitle('添加商品')
            maDialog.open({ formType: 'add' })
          }"
        >
          添加商品
        </el-button>
      </template>
      <template #toolbarLeft>
        <el-button
          v-auth="['seckill:product:delete']"
          type="danger"
          plain
          :disabled="selections.length < 1"
          @click="handleBatchDelete"
        >
          批量移除
        </el-button>
      </template>
      <template #empty>
        <el-empty description="暂无商品">
          <el-button
            v-auth="['seckill:product:create']"
            type="primary"
            @click="() => {
              maDialog.setTitle('添加商品')
              maDialog.open({ formType: 'add' })
            }"
          >
            添加商品
          </el-button>
        </el-empty>
      </template>
    </MaProTable>

    <component :is="maDialog.Dialog">
      <template #default="{ formType, data }">
        <ProductForm
          ref="formRef"
          :form-type="formType"
          :data="data"
          :session-id="sessionId"
          :activity-id="activityId"
        />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">

</style>
