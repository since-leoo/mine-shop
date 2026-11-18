<script setup lang="ts">
import { ref } from 'vue'
import { componentRegistry } from '../../../../plugins/diy/web/views/schema/componentRegistry'
import PhonePreview from '../../../../plugins/diy/web/views/components/PhonePreview.vue'
import PropertyPanel from '../../../../plugins/diy/web/views/components/PropertyPanel.vue'

const component = ref(componentRegistry.find(item => item.type === 'distribution-center')!.defaults())
const schema = ref({ version: 1 as const, page: { key: 'user', title: '个人中心' }, components: [component.value] })
function update(value: typeof component.value) {
  component.value = value
  schema.value.components = [value]
}
</script>

<template>
  <div style="display:flex">
    <PhonePreview :schema="schema" :selected-id="component.id" /><PropertyPanel :component="component" @update="update" />
  </div>
</template>
