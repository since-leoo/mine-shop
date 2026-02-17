<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 * For the full copyright and license information.
 *
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { useI18n } from 'vue-i18n'
import { page, remove } from '~/mall/api/brand'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import BrandForm from './form.vue'

defineOptions({ name: 'mall:brand' })

const { t } = useI18n()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref()
const selections = ref<any[]>([])
const msg = useMessage()

const maDialog: UseDialogExpose = useDialog({
  lgWidth: '520px',
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    const elForm = formRef.value.maForm.getElFormRef()
    elForm.validate().then(() => {
      const action = formType === 'add' ? formRef.value.add : formRef.value.edit
      action().then((res: any) => {
        res.code === ResultCode.SUCCESS ? msg.success(t('mall.brand.operationSuccess')) : msg.error(res.message)
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
    mainTitle: () => t('mall.brand.title'),
    subTitle: () => t('mall.brand.subtitle'),
  },
  tableOptions: {
    on: {
      onSelectionChange: (selection: any[]) => selections.value = selection,
    },
  },
  searchOptions: {
    fold: true,
    text: {
      searchBtn: () => t('mall.search'),
      resetBtn: () => t('mall.reset'),
      isFoldBtn: () => t('mall.common.unfold'),
      notFoldBtn: () => t('mall.common.fold'),
    },
  },
  searchFormOptions: { labelWidth: '100px' },
  requestOptions: { api: page },
})


const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(maDialog),
})

function handleBatchDelete() {
  const ids = selections.value.map(item => item.id)
  if (ids.length < 1) {
    msg.warning(t('mall.brand.selectDeleteData'))
    return
  }
  msg.delConfirm(t('mall.brand.confirmDeleteBrand')).then(async () => {
    const tasks = ids.map((id: number) => remove(id))
    const results = await Promise.all(tasks)
    if (results.every(item => item.code === ResultCode.SUCCESS)) {
      msg.success(t('mall.brand.deleteSuccess'))
      proTableRef.value.refresh()
    }
  })
}
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button
          v-auth="['product:brand:create']"
          type="primary"
          @click="() => {
            maDialog.setTitle(t('mall.brand.addBrand'))
            maDialog.open({ formType: 'add' })
          }"
        >
          {{ t('mall.brand.addBrand') }}
        </el-button>
      </template>
      <template #toolbarLeft>
        <el-button
          v-auth="['product:brand:delete']"
          type="danger"
          plain
          :disabled="selections.length < 1"
          @click="handleBatchDelete"
        >
          {{ t('mall.brand.batchDelete') }}
        </el-button>
      </template>
      <template #empty>
        <el-empty>
          <el-button
            v-auth="['product:brand:create']"
            type="primary"
            @click="() => {
              maDialog.setTitle(t('mall.brand.addBrand'))
              maDialog.open({ formType: 'add' })
            }"
          >
            {{ t('mall.brand.addBrand') }}
          </el-button>
        </el-empty>
      </template>
    </MaProTable>

    <component :is="maDialog.Dialog">
      <template #default="{ formType, data }">
        <BrandForm ref="formRef" :form-type="formType" :data="data" />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">

</style>