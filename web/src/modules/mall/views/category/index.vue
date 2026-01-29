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
import type { Ref } from 'vue'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

import { page, remove, tree } from '~/mall/api/category'
import getSearchItems from './data/getSearchItems.tsx'
import getTableColumns from './data/getTableColumns.tsx'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import CategoryForm from './form.vue'

defineOptions({ name: 'mall:category' })

const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref()
const selections = ref<any[]>([])
const categoryTree = ref<any[]>([])
const treeData = ref<any[]>([])
const parentNameMap = ref<Record<number, string>>({})
const currentParentId = ref<number | undefined>(undefined)
const msg = useMessage()

const maDialog: UseDialogExpose = useDialog({
  lgWidth: '520px',
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    const elForm = formRef.value.maForm.getElFormRef()
    elForm.validate().then(() => {
      const action = formType === 'add' ? formRef.value.add : formRef.value.edit
      action().then((res: any) => {
        res.code === ResultCode.SUCCESS ? msg.success('操作成功') : msg.error(res.message)
        maDialog.close()
        proTableRef.value.refresh()
        loadTree()
      }).catch((err: any) => {
        msg.alertError(err)
      }).finally(() => okLoadingState(false))
    }).catch(() => okLoadingState(false))
  },
})

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 161,
  header: {
    mainTitle: () => '分类管理',
    subTitle: () => '维护商品分类层级与状态',
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
  requestOptions: { api: page },
  onSearchReset: () => {
    proTableRef.value.setRequestParams({ parent_id: currentParentId.value }, false)
  },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(maDialog, formRef, parentNameMap),
})

function buildNameMap(list: any[]) {
  const map: Record<number, string> = {}
  const walk = (items: any[]) => {
    items.forEach((item) => {
      if (item?.id) {
        map[item.id] = item.name
      }
      if (Array.isArray(item.children) && item.children.length > 0) {
        walk(item.children)
      }
    })
  }
  walk(list)
  parentNameMap.value = map
}

async function loadTree() {
  const res = await tree(0)
  categoryTree.value = res.data || []
  buildNameMap(categoryTree.value)
  treeData.value = [
    { id: 0, name: '全部分类', children: categoryTree.value },
  ]
}

function handleTreeSelect(node: any) {
  if (!node || node.id === 0) {
    currentParentId.value = undefined
  }
  else {
    currentParentId.value = node.id
  }
  proTableRef.value.setRequestParams({ parent_id: currentParentId.value }, true)
}

function handleBatchDelete() {
  const ids = selections.value.map(item => item.id)
  if (ids.length < 1) {
    msg.warning('请选择要删除的数据')
    return
  }
  msg.delConfirm('确定删除选中的分类吗？').then(async () => {
    const tasks = ids.map((id: number) => remove(id))
    const results = await Promise.all(tasks)
    if (results.every(item => item.code === ResultCode.SUCCESS)) {
      msg.success('删除成功')
      proTableRef.value.refresh()
      loadTree()
    }
  })
}

onMounted(() => {
  loadTree()
})
</script>

<template>
  <div class="mine-layout flex justify-between pb-0 pl-3 pt-3">
    <div class="w-full rounded bg-[#fff] p-2 md:w-2/12 dark-bg-dark-8">
      <ma-tree
        :data="treeData"
        tree-key="name"
        node-key="id"
        :props="{ label: 'name' }"
        :expand-on-click-node="false"
        @node-click="handleTreeSelect"
      >
        <template #default="{ data }">
          <div class="mine-tree-node">
            <div class="label">
              {{ data.name }}
            </div>
          </div>
        </template>
      </ma-tree>
    </div>
    <div class="w-full md:w-10/12">
      <MaProTable ref="proTableRef" :options="options" :schema="schema">
        <template #actions>
          <el-button
            v-auth="['product:category:create']"
            type="primary"
            @click="() => {
              maDialog.setTitle('新增分类')
              maDialog.open({ formType: 'add' })
            }"
          >
            新增分类
          </el-button>
        </template>
        <template #toolbarLeft>
          <el-button
            v-auth="['product:category:delete']"
            type="danger"
            plain
            :disabled="selections.length < 1"
            @click="handleBatchDelete"
          >
            批量删除
          </el-button>
        </template>
        <template #empty>
          <el-empty>
            <el-button
              v-auth="['product:category:create']"
              type="primary"
              @click="() => {
                maDialog.setTitle('新增分类')
                maDialog.open({ formType: 'add' })
              }"
            >
              新增分类
            </el-button>
          </el-empty>
        </template>
      </MaProTable>
    </div>

    <component :is="maDialog.Dialog">
      <template #default="{ formType, data }">
        <CategoryForm ref="formRef" :form-type="formType" :data="data" />
      </template>
    </component>
  </div>
</template>

<style scoped lang="scss">

</style>
