<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'
import type { DiyTemplateVo } from '~/mall/api/diyTemplate'

import { ElMessage, ElMessageBox } from 'element-plus'
import { applyDiyTemplate, pageDiyTemplates } from '~/mall/api/diyTemplate'
import getSearchItems from './data/getSearchItems'
import getTableColumns from './data/getTableColumns'
import useDialog from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import TemplateForm from './form.vue'

defineOptions({ name: 'mall:diy:template' })

const msg = useMessage()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref<any>()
const applyVisible = ref(false)
const applying = ref<DiyTemplateVo | null>(null)
const applyForm = reactive({
  page_id: undefined as number | undefined,
})

const formDialog: UseDialogExpose = useDialog({
  lgWidth: '560px',
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
    mainTitle: () => 'DIY 模板',
    subTitle: () => '维护可复用装修模板并快速套用到页面草稿',
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
  requestOptions: { api: pageDiyTemplates },
})

const schema = ref<MaProTableSchema>({
  searchItems: getSearchItems(),
  tableColumns: getTableColumns(formDialog, openApply),
})

function openCreate() {
  formDialog.setTitle('新建模板')
  formDialog.open({ formType: 'add', data: null })
}

function openApply(row: DiyTemplateVo) {
  applying.value = row
  applyForm.page_id = undefined
  applyVisible.value = true
}

async function submitApply() {
  const templateId = applying.value?.id
  const pageId = applyForm.page_id
  if (!templateId || !pageId) {
    ElMessage.warning('请输入要套用的页面 ID')
    return
  }

  await ElMessageBox.confirm('套用模板会覆盖目标页面当前草稿，确认继续？', '套用模板', {
    type: 'warning',
  })
  await applyDiyTemplate(templateId, pageId)
  ElMessage.success('模板已套用为页面草稿')
  applyVisible.value = false
}
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="proTableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button v-auth="['mall:diy:template:create']" type="primary" @click="openCreate">
          <ma-svg-icon name="ph:plus" size="14" />
          新建模板
        </el-button>
      </template>
      <template #empty>
        <el-empty description="暂无 DIY 模板">
          <el-button v-auth="['mall:diy:template:create']" type="primary" @click="openCreate">
            新建模板
          </el-button>
        </el-empty>
      </template>
    </MaProTable>

    <component :is="formDialog.Dialog">
      <template #default="{ formType, data }">
        <TemplateForm ref="formRef" :form-type="formType" :data="data" />
      </template>
    </component>

    <el-dialog v-model="applyVisible" title="套用模板" width="420px">
      <el-form label-width="90px">
        <el-form-item label="模板">
          <span>{{ applying?.name }}</span>
        </el-form-item>
        <el-form-item label="页面ID" required>
          <el-input-number v-model="applyForm.page_id" :min="1" controls-position="right" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="applyVisible = false">
          取消
        </el-button>
        <el-button type="primary" @click="submitApply">
          确认套用
        </el-button>
      </template>
    </el-dialog>
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
