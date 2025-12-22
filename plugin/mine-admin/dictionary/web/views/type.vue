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
import { create, deleteByIds, type DictionaryTypeVo, list, save } from '../api/DictionaryType.ts'
import useDialog from '@/hooks/useDialog.ts'
import type { MaFormExpose, MaFormItem, MaFormOptions } from '@mineadmin/form'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

const t = useTrans().globalTrans
const msg = useMessage()
const typeInfo = inject('typeInfo')
const typeList = ref<DictionaryTypeVo[]>([])
const maTreeRef = ref()

function getTypeList() {
  list({ page_size: 9999 }).then((res) => {
    typeList.value = res.data
  })
}

getTypeList()

const model = ref<DictionaryTypeVo>({} as DictionaryTypeVo)
const formRef = ref<MaFormExpose>()
const options = ref<MaFormOptions>({
  labelWidth: '80px',
})

const items = ref<MaFormItem[]>([
  {
    label: () => t('dictionary.dicType.name'),
    prop: 'name',
    render: () => <el-input />,
    renderProps: {
      placeholder: t('form.pleaseInput', { msg: t('dictionary.dicType.name') }),
    },
    itemProps: {
      rules: [{ required: true, message: t('form.pleaseInput', { msg: t('dictionary.dicType.name') }) }],
    },
  },
  {
    label: () => t('dictionary.dicType.code'),
    prop: 'code',
    render: () => <el-input />,
    renderProps: {
      placeholder: t('form.pleaseInput', { msg: t('dictionary.dicType.code') }),
    },
    itemProps: {
      rules: [{ required: true, message: t('form.pleaseInput', { msg: t('dictionary.dicType.code') }) }],
    },
  },
  {
    label: () => t('dictionary.dicType.status'),
    prop: 'status',
    render: () => <ma-dict-radio />,
    renderProps: {
      dictName: 'system-status',
    },
  },
  {
    label: () => t('dictionary.dicType.remark'),
    prop: 'remark',
    render: () => <el-input />,
    renderProps: {
      type: 'textarea',
      placeholder: t('form.pleaseInput', { msg: t('dictionary.dicType.remark') }),
    },
  },
])

const dl = useDialog({
  // 保存数据
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    if (['add', 'edit'].includes(formType)) {
      const elForm = formRef!.value!.getElFormRef()
      // 验证通过后
      elForm.validate().then(() => {
        switch (formType) {
          // 新增
          case 'add':
            create(model.value).then((res: any) => {
              res.code === ResultCode.SUCCESS ? msg.success(t('crud.createSuccess')) : msg.error(res.message)
              dl.close()
              getTypeList()
            }).catch((err: any) => {
              msg.alertError(err)
            })
            break
          // 修改
          case 'edit':
            save(model.value.id as number, model.value).then((res: any) => {
              res.code === 200 ? msg.success(t('crud.updateSuccess')) : msg.error(res.message)
              dl.close()
              getTypeList()
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
</script>

<template>
  <div>
    <ma-tree
      ref="maTreeRef"
      highlight-current
      tree-key="name"
      :data="typeList"
      node-key="id"
      :indent="5"
      auto-expand-parent
      class="h-full"
      @node-click="(node: DictionaryTypeVo) => typeInfo = node"
    >
      <template #default="{ data }">
        <div class="mine-tree-node">
          <div class="label">
            {{ data.name ?? 'unknown' }}
          </div>
          <div class="do" :class="{ '!inline-block': maTreeRef?.elTree?.getCurrentKey?.() === data.id }">
            <el-tag type="primary" class="mr-2">
              {{ data.code }}
            </el-tag>
            <el-button
              v-auth="['dataCenter:dictionary:typeUpdate']" size="small" circle type="success"
              @click.stop="() => {
                dl.setTitle(t('dictionary.typeCrud.update'))
                dl.open({ formType: 'edit' })
                Object.keys(data).map((key: string) => {
                  model[key] = key === 'status' ? Number(data[key]) : data[key]
                })
              }"
            >
              <ma-svg-icon name="i-heroicons:pencil" />
            </el-button>
            <el-popconfirm
              :title="t('crud.delDataMessage')"
              :confirm-button-text="t('crud.ok')"
              :cancel-button-text="t('crud.cancel')"
              @confirm.stop="async () => {
                await deleteByIds([data.id])
                getTypeList()
              }"
            >
              <template #reference>
                <el-button
                  v-auth="['dataCenter:dictionary:typeDelete']" size="small" circle type="danger"
                >
                  <ma-svg-icon name="i-heroicons:trash" />
                </el-button>
              </template>
            </el-popconfirm>
          </div>
        </div>
      </template>
      <template #extra>
        <el-button
          v-auth="['dataCenter:dictionary:typeSave']"
          type="primary"
          class="w-full"
          @click="() => {
            dl.setTitle(t('dictionary.typeCrud.add'))
            dl.open({ formType: 'add' })
            model = {
              status: 1,
            }
          }"
        >
          {{ t('dictionary.typeCrud.add') }}
        </el-button>
      </template>
    </ma-tree>

    <component :is="dl.Dialog">
      <ma-form ref="formRef" v-model="model" :options="options" :items="items" />
    </component>
  </div>
</template>

<style scoped lang="scss">

</style>
