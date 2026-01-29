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
import { sessionPage, sessionRemove } from '~/mall/api/seckill'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import SessionForm from './form.vue'

defineOptions({ name: 'mall:seckill:session' })

const route = useRoute()
const router = useRouter()
const activityId = computed(() => Number(route.query.activity_id) || 0)
const activityTitle = computed(() => route.query.activity_title as string || '未知活动')

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
    mainTitle: () => `场次管理 - ${activityTitle.value}`,
    subTitle: () => '管理秒杀活动的时间场次',
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
    api: (params: any) => sessionPage({ ...params, activity_id: activityId.value }),
  },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(maDialog, router, proTableRef),
})

function handleBatchDelete() {
  const ids = selections.value.map(item => item.id)
  if (ids.length < 1) {
    msg.warning('请选择要删除的数据')
    return
  }
  msg.delConfirm('确定删除选中的场次吗？场次下的商品也会被删除。').then(async () => {
    const tasks = ids.map((id: number) => sessionRemove(id))
    const results = await Promise.all(tasks)
    if (results.every(item => item.code === ResultCode.SUCCESS)) {
      msg.success('删除成功')
      proTableRef.value.refresh()
    }
  })
}

function goBack() {
  router.push({ path: '/mall/seckill' })
}
</script>

<template>
  <div class="mine-layout pt-3">
    <div class="mb-3">
      <el-button @click="goBack">
        <ma-svg-icon name="material-symbols:arrow-back" class="mr-1" />
        返回活动列表
      </el-button>
    </div>
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button
          v-auth="['seckill:session:create']"
          type="primary"
          @click="() => {
            maDialog.setTitle('新增场次')
            maDialog.open({ formType: 'add' })
          }"
        >
          新增场次
        </el-button>
      </template>
      <template #toolbarLeft>
        <el-button
          v-auth="['seckill:session:delete']"
          type="danger"
          plain
          :disabled="selections.length < 1"
          @click="handleBatchDelete"
        >
          批量删除
        </el-button>
      </template>
      <template #empty>
        <el-empty description="暂无场次">
          <el-button
            v-auth="['seckill:session:create']"
            type="primary"
            @click="() => {
              maDialog.setTitle('新增场次')
              maDialog.open({ formType: 'add' })
            }"
          >
            新增场次
          </el-button>
        </el-empty>
      </template>
    </MaProTable>

    <component :is="maDialog.Dialog">
      <template #default="{ formType, data }">
        <SessionForm ref="formRef" :form-type="formType" :data="data" :activity-id="activityId" />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">

</style>
