<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'
import type { DiyPageVo, DiyPublishRecordVo } from '~/mall/api/diyPage'

import { ElMessage, ElMessageBox } from 'element-plus'
import { useRouter } from 'vue-router'
import {
  createDiyPreviewToken,
  getDiyPublishRecords,
  pageDiyPages,
  rollbackDiyPage,
  saveDiyPageAsTemplate,
} from '~/mall/api/diyPage'
import getSearchItems from './data/getSearchItems'
import getTableColumns from './data/getTableColumns'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import PageForm from './form.vue'

defineOptions({ name: 'mall:diy:page' })

const router = useRouter()
const msg = useMessage()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref<any>()
const templateVisible = ref(false)
const recordVisible = ref(false)
const templateSource = ref<DiyPageVo | null>(null)
const recordSource = ref<DiyPageVo | null>(null)
const publishRecords = ref<DiyPublishRecordVo[]>([])
const recordLoading = ref(false)
const templateForm = reactive({
  category_id: 1,
  name: '',
  cover: '',
  description: '',
  sort: 0,
  is_enabled: true,
})

const formDialog: UseDialogExpose = useDialog({
  lgWidth: '520px',
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    formRef.value.maForm.getElFormRef().validate().then(() => {
      const action = formType === 'add' ? formRef.value.add : formRef.value.edit
      action().then((res: any) => {
        res.code === ResultCode.SUCCESS ? msg.success('操作成功') : msg.error(res.message)
        formDialog.close()
        proTableRef.value?.refresh()
      }).catch((err: any) => {
        msg.alertError(err)
      }).finally(() => okLoadingState(false))
    }).catch(() => okLoadingState(false))
  },
})

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 161,
  header: {
    mainTitle: () => 'DIY 页面',
    subTitle: () => '管理商城首页、活动页和多端装修页面',
  },
  searchOptions: {
    fold: true,
    text: {
      searchBtn: () => '查询',
      resetBtn: () => '重置',
      isFoldBtn: () => '展开',
      notFoldBtn: () => '收起',
    },
  },
  searchFormOptions: { labelWidth: '100px' },
  requestOptions: { api: pageDiyPages },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(formDialog, router, openPublishRecords, openSaveAsTemplate, handlePreview),
})

function openCreate() {
  formDialog.setTitle('新建页面')
  formDialog.open({ formType: 'add', data: null })
}

function openSaveAsTemplate(row: DiyPageVo) {
  if (!row.published_version) {
    ElMessage.warning('请先发布页面后再保存为模板')
    return
  }
  templateSource.value = row
  templateForm.category_id = 1
  templateForm.name = `${row.title}模板`
  templateForm.cover = ''
  templateForm.description = row.description || ''
  templateForm.sort = 0
  templateForm.is_enabled = true
  templateVisible.value = true
}

async function submitSaveAsTemplate() {
  const row = templateSource.value
  if (!row?.id || !row.published_version?.schema) {
    ElMessage.warning('缺少已发布页面结构')
    return
  }
  if (!templateForm.name) {
    ElMessage.warning('请填写模板名称')
    return
  }

  await saveDiyPageAsTemplate(row.id, {
    category_id: templateForm.category_id,
    name: templateForm.name,
    page_key: row.page_key,
    page_type: row.page_type,
    cover: templateForm.cover || null,
    description: templateForm.description || null,
    schema: row.published_version.schema,
    sort: templateForm.sort,
    is_enabled: templateForm.is_enabled,
  })
  ElMessage.success('已保存为模板')
  templateVisible.value = false
}

async function openPublishRecords(row: DiyPageVo) {
  recordSource.value = row
  recordVisible.value = true
  recordLoading.value = true
  try {
    const res = await getDiyPublishRecords(row.id as number)
    publishRecords.value = res.data || []
  }
  finally {
    recordLoading.value = false
  }
}

async function handleRollback(versionId: number) {
  const pageId = recordSource.value?.id
  if (!pageId) {
    return
  }
  await ElMessageBox.confirm('确认回滚到该历史发布版本？当前线上版本会被替换。', '回滚页面', {
    type: 'warning',
  })
  await rollbackDiyPage(pageId, versionId)
  ElMessage.success('页面已回滚')
  recordVisible.value = false
  proTableRef.value?.refresh()
}

async function handlePreview(row: DiyPageVo) {
  if (!row.published_version_id) {
    ElMessage.warning('请先发布页面后再预览')
    return
  }
  const res = await createDiyPreviewToken(row.id as number, row.published_version_id)
  const token = res.data?.token
  if (!token) {
    ElMessage.warning('预览令牌生成失败')
    return
  }
  ElMessage.success(`预览令牌：${token}`)
}
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button v-auth="['mall:diy:page:create']" type="primary" @click="openCreate">
          <ma-svg-icon name="ph:plus" size="14" />
          新建页面
        </el-button>
      </template>
      <template #empty>
        <el-empty description="暂无 DIY 页面">
          <el-button v-auth="['mall:diy:page:create']" type="primary" @click="openCreate">
            新建页面
          </el-button>
        </el-empty>
      </template>
    </MaProTable>

    <component :is="formDialog.Dialog">
      <template #default="{ formType, data }">
        <PageForm ref="formRef" :form-type="formType" :data="data" />
      </template>
    </component>

    <el-dialog v-model="templateVisible" title="保存为模板" width="520px">
      <el-form label-width="96px">
        <el-form-item label="来源页面">
          <span>{{ templateSource?.title }}</span>
        </el-form-item>
        <el-form-item label="分类ID" required>
          <el-input-number v-model="templateForm.category_id" :min="1" controls-position="right" />
        </el-form-item>
        <el-form-item label="模板名称" required>
          <el-input v-model="templateForm.name" />
        </el-form-item>
        <el-form-item label="封面">
          <el-input v-model="templateForm.cover" placeholder="图片 URL" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="templateForm.sort" :min="0" controls-position="right" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="templateForm.is_enabled" />
        </el-form-item>
        <el-form-item label="说明">
          <el-input v-model="templateForm.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="templateVisible = false">
          取消
        </el-button>
        <el-button type="primary" @click="submitSaveAsTemplate">
          保存
        </el-button>
      </template>
    </el-dialog>

    <el-drawer v-model="recordVisible" title="发布记录" size="620px">
      <el-table v-loading="recordLoading" :data="publishRecords" border>
        <el-table-column prop="publish_type" label="类型" width="100" />
        <el-table-column prop="publish_status" label="状态" width="100" />
        <el-table-column prop="version_id" label="版本" width="90" />
        <el-table-column prop="published_at" label="发布时间" min-width="160" />
        <el-table-column prop="scheduled_at" label="定时时间" min-width="160" />
        <el-table-column label="操作" width="90" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.version_id && row.publish_status === 'published'"
              type="primary"
              size="small"
              plain
              @click="handleRollback(row.version_id)"
            >
              回滚
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-drawer>
  </div>
</template>

<style scoped lang="scss">
:deep(.diy-table-actions) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  white-space: nowrap;

  .el-button {
    margin-left: 0;
  }
}
</style>
