<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 - Thank you very much for using MineAdmin.
 -
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'
import type { DictionaryTypeVo } from '../api/DictionaryType.ts'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { deleteByIds, page } from '../api/Dictionary.ts'
import getSearchItems from './components/GetSearchItems.tsx'
import getTableColumns from './components/GetTableColumns.tsx'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import Type from './type.vue'
import Form from './form.vue'

defineOptions({ name: 'dataCenter:dictionary' })

const proTableRef = ref<MaProTableExpose>()
const formRef = ref()
const selections = ref<any[]>([])
const i18n = useTrans() as TransType
const t = i18n.globalTrans
const msg = useMessage()

const typeInfo = ref<DictionaryTypeVo | null>(null)

provide('typeInfo', typeInfo)

// 弹窗配置
const maDialog: UseDialogExpose = useDialog({
  // 保存数据
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    if (['add', 'edit'].includes(formType)) {
      const elForm = formRef.value.maForm.getElFormRef()
      // 验证通过后
      elForm.validate().then(() => {
        switch (formType) {
          // 新增
          case 'add':
            formRef.value.add().then((res: any) => {
              res.code === ResultCode.SUCCESS ? msg.success(t('crud.createSuccess')) : msg.error(res.message)
              maDialog.close()
              proTableRef.value.refresh()
            }).catch((err: any) => {
              msg.alertError(err)
            })
            break
          // 修改
          case 'edit':
            formRef.value.edit().then((res: any) => {
              res.code === 200 ? msg.success(t('crud.updateSuccess')) : msg.error(res.message)
              maDialog.close()
              proTableRef.value.refresh()
            }).catch((err: any) => {
              msg.alertError(err)
            })
            break
        }
      }).catch()
    }
    okLoadingState(false)
  },
})

// 参数配置
const options = ref<MaProTableOptions>({
  // 表格距离底部的像素偏移适配
  adaptionOffsetBottom: 161,
  header: {
    mainTitle: () => '数据字典',
  },
  // 表格参数
  tableOptions: {
    on: {
      // 表格选择事件
      onSelectionChange: (selection: any[]) => selections.value = selection,
    },
  },
  // 搜索参数
  searchOptions: {
    fold: true,
    text: {
      searchBtn: () => t('crud.search'),
      resetBtn: () => t('crud.reset'),
      isFoldBtn: () => t('crud.searchFold'),
      notFoldBtn: () => t('crud.searchUnFold'),
    },
  },
  // 搜索表单参数
  searchFormOptions: { labelWidth: '90px' },
  // 请求配置
  requestOptions: {
    requestParams: {
      order_by: 'sort',
      order_by_direction: 'desc',
    },
  },
})
// 架构配置
const schema = ref<MaProTableSchema>({
  // 搜索项
  searchItems: getSearchItems(t),
  // 表格列
  tableColumns: getTableColumns(maDialog, formRef, t, typeInfo),
})

function addOpen() {
  if (typeInfo.value !== null) {
    maDialog.setTitle(t('crud.add'))
    maDialog.open({ formType: 'add', typeInfo: typeInfo.value })
  }
  else {
    msg.error(t('dictionary.errorInfo.noSelectType'))
  }
}

// 批量删除
function handleDelete() {
  const ids = selections.value.map((item: any) => item.id)
  msg.confirm(t('crud.delMessage')).then(async () => {
    const response = await deleteByIds(ids)
    if (response.code === ResultCode.SUCCESS) {
      msg.success(t('crud.delSuccess'))
      proTableRef.value.refresh()
    }
  })
}

watch(() => typeInfo.value?.id, (id) => {
  if (id) {
    console.log(proTableRef.value)
    proTableRef.value.setRequestParams({ type_id: id })
    proTableRef.value.changeApi(page)
  }
})
</script>

<template>
  <div class="mine-layout gap-x-0 overflow-auto p-3 pb-0 md:flex md:gap-x-3">
    <div class="justify-center rounded bg-white p-2 md:w-2/12 dark-bg-dark-8">
      <Type />
    </div>
    <div class="mt-3 md:mt-0 md:w-10/12">
      <MaProTable ref="proTableRef" :options="options" :schema="schema">
        <template #actions>
          <el-button
            v-auth="['dataCenter:dictionary:save']"
            type="primary"
            @click="addOpen"
          >
            {{ t('crud.add') }}
          </el-button>
        </template>

        <template #toolbarLeft>
          <el-button
            v-auth="['dataCenter:dictionary:delete']"
            type="danger"
            plain
            :disabled="selections.length < 1"
            @click="handleDelete"
          >
            {{ t('crud.delete') }}
          </el-button>
        </template>
        <!-- 数据为空时 -->
        <template #empty>
          <el-empty>
            <el-button
              v-auth="['dataCenter:dictionary:save']"
              type="primary"
              @click="addOpen"
            >
              {{ t('crud.add') }}
            </el-button>
          </el-empty>
        </template>
      </MaProTable>
    </div>

    <component :is="maDialog.Dialog">
      <template #default="{ formType, typeInfo, data }">
        <!-- 新增、编辑表单 -->
        <Form ref="formRef" :form-type="formType" :type-info="typeInfo" :data="data" />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">
:deep(.mine-card) {
  margin-right: 0;
  margin-left: 0;
}
</style>
