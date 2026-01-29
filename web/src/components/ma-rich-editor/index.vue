<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 -
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import { onBeforeUnmount, shallowRef } from 'vue'
import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
import type { IDomEditor, IEditorConfig, IToolbarConfig } from '@wangeditor/editor'
import '@wangeditor/editor/dist/css/style.css'
import { upload as uploadFile } from '~/base/api/attachment'
import { useMessage } from '@/hooks/useMessage.ts'

defineOptions({ name: 'MaRichEditor' })

const {
  modelValue = '',
  placeholder = '请输入内容',
  height = 360,
} = defineProps<{
  modelValue?: string
  placeholder?: string
  height?: number
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const msg = useMessage()
const editorRef = shallowRef<IDomEditor>()

const toolbarConfig: Partial<IToolbarConfig> = {}
const editorConfig: Partial<IEditorConfig> = {
  placeholder,
  MENU_CONF: {
    uploadImage: {
      async customUpload(file: File, insertFn: (url: string, alt?: string, href?: string) => void) {
        try {
          const response = await uploadFile(file)
          if (response.code === 200 && response.data?.url) {
            insertFn(response.data.url, response.data.origin_name || '', response.data.url)
          }
          else {
            msg.error(response.message || '图片上传失败')
          }
        }
        catch (error: any) {
          msg.error(error.message || '图片上传失败')
        }
      },
    },
  },
}

const handleCreated = (editor: IDomEditor) => {
  editorRef.value = editor
}

onBeforeUnmount(() => {
  const editor = editorRef.value
  if (editor) {
    editor.destroy()
  }
})
</script>

<template>
  <div class="ma-rich-editor">
    <Toolbar
      class="ma-rich-editor__toolbar"
      :editor="editorRef"
      :default-config="toolbarConfig"
      mode="default"
    />
    <Editor
      class="ma-rich-editor__content"
      :style="{ height: `${height}px` }"
      :model-value="modelValue"
      :default-config="editorConfig"
      mode="default"
      @update:model-value="val => emit('update:modelValue', val as string)"
      @onCreated="handleCreated"
    />
  </div>
</template>

<style scoped lang="scss">
.ma-rich-editor {
  border: 1px solid var(--el-border-color);
  border-radius: 4px;
  overflow: hidden;
}

.ma-rich-editor__toolbar {
  border-bottom: 1px solid var(--el-border-color);
}

.ma-rich-editor__content {
  overflow-y: auto;
}
</style>
