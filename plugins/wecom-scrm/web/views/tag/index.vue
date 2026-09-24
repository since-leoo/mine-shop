<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { getTags, saveTag, deleteTag, type WecomTag } from '../../api/wecom'

const loading = ref(false)
const rows = ref<WecomTag[]>([])
const total = ref(0)
const query = reactive({ page: 1, pageSize: 20, name: '' })
const dialog = ref(false)
const form = reactive<Partial<WecomTag>>({ name: '', group_id: '', color: '' })

async function load() {
  loading.value = true
  const result = await getTags(query)
  rows.value = result.data?.items ?? []
  total.value = result.data?.total ?? 0
  loading.value = false
}
function createTag() { Object.assign(form, { id: undefined, name: '', group_id: '', color: '' }); dialog.value = true }
function editTag(row: WecomTag) { Object.assign(form, row); dialog.value = true }
async function submit() { await saveTag(form); dialog.value = false; await load() }
async function remove(row: WecomTag) { await deleteTag(row.id); await load() }
onMounted(load)
</script>

<template>
  <div class="p-4">
    <ElCard shadow="never">
      <div class="toolbar">
        <ElInput v-model="query.name" placeholder="标签名称" clearable @keyup.enter="load" />
        <ElButton type="primary" @click="load">查询</ElButton>
        <ElButton type="success" @click="createTag">新增标签</ElButton>
      </div>
      <ElTable v-loading="loading" :data="rows" stripe>
        <ElTableColumn prop="name" label="标签名称" />
        <ElTableColumn prop="group_id" label="标签组" />
        <ElTableColumn prop="tag_id" label="企业微信标签 ID" />
        <ElTableColumn prop="color" label="颜色" />
        <ElTableColumn label="操作" width="160">
          <template #default="{ row }"><ElButton link type="primary" @click="editTag(row)">编辑</ElButton><ElButton link type="danger" @click="remove(row)">删除</ElButton></template>
        </ElTableColumn>
      </ElTable>
      <ElPagination v-model:current-page="query.page" v-model:page-size="query.pageSize" :total="total" layout="total, sizes, prev, pager, next" @change="load" />
    </ElCard>
    <ElDialog v-model="dialog" title="标签" width="420px"><ElForm label-width="90px"><ElFormItem label="名称"><ElInput v-model="form.name" /></ElFormItem><ElFormItem label="标签组"><ElInput v-model="form.group_id" /></ElFormItem><ElFormItem label="颜色"><ElInput v-model="form.color" /></ElFormItem></ElForm><template #footer><ElButton @click="dialog = false">取消</ElButton><ElButton type="primary" @click="submit">保存</ElButton></template></ElDialog>
  </div>
</template>

<style scoped>
.toolbar { display: flex; gap: 12px; margin-bottom: 16px; }
.toolbar .el-input { width: 220px; }
.el-pagination { margin-top: 16px; justify-content: flex-end; }
</style>
