<script setup lang="ts">
import OptionalPluginPage from "@/components/optional-plugin-page/index.vue"
const loader = () => import('../../../../../../../plugins/diy/web/views/components/ComponentLibrary.vue')
</script>
<template><OptionalPluginPage plugin="since/diy" :loader="loader" /></template>
