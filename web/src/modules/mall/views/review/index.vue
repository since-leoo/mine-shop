<script setup lang="ts">
import OptionalPluginPage from "@/components/optional-plugin-page/index.vue"
const loader = () => import('../../../../../../plugins/product-review/web/views/review/index.vue')
</script>
<template><OptionalPluginPage plugin="since/product-review" :loader="loader" /></template>
